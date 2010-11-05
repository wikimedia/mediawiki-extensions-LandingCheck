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
	'version' => '1.2',
	'url' => 'http://www.mediawiki.org/wiki/Extension:LandingCheck',
	'author' => 'Ryan Kaldari',
	'descriptionmsg' => 'landingcheck-desc',
);

// If there are any countries for which the country page should be the fallback rather than a
// language page, add its country code to this array.
$priorityCountries = array();

$dir = dirname( __FILE__ ) . '/';

$wgAutoloadClasses['SpecialLandingCheck'] = $dir . 'SpecialLandingCheck.php';
$wgExtensionMessagesFiles['LandingCheck'] = $dir . 'LandingCheck.i18n.php';
$wgExtensionAliasesFiles['LandingCheck'] = $dir . 'LandingCheck.alias.php';
$wgSpecialPages['LandingCheck'] = 'SpecialLandingCheck';
$wgSpecialPageGroups['LandingCheck'] = 'contribution';
