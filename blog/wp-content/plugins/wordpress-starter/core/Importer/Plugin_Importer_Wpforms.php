<?php
namespace SiteGround_Central\Importer;

/**
 * Ocean WP theme functions and main initialization class.
 */
class Plugin_Importer_Wpforms extends Importer {

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

		// Bail if provided json is invalid.
		if (
			false === $maybe_json ||
			! class_exists( '\WPForms\WPForms' )
		) {
			return true;
		}

		add_filter( 'wpforms_current_user_can', '__return_true' );

		// Get the form data.
		$form = array_shift( $maybe_json );

		// Create empty form so we have an ID to work with.
		$new_post = wp_insert_post(
			array(
				'post_status' => 'publish',
				'post_type'   => 'wpforms',
			)
		);

		// Bail if post creation has failed.
		if (
			empty( $new_post ) ||
			is_wp_error( $new_post )
		) {
			return true;
		}

		// very ugly way to change the form id, but since we are using the form
		// in sample data xml, we have to change it that way.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->posts SET ID=%s WHERE ID = %d",
				$form['id'],
				$new_post
			)
		);

		$form['field_id'] = count( $form['fields'] ) + 1;

		// Update the form post, in the way WPForms expects.
		wp_update_post(
			array(
				'ID'           => $form['id'],
				'post_title'   => $form_data['settings']['form_title'],
				'post_type'    => 'wpforms',
				'post_content' => wp_slash( wp_json_encode( $form ) ), // format it as wpforms_encode().
				'post_name'    => 'contact-form',
			)
		);

		remove_filter( 'wpforms_current_user_can', '__return_true' );

		return false;
	}
}
