<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests\Unit;

use InvalidArgumentException;
use MediaWiki\WikiMap\WikiMap;
use MWStake\MediaWiki\Component\TokenAuthenticator\TokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\TokenAuthenticator
 */
class TokenAuthenticatorTest extends TestCase {

	private TokenAuthenticator $authenticator;
	private HashBagOStuff $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new HashBagOStuff();
		$this->authenticator = new TokenAuthenticator( $this->cache );
	}

	public function testDoGenerateTokenCreatesValidToken() {
		$token = $this->authenticator->doGenerateToken();
		$this->assertIsString( $token );
		$this->assertEquals( 32, strlen( $token ) );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/', $token );
	}

	public function testDoGenerateTokenStoresDataInCache() {
		$data = [ 'key' => 'value' ];
		$token = $this->authenticator->doGenerateToken( $data );

		$storedData = $this->authenticator->doVerifyToken( $token );
		$this->assertNotNull( $storedData );
		$this->assertEquals( 'value', $storedData['key'] );
		$this->assertArrayHasKey( 'wiki', $storedData );
	}

	public function testDoGenerateTokenIncludesWikiId() {
		$token = $this->authenticator->doGenerateToken();
		$storedData = $this->authenticator->doVerifyToken( $token );

		$this->assertNotNull( $storedData );
		$this->assertArrayHasKey( 'wiki', $storedData );
		$this->assertEquals( WikiMap::getCurrentWikiId(), $storedData['wiki'] );
	}

	public function testDoVerifyTokenReturnsNullForInvalidToken() {
		$result = $this->authenticator->doVerifyToken( 'invalidtoken' );
		$this->assertNull( $result );
	}

	public function testDoVerifyTokenReturnsDataForValidToken() {
		$data = [ 'test' => 'data' ];
		$token = $this->authenticator->doGenerateToken( $data );

		$result = $this->authenticator->doVerifyToken( $token );
		$this->assertIsArray( $result );
		$this->assertEquals( 'data', $result['test'] );
	}

	public function testDoGenerateTokenWithIssuerRequiresSalt() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Salt must be set to generate a token with issuer.' );

		$this->authenticator->doGenerateTokenWithIssuer();
	}

	public function testDoGenerateTokenWithIssuerCreatesValidTokenWithSalt() {
		$authenticator = new TokenAuthenticator( $this->cache, 'test-salt' );
		$encodedToken = $authenticator->doGenerateTokenWithIssuer( [ 'user' => 'TestUser' ] );

		$this->assertIsString( $encodedToken );
		$decoded = json_decode( base64_decode( $encodedToken ), true );

		$this->assertArrayHasKey( 'verifyCallback', $decoded );
		$this->assertArrayHasKey( 'token', $decoded );
		$this->assertArrayHasKey( 'sig', $decoded );
		$this->assertEquals( 32, strlen( $decoded['token'] ) );
	}

	public function testDoGenerateTokenWithIssuerSignatureIsValid() {
		$salt = 'test-salt-secret';
		$authenticator = new TokenAuthenticator( $this->cache, $salt );
		$encodedToken = $authenticator->doGenerateTokenWithIssuer( [ 'user' => 'TestUser' ] );

		$decoded = json_decode( base64_decode( $encodedToken ), true );
		$expectedSig = hash_hmac(
			'sha256',
			$decoded['verifyCallback'] . $decoded['token'],
			$salt
		);

		$this->assertEquals( $expectedSig, $decoded['sig'] );
	}

	public function testMultipleTokensAreUnique() {
		$token1 = $this->authenticator->doGenerateToken();
		$token2 = $this->authenticator->doGenerateToken();

		$this->assertNotEquals( $token1, $token2 );
	}
}
