<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\UrlUtils;
use MWStake\MediaWiki\Component\TokenAuthenticator\UserTokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\UserTokenAuthenticator
 */
class UserTokenAuthenticatorTest extends TestCase {

	private UserTokenAuthenticator $authenticator;
	private HashBagOStuff $cache;
	private UserFactory $userFactory;
	private UserGroupManager $groupManager;
	private UserOptionsLookup $userOptionsLookup;
	private LanguageNameUtils $languageNameUtils;
	private Language $contentLanguage;
	private HookContainer $hookContainer;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new HashBagOStuff();
		$this->userFactory = $this->createMock( UserFactory::class );
		$this->groupManager = $this->createMock( UserGroupManager::class );
		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->languageNameUtils = $this->createMock( LanguageNameUtils::class );
		$this->contentLanguage = $this->createMock( Language::class );
		$this->hookContainer = $this->createMock( HookContainer::class );
		$urlUtils = $this->createMock( UrlUtils::class );

		$this->authenticator = new UserTokenAuthenticator(
			$urlUtils,
			$this->cache,
			$this->userFactory,
			$this->groupManager,
			$this->userOptionsLookup,
			$this->languageNameUtils,
			$this->contentLanguage,
			$this->hookContainer
		);
	}

	private function getMockUser( string $name, bool $registered, int $id = 0 ): UserIdentity {
		$user = $this->getMockBuilder( UserIdentity::class )
			->onlyMethods( [ 'getName', 'getId', 'isRegistered', 'equals', 'assertWiki', 'getWikiId' ] )
			->addMethods( [ 'isAnon', 'getRealName' ] )
			->getMock();
		$user->method( 'getName' )->willReturn( $name );
		$user->method( 'isRegistered' )->willReturn( $registered );
		$user->method( 'getId' )->willReturn( $id );
		$user->method( 'isAnon' )->willReturn( !$registered );
		$user->method( 'assertWiki' )->willReturn( null );
		$user->method( 'getWikiId' )->willReturn( false );
		$user->method( 'getRealName' )->willReturn( '' );
		return $user;
	}

	public function testGenerateTokenForRegisteredUser() {
		$user = $this->getMockUser( 'TestUser', true, 123 );
		$token = $this->authenticator->generateToken( $user );

		$this->assertIsString( $token );
		$this->assertEquals( 32, strlen( $token ) );
	}

	public function testGenerateTokenForAnonymousUser() {
		$user = $this->getMockUser( '192.168.1.1', false );
		$token = $this->authenticator->generateToken( $user );

		$this->assertIsString( $token );
		$this->assertEquals( 32, strlen( $token ) );
	}

	public function testGenerateTokenStoresUserData() {
		$user = $this->getMockUser( 'TestUser', true, 456 );
		$token = $this->authenticator->generateToken( $user );

		$data = $this->authenticator->doVerifyToken( $token );
		$this->assertNotNull( $data );
		$this->assertEquals( 'TestUser', $data['user'] );
		$this->assertTrue( $data['registered'] );
	}

	public function testVerifyTokenReturnsNullForInvalidToken() {
		$result = $this->authenticator->verifyToken( 'invalidtoken' );
		$this->assertNull( $result );
	}

	public function testVerifyTokenReturnsUserForValidToken() {
		$user = $this->getMockUser( 'TestUser', true, 789 );
		$token = $this->authenticator->generateToken( $user );

		$mockReturnedUser = $this->getMockBuilder( \MediaWiki\User\User::class )
			->disableOriginalConstructor()
			->getMock();
		$this->userFactory->method( 'newFromName' )
			->with( 'TestUser' )
			->willReturn( $mockReturnedUser );

		$result = $this->authenticator->verifyToken( $token );
		$this->assertInstanceOf( UserIdentity::class, $result );
	}

	public function testVerifyTokenHandlesAnonymousUser() {
		$user = $this->getMockUser( '192.168.1.1', false );
		$token = $this->authenticator->generateToken( $user );

		$mockReturnedUser = $this->getMockBuilder( \MediaWiki\User\User::class )
			->disableOriginalConstructor()
			->getMock();
		$this->userFactory->method( 'newAnonymous' )
			->with( '192.168.1.1' )
			->willReturn( $mockReturnedUser );

		$result = $this->authenticator->verifyToken( $token );
		$this->assertInstanceOf( UserIdentity::class, $result );
	}

	public function testGetAuthInfoReturnsAuthInfo() {
		$user = $this->getMockUser( 'TestUser', true, 123 );

		$this->groupManager->method( 'getUserEffectiveGroups' )
			->with( $user )
			->willReturn( [ 'user', 'sysop' ] );

		$this->userOptionsLookup->method( 'getOption' )
			->with( $user, 'language', '' )
			->willReturn( 'de' );

		$this->languageNameUtils->method( 'isValidCode' )
			->with( 'de' )
			->willReturn( true );

		$this->hookContainer->method( 'run' )
			->with( 'MWStakeTokenAuthenticatorGetAuthInfo', $this->anything() );

		$authInfo = $this->authenticator->getAuthInfo( $user );

		$this->assertNotNull( $authInfo );
		$this->assertSame( $user, $authInfo->getUser() );
		$this->assertEquals( [ 'user', 'sysop' ], $authInfo->getGroups() );
	}

	public function testGetAuthInfoHandlesAnonymousUser() {
		$user = $this->getMockUser( '192.168.1.1', false );

		$this->groupManager->method( 'getUserEffectiveGroups' )
			->willReturn( [] );

		$this->userOptionsLookup->method( 'getOption' )
			->willReturn( '' );

		$this->contentLanguage->method( 'getCode' )
			->willReturn( 'en' );

		$this->hookContainer->method( 'run' )
			->willReturnCallback( static function ( $hook, $args ) {
				$args[1]['anon'] = true;
				return true;
			} );

		$authInfo = $this->authenticator->getAuthInfo( $user );

		$this->assertNotNull( $authInfo );
		$this->assertSame( $user, $authInfo->getUser() );
	}

	public function testGetAuthInfoUsesContentLanguageWhenUserLanguageInvalid() {
		$user = $this->getMockUser( 'TestUser', true, 123 );

		$this->groupManager->method( 'getUserEffectiveGroups' )
			->willReturn( [] );

		$this->userOptionsLookup->method( 'getOption' )
			->with( $user, 'language', '' )
			->willReturn( 'invalid-code' );

		$this->languageNameUtils->method( 'isValidCode' )
			->with( 'invalid-code' )
			->willReturn( false );

		$this->contentLanguage->method( 'getCode' )
			->willReturn( 'en' );

		$this->hookContainer->method( 'run' );

		$authInfo = $this->authenticator->getAuthInfo( $user );
		$json = $authInfo->jsonSerialize();

		$this->assertEquals( 'en', $json['lang_code'] );
	}
}
