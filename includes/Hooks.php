<?php

namespace MediaWiki\Extension\LandingCheck;

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

use MediaWiki\Languages\Hook\Language__getMessagesFileNameHook;

class Hooks implements Language__getMessagesFileNameHook {

	/**
	 * Register es-419 as a language supported by this extension but not by
	 * MediaWiki core. Handles Language::onGetMessagesFileName hook called in
	 * LanguageNameUtils::getMessagesFileName
	 *
	 * @param string $code language code
	 * @param string &$file path of Messages file as found by MediaWiki core
	 */
	public function onLanguage__getMessagesFileName( $code, &$file ) {
		if ( $code === 'es-419' ) {
			$file = dirname( __DIR__ ) . '/messages/MessagesEs_419.php';
		}
	}
}
