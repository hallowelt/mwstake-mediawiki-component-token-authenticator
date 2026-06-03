<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests;

use InvalidArgumentException;
use MWStake\MediaWiki\Component\TokenAuthenticator\CIDRValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\CIDRValidator
 */
class CIDRValidatorTest extends TestCase {

	public function testConstructorThrowsExceptionForInvalidCIDR() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid CIDR range provided' );

		$validator = new CIDRValidator();
		$validator->validateIP( '', 'invalid_cidr' );
	}

	public function testValidateIPReturnsTrueForMatchingIPv4() {
		$validator = new CIDRValidator();
		$this->assertTrue( $validator->validateIP( '192.168.1.100', '192.168.1.0/24' ) );
		$this->assertTrue( $validator->validateIP( '192.168.1.1', '192.168.1.0/24' ) );
		$this->assertTrue( $validator->validateIP( '192.168.1.254', '192.168.1.0/24' ) );
	}

	public function testValidateIPReturnsFalseForNonMatchingIPv4() {
		$validator = new CIDRValidator();
		$this->assertFalse( $validator->validateIP( '192.168.2.1', '192.168.1.0/24' ) );
		$this->assertFalse( $validator->validateIP( '10.0.0.1', '192.168.1.0/24' ) );
		$this->assertFalse( $validator->validateIP( '172.16.0.1', '192.168.1.0/24' ) );
	}

	public function testValidateIPReturnsTrueForMatchingIPv6() {
		$validator = new CIDRValidator();
		$this->assertTrue( $validator->validateIP( '2001:db8::1', '2001:db8::/32' ) );
		$this->assertTrue( $validator->validateIP( '2001:db8:0:0:0:0:0:1', '2001:db8::/32' ) );
	}

	public function testValidateIPReturnsFalseForNonMatchingIPv6() {
		$validator = new CIDRValidator();
		$this->assertFalse( $validator->validateIP( '2001:db9::1', '2001:db8::/32' ) );
		$this->assertFalse( $validator->validateIP( 'fe80::1', '2001:db8::/32' ) );
	}

	public function testValidateIPReturnsTrueWhenNoCIDRSet() {
		$validator = new CIDRValidator();
		$this->assertTrue( $validator->validateIP( '192.168.1.1', '' ) );
		$this->assertTrue( $validator->validateIP( '10.0.0.1', '' ) );
		$this->assertTrue( $validator->validateIP( '2001:db8::1', '' ) );
		$this->assertTrue( $validator->validateIP( 'any-ip-here', '' ) );
	}

	public function testValidateIPWithSingleIPCIDR() {
		$validator = new CIDRValidator();
		$this->assertTrue( $validator->validateIP( '192.168.1.50', '192.168.1.50/32' ) );
		$this->assertFalse( $validator->validateIP( '192.168.1.51', '192.168.1.50/32' ) );
	}

	public function testValidateIPWithBroadCIDR() {
		$validator = new CIDRValidator();
		$this->assertTrue( $validator->validateIP( '10.0.0.1', '10.0.0.0/8' ) );
		$this->assertTrue( $validator->validateIP( '10.255.255.255', '10.0.0.0/8' ) );
		$this->assertFalse( $validator->validateIP( '11.0.0.1', '10.0.0.0/8' ) );
	}
}
