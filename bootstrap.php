<?php

if ( defined( 'MWSTAKE_MEDIAWIKI_COMPONENT_TOKEN_AUTHENTICATOR_VERSION' ) ) {
	return;
}

define( 'MWSTAKE_MEDIAWIKI_COMPONENT_TOKEN_AUTHENTICATOR_VERSION', '1.0.0' );

MWStake\MediaWiki\ComponentLoader\Bootstrapper::getInstance()
->register( 'token-authenticator', static function () {
	$GLOBALS['wgServiceWiringFiles'][] = __DIR__ . '/ServiceWiring.php';

	$restFilePath = wfRelativePath( __DIR__ . '/rest-routes.json', $GLOBALS['IP'] );
	$GLOBALS['wgRestAPIAdditionalRouteFiles'][] = $restFilePath;

	$GLOBALS['wgResourceModules']['mwstake.component.tokenAuthenticator'] = [
		'scripts' => [
			'resources/bootstrap.js'
		],
		'localBasePath' => __DIR__
	];
} );
