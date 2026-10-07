<?php
namespace SiteGround_Central\Importer;

/**
 * Pathway theme functions and main initialization class.
 */
class Theme_Importer_Pathway extends Importer {

	/**
	 * Pathway XML importer.
	 *
	 * @since  3.0.1
	 *
	 * @param string $url The xml url.
	 */
	public function import_xml( $url ) {
		// Activate the plugin before we import the file, so proper id's are set.
		exec( 'wp plugin activate kubio' );

		exec(
			sprintf(
				'wp import %s --authors=skip',
				escapeshellarg( $url )
			),
			$output,
			$status
		);

		// Check for errors during the import.
		if ( ! empty( $status ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Import sample data to WordPress.
	 *
	 * @since  3.0.1
	 *
	 * @param  object $json Json data.
	 *
	 * @return bool True on error, false on success.
	 */
	public function import_json( $json ) {
		$maybe_json = self::maybe_json_decode( $json );

		// Bail if provided json is invalid.
		if ( false === $maybe_json ) {
			return true;
		}

		// Loop through mods and add them.
		foreach ( $maybe_json as $mod => $value ) {
			set_theme_mod( $mod, $value );
		}

		return false;
	}
}
