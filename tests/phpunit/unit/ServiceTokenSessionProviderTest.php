<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests\Unit;

use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MWStake\MediaWiki\Component\TokenAuthenticator\AppTokenAuthenticator;
use MWStake\MediaWiki\Component\TokenAuthenticator\ServiceTokenSessionProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\ServiceTokenSessionProvider
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\ServiceUserConfiguration
 */
class ServiceTokenSessionProviderTest extends TestCase {

	/**
	 * @dataProvider provideUserContexts
	 * @param array $tokenData
	 * @param string $expected
	 */
	public function testMatchTokenUsesConfiguredUserContext( array $tokenData, string $expected ) {
		$provider = $this->newProvider( [ $tokenData + [ 'token' => 'test-token' ] ] );
		$matchToken = new ReflectionMethod( $provider, 'matchToken' );

		$this->assertTrue( $matchToken->invoke( $provider, 'ApiKey test-token' ) );
		$userName = new ReflectionProperty( $provider, 'serviceUserName' );
		$this->assertSame( $expected, $userName->getValue( $provider ) );
	}

	public static function provideUserContexts(): array {
		return ServiceUserConfigurationTest::provideUserContexts();
	}

	public function testDefaultContextDoesNotInheritPreviousTokenUser() {
		$provider = $this->newProvider( [
			[ 'token' => 'custom', 'user-context' => 'Custom user' ],
			[ 'token' => 'default' ],
		] );
		$matchToken = new ReflectionMethod( $provider, 'matchToken' );
		$userName = new ReflectionProperty( $provider, 'serviceUserName' );

		$this->assertTrue( $matchToken->invoke( $provider, 'ApiKey custom' ) );
		$this->assertSame( 'Custom user', $userName->getValue( $provider ) );
		$this->assertTrue( $matchToken->invoke( $provider, 'ApiKey default' ) );
		$this->assertSame( 'Default user', $userName->getValue( $provider ) );
	}

	private function newProvider( array $tokens ): ServiceTokenSessionProvider {
		return new ServiceTokenSessionProvider(
			$this->createMock( UserFactory::class ),
			$this->createMock( AppTokenAuthenticator::class ),
			$this->createMock( UserGroupManager::class ),
			[
				'service-user' => 'Default user',
				'tokens' => $tokens,
				'main-cidr' => '',
			]
		);
	}
}
