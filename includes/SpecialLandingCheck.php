<?php

/**
 * This checks to see if a version of a landing page exists for the user's language and country.
 * If not, it looks for a version localized for the user's language. If that doesn't exist either,
 * it looks for the English version. If any of those exist, it then redirects the user.
 */

namespace Mediawiki\Extension\LandingCheck;

use Language;
use SpecialPage;
use Title;
use Wikimedia\IPUtils;

class SpecialLandingCheck extends SpecialPage {
	protected $localServerType = null;
	/**
	 * If basic is set to true, do a local redirect, ignore priority, and don't pass tracking
	 * params. This is for non-fundraising links that just need localization.
	 *
	 * @var bool
	 */
	protected $basic = false;

	/**
	 * If anchor text is passed add that to the end of the created url so that it can be used to
	 * position the resulting page. This is currently used only for non-fundraising links that need
	 * localization and therefore is only checked if basic (above) is true.
	 *
	 * @var string
	 */
	protected $anchor = null;

	public function __construct() {
		// Register special page
		parent::__construct( 'LandingCheck' );
	}

	/**
	 * @param string $sub
	 */
	public function execute( $sub ) {
		global $wgPriorityCountries;
		$request = $this->getRequest();

		// If we have a subpage; assume it's a language like an internationalized page

		$language = 'en';
		$path = explode( '/', $sub );
		if ( Language::isValidCode( $path[count( $path ) - 1] ) ) {
			$language = $sub;
		}

		// Pull in query string parameters
		$language = $request->getVal( 'language', $language );
		$this->basic = $request->getBool( 'basic' );
		$country = $request->getVal( 'country' );
		$this->anchor = $request->getVal( 'anchor' );

		// if the language is false-ish, set to default
		if ( !$language ) {
			$language = 'en';
		}

		// if it's not a supported language, but the section before a
		// dash or underscore is, use that
		if ( !Language::isSupportedLanguage( $language ) ) {
			$parts = preg_split( '/[-_]/', $language );
			if ( Language::isSupportedLanguage( $parts[0] ) ) {
				$language = $parts[0];
			}
		}

		// Use the GeoIP cookie if available.
		if ( !$country ) {
			$geoip = $request->getCookie( 'GeoIP', '' );
			if ( $geoip ) {
				$components = explode( ':', $geoip );
				$country = $components[0];
			}
		}
		// If no country was found yet, try to do GeoIP lookup
		// Requires php5-geoip package
		if ( !$country && function_exists( 'geoip_country_code_by_name' ) ) {
			$ip = $request->getIP();
			if ( IPUtils::isValid( $ip ) ) {
				$country = geoip_country_code_by_name( $ip );
			}
		}
		if ( !$country ) {
			$country = 'US'; // Default
		}

		// determine if we are fulfilling a request for a priority country
		$priority = in_array( $country, $wgPriorityCountries );

		// handle the actual redirect
		$this->routeRedirect( $country, $language, $priority );
	}

	/**
	 * Determine whether this server is configured as the priority or normal server
	 *
	 * If this is neither the priority nor normal server, assumes 'local' - meaning
	 * this server should be handling the request.
	 * @return string
	 */
	public function determineLocalServerType() {
		global $wgServer, $wgLandingCheckPriorityURLBase, $wgLandingCheckNormalURLBase;

		$localServerDetails = wfParseUrl( $wgServer );

		if ( $localServerDetails === false ) {
			return 'local';
		}

		// The following checks are necessary due to a bug in wfParseUrl that was fixed in r94352.
		if ( $wgLandingCheckPriorityURLBase ) {
			$priorityServerDetails = wfParseUrl( $wgLandingCheckPriorityURLBase );
		} else {
			$priorityServerDetails = false;
		}
		if ( $wgLandingCheckNormalURLBase ) {
			$normalServerDetails = wfParseUrl( $wgLandingCheckNormalURLBase );
		} else {
			$normalServerDetails = false;
		}

		if (
			$priorityServerDetails !== false
			&& $localServerDetails[ 'host' ] == $priorityServerDetails[ 'host' ]
		) {
			return 'priority';
		}

		if (
			$normalServerDetails !== false
			&& $localServerDetails[ 'host' ] == $normalServerDetails[ 'host' ]
		) {
			return 'normal';
		}

		return 'local';
	}

