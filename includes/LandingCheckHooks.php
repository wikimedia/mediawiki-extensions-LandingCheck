<?php

class LandingCheckHooks {

	/**
	 * Register es-419 as a language supported by this extension but not by
	 * MediaWiki core. Handles Language::onGetMessagesFileName hook called in
	 * LanguageNameUtils::getMessagesFileName
	 *
	 * @param string $code language code
	 * @param string &$file path of Messages file as found by MediaWiki core
	 */
	public static function onGetMessagesFileName( $code, &$file ) {
		if ( $code === 'es-419' ) {
			$file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
				'messages' . DIRECTORY_SEPARATOR . 'MessagesEs_419.php';
		}
	}
}
