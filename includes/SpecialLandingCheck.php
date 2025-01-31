<?php

/**
 * This checks to see if a version of a landing page exists for the user's language and country.
 * If not, it looks for a version localized for the user's language. If that doesn't exist either,
 * it looks for the English version. If any of those exist, it then redirects the user.
 */

namespace MediaWiki\Extension\LandingCheck;

use MediaWiki\Languages\LanguageFallback;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MainConfigNames;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\Utils\UrlUtils;

class SpecialLandingCheck extends SpecialPage {
	/** @var LanguageNameUtils */
	private $languageNameUtils;

	/** @var LanguageFallback */
	private $languageFallback;

	/** @var UrlUtils */
	private $urlUtils;

	/** @var string|null */
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
	 * @var string|null
	 */
	protected $anchor = null;

	/**
	 * @param LanguageNameUtils $languageNameUtils
	 * @param LanguageFallback $languageFallback
	 * @param UrlUtils $urlUtils
	 */
	public function __construct(
		LanguageNameUtils $languageNameUtils,
		LanguageFallback $languageFallback,
		UrlUtils $urlUtils
	) {
		// Register special page
		parent::__construct( 'LandingCheck' );
		$this->languageNameUtils = $languageNameUtils;
		$this->languageFallback = $languageFallback;
		$this->urlUtils = $urlUtils;
	}

	/**
	 * @param string|null $sub
	 */
	public function execute( $sub ) {
		$request = $this->getRequest();

		// If we have a subpage; assume it's a language like an internationalized page

		$language = 'en';
		$path = explode( '/', $sub ?? '' );
		if ( $this->languageNameUtils->isValidCode( $path[count( $path ) - 1] ) ) {
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
		if ( !$this->languageNameUtils->isSupportedLanguage( $language ) ) {
			$parts = preg_split( '/[-_]/', $language );
			if ( $this->languageNameUtils->isSupportedLanguage( $parts[0] ) ) {
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

		if ( !$country ) {
			// Default
			$country = 'US';
		}

		// determine if we are fulfilling a request for a priority country
		$priority = in_array( $country, $this->getConfig()->get( 'PriorityCountries' ) );

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
		$config = $this->getConfig();
		$localServerDetails = $this->urlUtils->parse( $config->get( MainConfigNames::Server ) );

		if ( $localServerDetails === null ) {
			return 'local';
		}

		$landingCheckPriorityURLBase = $config->get( 'LandingCheckPriorityURLBase' );
		if ( $landingCheckPriorityURLBase !== null ) {
			$priorityServerDetails = $this->urlUtils->parse( $landingCheckPriorityURLBase );
			if ( $priorityServerDetails !== null
				&& $localServerDetails[ 'host' ] === $priorityServerDetails[ 'host' ]
			) {
				return 'priority';
			}
		}

		$landingCheckNormalURLBase = $config->get( 'LandingCheckNormalURLBase' );
		if ( $landingCheckNormalURLBase !== null ) {
			$normalServerDetails = $this->urlUtils->parse( $landingCheckNormalURLBase );
			if ( $normalServerDetails !== null
				&& $localServerDetails[ 'host' ] === $normalServerDetails[ 'host' ]
			) {
				return 'normal';
			}
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
		if ( $priority ) {
			$urlBase = $this->getConfig()->get( 'LandingCheckPriorityURLBase' );

		} else {
			$urlBase = $this->getConfig()->get( 'LandingCheckNormalURLBase' );
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
			// for {{int:xxx}} rendering
			'uselang' => $language,
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
			$fallbacks = $this->languageFallback->getAll( $language );
			foreach ( $fallbacks as $fallback ) {
				$targetTexts[] = $landingPage . '/' . $fallback;
			}
		}

		// Go through the possible landing pages and redirect the user as soon as one is found to exist
		foreach ( $targetTexts as $targetText ) {
			$target = Title::newFromText( $targetText );
			if ( $target && $target->isKnown() && $target->getNamespace() == NS_MAIN ) {
				if ( $this->basic ) {
					if ( $this->anchor !== null ) {
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

	/** @inheritDoc */
	protected function getGroupName() {
		return 'contribution';
	}
}
