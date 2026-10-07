<?php
namespace SiteGround_Central\Rest\Controllers\V1;

use SiteGround_Central\Wizard\Wizard;

/**
 * Class responsible for getting Wizards data through a REST API.
 */
class Rest_Wizards extends Rest {
	/**
	 * Register the routes for wizards.
	 *
	 * @since  3.0.0
	 */
	public function register_routes() {
		// Get wizard request.
		register_rest_route(
			'siteground-central/v1',
			'/wizard/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_wizard' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);

		// Close wizard request.
		register_rest_route(
			'siteground-central/v1',
			'/exit-wizard/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'exit_wizard' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Retrieve the site's corresponding wizard.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response Returns a WP_Rest_Response in a JSON format with the Wizard Data, error if failed.
	 */
	public function get_wizard() {
		return \rest_ensure_response( Wizard::get_wizard() );
	}

	/**
	 * Update the wizard activation redirect.
	 *
	 * @since 3.0.0
	 */
	public function exit_wizard() {
		// Update visibility.
		! \is_multisite() ? update_option( 'siteground_wizard_activation_redirect', 'no' ) : update_site_option( 'siteground_wizard_activation_redirect', 'no' );

		return self::send_response( array(
			'exit_url' => ! \is_multisite() ? admin_url( 'admin.php?page=siteground-dashboard.php' ) : admin_url()
		) );
	}
}
