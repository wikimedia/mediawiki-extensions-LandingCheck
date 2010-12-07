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
		global $wgOut, $wgRequest, $wgPriorityCountries;
		
		// Pull in query string parameters
		$language = $wgRequest->getVal( 'language', 'en' );
		
		$country = $wgRequest->getVal( 'country' );
		// If no country was passed, try to do GeoIP lookup
		// Requires php5-geoip package
		if ( !$country && function_exists( geoip_country_code_by_name ) ) {
			$ip = wfGetIP();
			if ( IP::isValid( $ip ) ) {
				$country = geoip_country_code_by_name( $ip );
			}
		}
		if ( !$country ) {
			$country = 'US'; // Default
		}
		
		$landingPage = $wgRequest->getVal( 'landing_page', 'Donate' );
		
		// Construct new query string for tracking
		$tracking = wfArrayToCGI( array( 
			'utm_source' => $wgRequest->getVal( 'utm_source' ),
			'utm_medium' => $wgRequest->getVal( 'utm_medium' ),
			'utm_campaign' => $wgRequest->getVal( 'utm_campaign' ),
			'referrer' => $wgRequest->getHeader( 'referer' )
		) );
		
		if ( in_array( $country, $wgPriorityCountries ) ) {
			// Build array of landing pages to check for
			$targetTexts = array(
				$landingPage . '/' . $country . '/' . $language,
				$landingPage . '/' . $country
			);
		} else {
			// Build array of landing pages to check for
			$targetTexts = array(
				$landingPage . '/' . $language . '/' . $country,
				$landingPage . '/' . $language
			);
			// Add fallback languages
			$code = $language;
			while ( $code !== 'en' ) {
				$code = Language::getFallbackFor( $code );
				$targetTexts[] = $landingPage . '/' . $code;
			}
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
