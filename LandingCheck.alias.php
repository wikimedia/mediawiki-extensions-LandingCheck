<?php
/**
 * Aliases for Special:LandingCheck
 *
 * @file
 * @ingroup Extensions
 */

$specialPageAliases = array();

/** English (English) */
$specialPageAliases['en'] = array(
	'LandingCheck' => array( 'LandingCheck' ),
);

/** Arabic (العربية) */
$specialPageAliases['ar'] = array(
	'LandingCheck' => array( 'تحقق_الهدف' ),
);

/** Haitian (Kreyòl ayisyen) */
$specialPageAliases['ht'] = array(
	'LandingCheck' => array( 'VerifikasyonAteri' ),
);

/** Macedonian (Македонски) */
$specialPageAliases['mk'] = array(
	'LandingCheck' => array( 'ПроверкаНаОдредница' ),
);

/** Dutch (Nederlands) */
$specialPageAliases['nl'] = array(
	'LandingCheck' => array( 'Landingspaginacontrole' ),
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬) */
$specialPageAliases['no'] = array(
	'LandingCheck' => array( 'Landingssjekk' ),
);

/**
 * For backwards compatibility with MediaWiki 1.15 and earlier.
 */
$aliases =& $specialPageAliases;