<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator\Tests\Unit;

use MWStake\MediaWiki\Component\TokenAuthenticator\ServiceUserConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MWStake\MediaWiki\Component\TokenAuthenticator\ServiceUserConfiguration
 */
class ServiceUserConfigurationTest extends TestCase {

	/**
	 * @dataProvider provideUserContexts
	 * @param array $tokenData
	 * @param string $expected
	 */
	public function testGetUserName( array $tokenData, string $expected ) {
		$this->assertSame(
			$expected, ServiceUserConfiguration::getUserName( $tokenData, 'Default user' )
		);
	}

	public static function provideUserContexts(): array {
		return [
			'preferred key' => [ [ 'user-context' => 'Preferred user' ], 'Preferred user' ],
			'legacy alias' => [ [ 'context-user' => 'Legacy user' ], 'Legacy user' ],
			'preferred key wins' => [
				[ 'user-context' => 'Preferred user', 'context-user' => 'Legacy user' ],
				'Preferred user'
			],
			'empty preferred key' => [
				[ 'user-context' => '', 'context-user' => 'Legacy user' ], 'Legacy user'
			],
			'null preferred key' => [
				[ 'user-context' => null, 'context-user' => 'Legacy user' ], 'Legacy user'
			],
			'no context' => [ [], 'Default user' ],
			'empty contexts' => [
				[ 'user-context' => '', 'context-user' => '' ], 'Default user'
			],
		];
	}

	public function testAddReservedUsernames() {
		$reservedUsernames = [ 'Existing user', 'Default user' ];
		$tokens = [
			[ 'user-context' => 'Preferred user' ],
			[ 'context-user' => 'Legacy user' ],
			[ 'user-context' => 'Preferred user', 'context-user' => 'Unused alias' ],
			[ 'user-context' => '' ],
			[],
		];

		ServiceUserConfiguration::addReservedUsernames( $reservedUsernames, $tokens, 'Default user' );
		ServiceUserConfiguration::addReservedUsernames( $reservedUsernames, $tokens, 'Default user' );

		$this->assertSame(
			[ 'Existing user', 'Default user', 'Preferred user', 'Legacy user' ],
			$reservedUsernames
		);
	}

	public function testAddsDefaultUserWithoutTokens() {
		$reservedUsernames = [];
		ServiceUserConfiguration::addReservedUsernames( $reservedUsernames, [], 'Default user' );
		$this->assertSame( [ 'Default user' ], $reservedUsernames );
	}

	public function testDoesNotReserveEmptyUsernames() {
		$reservedUsernames = [];
		ServiceUserConfiguration::addReservedUsernames( $reservedUsernames, [ [] ], '' );
		$this->assertSame( [], $reservedUsernames );
	}
}
