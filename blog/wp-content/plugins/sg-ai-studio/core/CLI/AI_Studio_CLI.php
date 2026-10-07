<?php
/**
 * WP-CLI commands for SG AI Studio
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\CLI;

use SG_AI_Studio\Helper\Helper;
use CharlesRumley\PoToJson;
/**
 * WP-CLI commands for SG AI Studio plugin
 */
class AI_Studio_CLI {

	/**
	 * Initialize client authentication with encoded data
	 *
	 * ## OPTIONS
	 *
	 * <hash>
	 * : The base64 encoded data containing client_id:link_id
	 *
	 * ## EXAMPLES
	 *
	 *     wp sg ai-studio auth <base64_encoded_data>
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function auth( $args, $assoc_args ) {
		// Check if hash argument is provided.
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Hash parameter is required.' );
			return;
		}

		$encoded_data = $args[0];

		// Base64 decode the data.
		$decoded_data = base64_decode( $encoded_data );

		if ( false === $decoded_data ) {
			\WP_CLI::error( 'Invalid base64 encoded data.' );
			return;
		}

		// Split by colon to get client_id and link_id.
		$parts = explode( ':', $decoded_data );

		if ( count( $parts ) !== 2 ) {
			\WP_CLI::error( 'Data must contain exactly one colon separator.' );
			return;
		}

		$client_id = trim( $parts[0] );
		$link_id   = trim( $parts[1] );

		if ( empty( $client_id ) || empty( $link_id ) ) {
			\WP_CLI::error( 'Both client_id and link_id must be non-empty.' );
			return;
		}

		// Save client_id and link_id as site options.
		$client_id_saved = update_option( 'sg_ai_studio_client_id', $client_id );
		$link_id_saved   = update_option( 'sg_ai_studio_link_id', $link_id );

		// Save the connected URL as base64 encoded value.
		$site_url         = get_site_url();
		$encoded_site_url = base64_encode( $site_url );
		update_option( 'sg_ai_studio_connected_url', $encoded_site_url );

		// Use Helper class to make the API call.
		$api_result = Helper::init_client_auth( $client_id, $link_id );
		// Check if API call failed.
		if ( ! $api_result['success'] ) {
			// API call failed - handled below.
		} else {
			update_option( 'sg_ai_studio_connected', true );
		}
		// Check if API call failed.
		if ( ! $api_result['success'] ) {
			\WP_CLI::error(
				sprintf(
					'API call failed: %s',
					$api_result['message'] . ( isset( $api_result['error'] ) ? ' - ' . $api_result['error'] : '' )
				)
			);
			return;
		}

		\WP_CLI::success(
			sprintf(
				'Authentication initialized successfully. Client ID: %s, Link ID: %s',
				$client_id,
				$link_id
			)
		);
	}

	/**
	 * Clean up all plugin data from the database
	 *
	 * ## OPTIONS
	 *
	 * [--disconnect]
	 * : Disconnect from AI Studio Backend API before cleanup
	 *
	 * ## EXAMPLES
	 *
	 *     wp sg ai-studio cleanup
	 *     wp sg ai-studio cleanup --disconnect
	 *
	 * @param array $args Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		\WP_CLI::log( 'Cleaning up SG AI Studio data...' );

		// Check if --disconnect flag is present.
		if ( isset( $assoc_args['disconnect'] ) ) {
			\WP_CLI::log( 'Disconnecting from AI Studio Backend API...' );

			$auth_token = Helper::generate_ai_studio_token();

			// Get the current site URL.
			$site_url = get_site_url();

			if ( Helper::is_staging_environment() ) {
				$api_url = 'https://api.staging.studio.siteground.ai/api/v1/wp/wp-disconnect';
			} else {
				$api_url = 'https://api.studio.siteground.ai/api/v1/wp/wp-disconnect';
			}

			$api_response = wp_remote_post(
				$api_url,
				array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $auth_token,
					),
					'body'    => wp_json_encode(
						array(
							'wp_url' => $site_url,
						)
					),
					'timeout' => 30,
				)
			);

			// Check if API call failed.
			if ( is_wp_error( $api_response ) ) {
				\WP_CLI::warning( 'Failed to connect to AI Studio Backend API.' );
			}
		}

		$result = Helper::cleanup_plugin_data();

		// Report results.
		if ( $result['success'] ) {
			\WP_CLI::success( 'Successfully cleaned up all SG AI Studio data from the database.' );
		} else {
			\WP_CLI::warning( 'Cleanup completed with some errors:' );
			foreach ( $result['errors'] as $error ) {
				\WP_CLI::log( '  ✗ ' . $error );
			}
		}
	}

	/**
	 * Generate JSONs for translation
	 *
	 * ## EXAMPLES
	 *
	 *     wp sg ai-studio translate
	 *
	 * @param array $args Command arguments.
	 */
	public function translate( $args ) {
		global $wp_filesystem;

		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		// Init the convertor class.
		$po_to_json = new PoToJson();

		$languages = array(
			'de_DE',
			'es_ES',
			'fr_FR',
			'it_IT',
		);

		foreach ( $languages as $key ) {
			// Convert a PO file to Jed-compatible JSON.
			$json = $po_to_json
				->withPoFile( \SG_AI_Studio\DIR . '/languages/sg-ai-studio-' . $key . '.po' )
				->toJedJson( false, 'sg-ai-studio' );

			// Convert and get the json content.
			$content = json_decode( $json, true );

			// Build the json filepath.
			$json_filepath = \SG_AI_Studio\DIR . '/languages/sg-ai-studio-' . $key . '.json';

			// Create the file if donesn't exists.
			if ( ! is_file( $json_filepath ) ) {
				// Create the new file.
				$wp_filesystem->touch( $json_filepath );
			}

			// Add the translations to the file.
			$wp_filesystem->put_contents(
				$json_filepath,
				json_encode( $content['locale_data'][ 'sg-ai-studio' ] )
			);
		}
	}
}
