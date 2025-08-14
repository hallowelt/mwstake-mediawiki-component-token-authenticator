<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator;

use InvalidArgumentException;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use Wikimedia\ObjectCache\BagOStuff;

class UserTokenAuthenticator {
	private const TTL = 10;

	/**
	 * @param BagOStuff $sessionCache
	 * @param UserFactory $userFactory
	 * @param UserGroupManager $groupManager
	 */
	public function __construct(
		private readonly BagOStuff $sessionCache,
		private readonly UserFactory $userFactory,
		private readonly UserGroupManager $groupManager
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
