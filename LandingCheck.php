<?php

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/LandingCheck/LandingCheck.php" );
EOT;
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'LandingCheck',
	'version' => '2.1.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:LandingCheck',
	'author' => array( 'Ryan Kaldari', 'Arthur Richards' ),
	'descriptionmsg' => 'landingcheck-desc',
);

$dir = __DIR__ . '/';

$wgAutoloadClasses['SpecialLandingCheck'] = $dir . 'SpecialLandingCheck.php';
$wgMessagesDirs['LandingCheck'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['LandingCheck'] = $dir . 'LandingCheck.i18n.php';
$wgExtensionMessagesFiles['LandingCheckAlias'] = $dir . 'LandingCheck.alias.php';
$wgSpecialPages['LandingCheck'] = 'SpecialLandingCheck';
$wgSpecialPageGroups['LandingCheck'] = 'contribution';

// If there are any countries for which the country page should be the fallback rather than a
// language page, add its country code to this array.
$wgPriorityCountries = array();

/**
 * It is possible to configure a separate server running LandingCheck to handle
 * requests for priority countries and another for non-priority countries. By
 * default, the local instance of LandingCheck will handle both unless the following
 * variables are configured.
 *
 * The URLs contained in these variables should be the full URL to the location
 * of LandingCheck - the query string will be appended. For example:
 *   $wgLandingCheckPriorityURLBase = '//wikimediafoundation.org/wiki/Special:LandingCheck';
 *
 * LandingCheck will compare the host portion of these URLs to what is
 * configured for $wgServer. If the hosts match, the LandingCheck will just
 * handle the request locally. If these are not set, LandingCheck will default to
 * handling the request locally.
 */
$wgLandingCheckPriorityURLBase = null;
$wgLandingCheckNormalURLBase = null;
