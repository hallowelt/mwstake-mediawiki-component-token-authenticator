<?php

namespace MWStake\MediaWiki\Component\TokenAuthenticator;

use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;

class GenerateTokenHandler extends SimpleHandler {

	/**
	 * @param UserTokenAuthenticator $userTokenAuthenticator
	 */
	public function __construct(
		private readonly UserTokenAuthenticator $userTokenAuthenticator
	) {
	}

	/**
	 * @return \MediaWiki\Rest\Response|mixed
	 * @throws HttpException
	 */
	public function execute() {
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isRegistered() ) {
			throw new HttpException( 'User must be registered to generate a token.', 403 );
		}
		return $this->userTokenAuthenticator->generateToken( $user );
	}

	public function needsReadAccess() {
		return true;
	}
}
