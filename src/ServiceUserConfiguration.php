<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator;

class ServiceUserConfiguration {

	/**
	 * @param array $tokenData
	 * @param string $defaultUserName
	 * @return string
	 */
	public static function getUserName( array $tokenData, string $defaultUserName ): string {
		if ( !empty( $tokenData['user-context'] ) ) {
			return $tokenData['user-context'];
		}
		if ( !empty( $tokenData['context-user'] ) ) {
			return $tokenData['context-user'];
		}
		return $defaultUserName;
	}

	/**
	 * @param string[] &$reservedUsernames
	 * @param array $tokens
	 * @param string $defaultUserName
	 */
	public static function addReservedUsernames(
		array &$reservedUsernames, array $tokens, string $defaultUserName
	): void {
		$userNames = [ $defaultUserName ];
		foreach ( $tokens as $tokenData ) {
			$userNames[] = self::getUserName( $tokenData, $defaultUserName );
		}
		foreach ( $userNames as $userName ) {
			if ( $userName !== '' && !in_array( $userName, $reservedUsernames, true ) ) {
				$reservedUsernames[] = $userName;
			}
		}
	}
}
