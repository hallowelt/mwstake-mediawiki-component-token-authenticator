<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests\Unit;

use MWStake\MediaWiki\Component\TokenAuthenticator\AppTokenAuthenticator;
use PHPUnit\Framework\TestCase;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\AppTokenAuthenticator
 */
class AppTokenAuthenticatorTest extends TestCase {

	private AppTokenAuthenticator $authenticator;
	private HashBagOStuff $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new HashBagOStuff();
		$this->authenticator = new AppTokenAuthenticator( $this->cache, 'test-salt' );
	}

	public function testGenerateTokenCreatesValidToken() {
		$token = $this->authenticator->generateToken();
		$this->assertIsString( $token );
		$this->assertNotEmpty( $token );
	}

	public function testGenerateTokenCreatesBase64EncodedData() {
		$token = $this->authenticator->generateToken();
		$decoded = base64_decode( $token, true );
		$this->assertNotFalse( $decoded );

		$data = json_decode( $decoded, true );
		$this->assertIsArray( $data );
	}

	public function testGenerateTokenIncludesAppFlag() {
		$token = $this->authenticator->generateToken();
		$decoded = json_decode( base64_decode( $token ), true );

		$this->assertArrayHasKey( 'token', $decoded );

		$storedData = $this->authenticator->doVerifyToken( $decoded['token'] );
		$this->assertNotNull( $storedData );
		$this->assertArrayHasKey( 'app', $storedData );
		$this->assertTrue( $storedData['app'] );
	}

	public function testGenerateTokenIncludesVerifyCallback() {
		$token = $this->authenticator->generateToken();
		$decoded = json_decode( base64_decode( $token ), true );

		$this->assertArrayHasKey( 'verifyCallback', $decoded );
		$this->assertIsString( $decoded['verifyCallback'] );
	}

	public function testGenerateTokenIncludesSignature() {
		$token = $this->authenticator->generateToken();
		$decoded = json_decode( base64_decode( $token ), true );

		$this->assertArrayHasKey( 'sig', $decoded );
		$this->assertIsString( $decoded['sig'] );
		$this->assertEquals( 64, strlen( $decoded['sig'] ) );
	}

	public function testGeneratedTokensAreUnique() {
		$token1 = $this->authenticator->generateToken();
		$token2 = $this->authenticator->generateToken();

		$this->assertNotEquals( $token1, $token2 );
	}
}
