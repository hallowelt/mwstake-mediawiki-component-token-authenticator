<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator;

use Wikimedia\IPUtils;

class CIDRValidator {

	/**
	 * @param string $ip
	 * @param string $cidr
	 * @return bool
	 */
	public function validateIP( string $ip, string $cidr ): bool {
		if ( !$cidr ) {
			return true;
		}
		if ( $cidr && !IPUtils::isValidRange( $cidr ) ) {
			throw new \InvalidArgumentException( 'Invalid CIDR range provided' );
		}
		return IPUtils::isInRange( $ip, $cidr );
	}
}
