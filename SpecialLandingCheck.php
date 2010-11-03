<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "LandingCheck extension\n";
	exit( 1 );
}

/**
 * This checks to see if a version of a landing page exists for the user's language and country. 
 * If not, it looks for a version localized for the user's language. If that doesn't exist either, 
 * it looks for the English version. If any of those exist, it then redirects the user.
 */
class SpecialLandingCheck extends SpecialPage {
	
	public function __construct() {
		// Register special page
		parent::__construct( 'LandingCheck' );
	}
	
	public function execute( $sub ) {
		global $wgOut, $wgRequest;
		
		// Pull in query string parameters
		$language = $wgRequest->getVal( 'language', 'en' );
		$country = $wgRequest->getVal( 'country', 'US' );
		$landingPage = $wgRequest->getVal( 'landing_page', 'Donate' );
		
		// Construct new query string for tracking
		$tracking = wfArrayToCGI( array( 
			'utm_source' => $wgRequest->getVal( 'utm_source' ),
			'utm_medium' => $wgRequest->getVal( 'utm_medium' ),
			'utm_campaign' => $wgRequest->getVal( 'utm_campaign' ),
			'referrer' => $wgRequest->getHeader( 'referer' )
		) );
		
		// Build array of landing pages to check for
		$targetTexts = array(
			$landingPage . '/' . $language . '/' . $country,
			$landingPage . '/' . $language
		);
		if ( $language != 'en' ) {
			$targetTexts[] = $landingPage . '/en'; // English fallback
		}
		
		// Go through the possible landing pages and redirect the user as soon as one is found to exist
		foreach ( $targetTexts as $targetText ) {
			$target = Title::newFromText( $targetText );
			if ( $target && $target->isKnown() && $target->getNamespace() == NS_MAIN ) {
				$wgOut->redirect( $target->getLocalURL( $tracking ) );
				return;
			} 
		}
		
	}
}
