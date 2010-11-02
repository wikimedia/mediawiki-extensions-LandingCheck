<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "LandingCheck extension\n";
	exit( 1 );
}

/**
 * This page checks to see if a version of a landing page exists for the user's language and 
 * country. If not, it looks for a version localized for the user's language. If that doesn't exist 
 * either, it looks for the English version. If any of those exist, it redirects the user.
 */
class SpecialLandingCheck extends SpecialPage {
	
	public function __construct() {
		// Register special page
		parent::__construct( 'LandingCheck' );
	}
	
	public function execute( $sub ) {
		global $wgOut, $wgUser, $wgRequest, $wgScript;
		
		$language = $wgRequest->getText( 'language', 'en' );
		$country = $wgRequest->getText( 'country' );
		$landingPage = $wgRequest->getText( 'landing_page' );
		
		$tracking = wfArrayToCGI( array( 
			'utm_source' => $wgRequest->getVal( 'utm_source' ),
			'utm_medium' => $wgRequest->getVal( 'utm_medium' ),
			'utm_campaign' => $wgRequest->getVal( 'utm_campaign' ),
			'referrer' => $wgRequest->getHeader( 'referer' )
		) );
		
		if ( $landingPage ) {
			if ( strpos( $landingPage, 'Special:' ) === false ) { // landing page is not a special page
				$target = Title::newFromText( $landingPage . '/' . $language . '/' . $country );
				if( $target->isKnown() ) {
					$wgOut->redirect( $target->getLocalURL( $tracking ) );
				} else {
					$target = Title::newFromText( $landingPage . '/' . $language );
					if( $target->isKnown() ) {
						$wgOut->redirect( $target->getLocalURL( $tracking ) );
					} elseif ( $language != 'en' ) {
						$target = Title::newFromText( $landingPage . '/en' );
						if( $target->isKnown() ) {
							$wgOut->redirect( $target->getLocalURL( $tracking ) );
						}
					}
				}
			}
		}
		
	}
}
