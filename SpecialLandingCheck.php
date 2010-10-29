<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "LandingCheck extension\n";
	exit( 1 );
}

class SpecialLandingCheck extends SpecialPage {
	
	function __construct() {
		// Register special page
		parent::__construct( 'LandingCheck' );
	}
	
	function execute( $sub ) {
		global $wgOut, $wgUser, $wgRequest, $wgLandingPageBase;
		
		if ( $wgRequest->getVal( 'language' ) ) {
			$language = ( preg_match( '/^[A-Za-z-]+$/', $wgRequest->getVal( 'language' ) ) );
		} else {
			$language = 'en';
		}
		$country = $wgRequest->getVal( 'country' );
		$landingPage = $wgRequest->getVal( 'landing_page' );
		
		$tracking = '?' . wfArrayToCGI( array( 
			'utm_source' => $wgRequest->getVal( 'utm_source' ),
			'utm_medium' => $wgRequest->getVal( 'utm_medium' ),
			'utm_campaign' => $wgRequest->getVal( 'utm_campaign' ),
			'referrer' => $wgRequest->getHeader( 'referer' )
		) );
		
		if ( 1 ) {
			$wgOut->redirect( $wgLandingPageBase . '/' . $language . '/' . $country . $tracking );
		}
	}
}
