<?php
namespace SiteGround_Central\Importer;

/**
 * Ocean WP theme functions and main initialization class.
 */
class Theme_Importer_Astra extends Importer {

	/**
	 * Import sample data to WordPress.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $json Json data.
	 *
	 * @return bool True on error, false on success.
	 */
	public function import_json( $json ) {
		global $wpdb;
		$maybe_json = self::maybe_json_decode( $json );

		// Ensure the importer classes are available.
		if (
			! class_exists( '\Astra_Site_Options_Import' ) ||
			! class_exists( '\Astra_Customizer_Import' ) ||
			! class_exists( '\Astra_Sites_Importer_Log' ) ||
			! class_exists( '\Astra_Sites_Page' )
		) {
			// Files required for the import.
			$required_files = array(
				WP_PLUGIN_DIR . '/astra-sites/astra-sites.php',
				WP_PLUGIN_DIR . '/astra-sites/inc/classes/class-astra-sites-page.php',
				WP_PLUGIN_DIR . '/astra-sites/inc/classes/class-astra-sites-importer-log.php',
				WP_PLUGIN_DIR . '/astra-sites/inc/importers/class-astra-site-options-import.php',
				WP_PLUGIN_DIR . '/astra-sites/inc/importers/class-astra-customizer-import.php',
			);

			foreach ( $required_files as $file ) {
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		// Bail if provided json is invalid.
		if (
			false === $maybe_json ||
			! class_exists( '\Astra_Site_Options_Import' ) ||
			! class_exists( '\Astra_Customizer_Import' ) ||
			! class_exists( '\Astra_Sites_Importer_Log' ) ||
			! class_exists( '\Astra_Sites_Page' )
		) {
			return true;
		}

		if ( ! empty( $maybe_json['astra-site-customizer-data'] ) ) {
			\Astra_Customizer_Import::instance()->import( $maybe_json['astra-site-customizer-data'] );
		}

		if ( ! empty( $maybe_json['astra-site-options-data'] ) ) {
			do_action( 'st_importer_import_site_options', $maybe_json['astra-site-options-data'] );
		}

		return false;
	}

	/**
	 * XML importer.
	 *
	 * @since  1.0.0
	 *
	 * @param string $url The xml url.
	 */
	public function import_xml( $url ) {
		exec( 'wp plugin deactivate astra-sites' );

		exec(
			sprintf(
				'wp import %s --authors=skip',
				escapeshellarg( $url )
			),
			$output,
			$status
		);

		exec( 'wp plugin activate astra-sites' );

		// Check for errors during the import.
		if ( ! empty( $status ) ) {
			return true;
		}

		return false;
	}
}
