<?php
namespace SiteGround_Central\Cli;


use SiteGround_Helper\Helper_Service;
use CharlesRumley\PoToJson;

/**
 * WP-CLI: sg-starter generate-translations.
 *
 * Run the `wp sg-starter generate-translations` command to generate JSON files for Front-End multilingual support.
 *
 * @since 3.0.0
 * @package Cli
 * @subpackage Cli/Cli_Translations
 */

/**
 * Define the {@link Cli_Translations} class.
 *
 * @since 3.0.0
 */
class Cli_Translations {

	/**
	 * Generate JSON files for Front-End I18n support.
	 *
	 * @since 3.0.0
	 */
	public function __invoke( $args ) {
		// Setup the WP Filesystem.
		$wp_filesystem = Helper_Service::setup_wp_filesystem();

		// Init the convertor class.
		$po_to_json = new \CharlesRumley\PoToJson();

		$languages = array(
			'de_DE',
			'es_ES',
			'fr_FR',
			'it_IT',
		);

		foreach ( $languages as $key ) {
			// Convert a PO file to Jed-compatible JSON.
			$json = $po_to_json
				->withPoFile( \SiteGround_Central\DIR . '/languages/siteground-wizard-' . $key . '.po' )
				->toJedJson( false, 'siteground-wizard' );

			// Convert and get the json content.
			$content = json_decode( $json, true );

			// Build the json filepath.
			$json_filepath = \SiteGround_Central\DIR . '/languages/siteground-wizard-' . $key . '.json';

			// Create the file if donesn't exists.
			if ( ! is_file( $json_filepath ) ) {
				// Create the new file.
				$wp_filesystem->touch( $json_filepath );
			}

			// Add the translations to the file.
			$wp_filesystem->put_contents(
				$json_filepath,
				json_encode( $content['locale_data'][ 'siteground-wizard' ] )
			);
		}
	}

}
