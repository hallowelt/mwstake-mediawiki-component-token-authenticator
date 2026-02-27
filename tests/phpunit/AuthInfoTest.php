<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests;

use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\TokenAuthenticator\AuthInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\AuthInfo
 */
class AuthInfoTest extends TestCase {

	private function getMockUser( string $name, int $id ): UserIdentity {
		$user = $this->createMock( UserIdentity::class );
		$user->method( 'getName' )->willReturn( $name );
		$user->method( 'getId' )->willReturn( $id );
		$user->method( 'assertWiki' )->willReturn( null );
		$user->method( 'getWikiId' )->willReturn( false );
		return $user;
	}

	private function getMockUserWithRealName( string $name, string $realName, int $id ) {
		$user = $this->getMockBuilder( UserIdentity::class )
			->onlyMethods( [ 'getName', 'getId', 'isRegistered', 'equals', 'assertWiki', 'getWikiId' ] )
			->addMethods( [ 'getRealName' ] )
			->getMock();
		$user->method( 'getName' )->willReturn( $name );
		$user->method( 'getRealName' )->willReturn( $realName );
		$user->method( 'getId' )->willReturn( $id );
		$user->method( 'assertWiki' )->willReturn( null );
		$user->method( 'getWikiId' )->willReturn( false );
		return $user;
	}

	public function testGetUserReturnsCorrectUser() {
		$user = $this->getMockUser( 'TestUser', 123 );
		$authInfo = new AuthInfo( $user, 'wiki1', 'en', [] );

		$this->assertSame( $user, $authInfo->getUser() );
	}

	public function testGetGroupsReturnsCorrectGroups() {
		$user = $this->getMockUser( 'TestUser', 123 );
		$groups = [ 'sysop', 'bureaucrat' ];
		$authInfo = new AuthInfo( $user, 'wiki1', 'en', $groups );

		$this->assertEquals( $groups, $authInfo->getGroups() );
	}

	public function testJsonSerializeContainsAllFields() {
		$user = $this->getMockUserWithRealName( 'TestUser', 'Test User Real', 456 );
		$groups = [ 'editor', 'reviewer' ];
		$metadata = [ 'custom' => 'data' ];
		$authInfo = new AuthInfo( $user, 'mywiki', 'de', $groups, $metadata );

		$json = $authInfo->jsonSerialize();

		$this->assertArrayHasKey( 'username', $json );
		$this->assertArrayHasKey( 'real_name', $json );
		$this->assertArrayHasKey( 'id', $json );
		$this->assertArrayHasKey( 'wiki_id', $json );
		$this->assertArrayHasKey( 'lang_code', $json );
		$this->assertArrayHasKey( 'groups', $json );
		$this->assertArrayHasKey( 'meta', $json );

		$this->assertEquals( 'TestUser', $json['username'] );
		$this->assertEquals( 'Test User Real', $json['real_name'] );
		$this->assertEquals( 456, $json['id'] );
		$this->assertEquals( 'mywiki', $json['wiki_id'] );
		$this->assertEquals( 'de', $json['lang_code'] );
		$this->assertEquals( $groups, $json['groups'] );
		$this->assertEquals( $metadata, $json['meta'] );
	}

	public function testJsonSerializeWithEmptyMetadata() {
		$user = $this->getMockUserWithRealName( 'User', '', 1 );
		$authInfo = new AuthInfo( $user, 'wiki', 'en', [] );

		$json = $authInfo->jsonSerialize();

		$this->assertArrayHasKey( 'meta', $json );
		$this->assertEquals( [], $json['meta'] );
	}

	public function testJsonSerializeCanBeEncoded() {
		$user = $this->getMockUserWithRealName( 'TestUser', 'Real Name', 789 );
		$authInfo = new AuthInfo( $user, 'wiki', 'fr', [ 'admin' ] );

		$encoded = json_encode( $authInfo );
		$this->assertIsString( $encoded );

		$decoded = json_decode( $encoded, true );
		$this->assertEquals( 'TestUser', $decoded['username'] );
		$this->assertEquals( 789, $decoded['id'] );
		$this->assertEquals( 'fr', $decoded['lang_code'] );
	}
}
