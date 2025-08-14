<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator;

use InvalidArgumentException;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\Utils\UrlUtils;
use MediaWiki\WikiMap\WikiMap;
use Wikimedia\ObjectCache\BagOStuff;

class UserTokenAuthenticator {
	private const TTL = 10;

	/**
	 * @param UrlUtils $urlUtils
	 * @param BagOStuff $sessionCache
	 * @param UserFactory $userFactory
	 * @param UserGroupManager $groupManager
	 * @param string $salt
	 */
	public function __construct(
		private readonly UrlUtils $urlUtils,
		private readonly BagOStuff $sessionCache,
		private readonly UserFactory $userFactory,
		private readonly UserGroupManager $groupManager,
		private readonly string $salt = ''
	) {
	}

	/**
	 * @param UserIdentity $user
	 * @return string
	 * @throws \Random\RandomException
	 */
	public function generateToken( UserIdentity $user ) {
		if ( !$user->isRegistered() ) {
			throw new InvalidArgumentException( 'User must be registered to generate a token.' );
		}
		$token = bin2hex( random_bytes( 16 ) );
		if ( $this->sessionCache->set(
			$this->sessionCache->makeKey( $token ),
			[ 'user' => $user->getName(), 'wiki' => WikiMap::getCurrentWikiId() ],
			static::TTL
		) ) {
			return $token;
		} else {
			throw new InvalidArgumentException( 'Failed to store user token in cache.' );
		}
	}

	/**
	 * Generates a token and bakes in the issues, to be used for verification.
	 * Salt must be set for this method to work
	 *
	 * @param UserIdentity $user
	 * @return string
	 * @throws \Random\RandomException
	 */
	public function generateTokenWithIssuer( UserIdentity $user ) {
		if ( !$this->salt ) {
			throw new InvalidArgumentException( 'Salt must be set to generate a token with issuer.' );
		}
		$token = $this->generateToken( $user );
		$callbackUrl = $this->urlUtils->expand( wfScript( 'rest' ) );
		$signature = hash_hmac( 'sha256', "$callbackUrl$token", $this->salt );
		return base64_encode( json_encode( [
			'verifyCallback' => $callbackUrl,
			'token' => $token,
			'sig' => $signature,
		] ) );
	}

	/**
	 * @param string $token
	 * @return AuthInfo|null
	 */
	public function getAuthInfo( string $token ): ?AuthInfo {
		$key = $this->sessionCache->makeKey( $token );
		$value = $this->sessionCache->get( $key );
		if ( !$value ) {
			return null;
		}
		$user = $this->userFactory->newFromName( $value['user'] );
		if ( !$user || !$user->isRegistered() ) {
			return null;
		}
		return new AuthInfo(
			$user,
			$value['wiki'],
			$this->groupManager->getUserEffectiveGroups( $user )
		);
	}
}
