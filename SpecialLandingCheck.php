<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "LandingCheck extension\n";
	exit( 1 );
}

class LandingCheck extends SpecialPage {
	
	function __construct() {
		// Register special page
		parent::__construct( 'LandingCheck' );
	}
}
	
	
//$wgOut->redirect( $this->getTitle( 'view' )->getLocalUrl( "template=$template" ) );

?>