<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'LandingCheck' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['LandingCheck'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['LandingCheckAlias'] = __DIR__ . '/LandingCheck.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for LandingCheck extension. ' .
		'Please use wfLoadExtension instead, see ' .
		'https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the LandingCheck extension requires MediaWiki 1.25+' );
}