	/**
	 * Route the request to the appropriate redirect method
	 * @param string $country
	 * @param string $language
	 * @param bool $priority Whether or not we handle this request on behalf of a priority country
	 */
	public function routeRedirect( $country, $language, $priority ) {
		$localServerType = $this->getLocalServerType();

		if ( $this->basic ) {
			$this->localRedirect( $country, $language, false );

		} elseif ( $localServerType == 'local' ) {
			$this->localRedirect( $country, $language, $priority );

		} elseif ( $priority && $localServerType == 'priority' ) {
			$this->localRedirect( $country, $language, $priority );

		} elseif ( !$priority && $localServerType == 'normal' ) {
			$this->localRedirect( $country, $language, $priority );

		} else {
			$this->externalRedirect( $priority );
		}
	}

	/**
	 * Handle an external redirect
	 *
	 * The external redirect should point to another instance of LandingCheck
	 * which will ultimately handle the request.
	 * @param bool $priority
	 */
	public function externalRedirect( $priority ) {
		global $wgLandingCheckPriorityURLBase, $wgLandingCheckNormalURLBase;

		if ( $priority ) {
			$urlBase = $wgLandingCheckPriorityURLBase;

		} else {
			$urlBase = $wgLandingCheckNormalURLBase;
		}

		$query = $this->getRequest()->getValues();
		unset( $query[ 'title' ] );

		$url = wfAppendQuery( $urlBase, $query );
		$this->getOutput()->redirect( $url );
	}

	/**
	 * Handle local redirect
	 * @param string $country
	 * @param string $language
	 * @param bool $priority Whether or not we handle this request on behalf of a priority country
	 */
	public function localRedirect( $country, $language, $priority = false ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$landingPage = $request->getVal( 'landing_page', 'Donate' );

		/**
		 * Construct new query string for tracking
		 *
		 * Note that both 'language' and 'uselang' get set to
		 * 	$request->getVal( 'language', 'en' )
		 * This is wacky, yet by design! This is a unique oddity to fundraising
		 * stuff, but CentralNotice converts wgUserLanguage to 'language' rather than
		 * 'uselang'. Ultimately, this is something that should probably be rectified
		 * in CentralNotice. Until then, this is what we've got.
		 */
		$tracking = wfArrayToCgi( [
			'utm_source' => $request->getVal( 'utm_source' ),
			'utm_medium' => $request->getVal( 'utm_medium' ),
			'utm_campaign' => $request->getVal( 'utm_campaign' ),
			'utm_key' => $request->getVal( 'utm_key' ),
			'language' => $language,
			'uselang' => $language, // for {{int:xxx}} rendering
			'country' => $country,
			'referrer' => $request->getHeader( 'referer' )
		] );

		if ( $priority ) {
			// Build array of landing pages to check for
			$targetTexts = [
				$landingPage . '/' . $country . '/' . $language,
				$landingPage . '/' . $country,
				$landingPage . '/' . $language
			];
		} else {
			// Build array of landing pages to check for
			$targetTexts = [
				$landingPage . '/' . $language . '/' . $country,
				$landingPage . '/' . $language
			];
			// Add fallback languages
			$fallbacks = Language::getFallbacksFor( $language );
			foreach ( $fallbacks as $fallback ) {
				$targetTexts[] = $landingPage . '/' . $fallback;
			}
		}

		// Go through the possible landing pages and redirect the user as soon as one is found to exist
		foreach ( $targetTexts as $targetText ) {
			$target = Title::newFromText( $targetText );
			if ( $target && $target->isKnown() && $target->getNamespace() == NS_MAIN ) {
				if ( $this->basic ) {
					if ( isset( $this->anchor ) ) {
						$out->redirect( $target->getLocalURL() . '#' . $this->anchor );
					} else {
						$out->redirect( $target->getLocalURL() );
					}
				} else {
					$out->redirect( $target->getLocalURL( $tracking ) );
				}
				return;
			}
		}

		// Output a simple error message if no pages were found
		$this->setHeaders();
		$this->outputHeader();
		$out->addWikiMsg( 'landingcheck-nopage' );
	}

	/**
	 * Setter for $this->localServerType
	 * @param string|null $type
	 */
	public function setLocalServerType( $type = null ) {
		if ( !$type ) {
			$this->localServerType = $this->determineLocalServerType();
		} else {
			$this->localServerType = $type;
		}
	}

	/**
	 * Getter for $this->localServerType
	 * @return string
	 */
	public function getLocalServerType() {
		if ( !$this->localServerType ) {
			$this->setLocalServerType();
		}
		return $this->localServerType;
	}

	protected function getGroupName() {
		return 'contribution';
	}
}
