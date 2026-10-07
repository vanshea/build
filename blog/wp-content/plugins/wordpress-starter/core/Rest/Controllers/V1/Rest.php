<?php
/**
 * Initialize this version of the REST API.
 */

namespace SiteGround_Central\Rest\Controllers\V1;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Rest {

	/**
	 * Check if a given request has admin access
	 *
	 * @since  3.0.0
	 *
	 * @param  WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_permissions( $request ) {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Sent Rest response object.
	 *
	 * @since  3.0.0
	 *
	 * @param  array   $data        Data to be send.
	 * @param  integer $result      The result status.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function send_response( $data = array(), $result = 1  ) {
		// Prepare the status code, based on the result.
		$status_code = 1 === $result ? 200 : 400;

		// Ensure the REST response is a response object.
		$response = \rest_ensure_response(
			array(
				'data'    => $data,
				'status'  => $status_code,
			)
		);

		// Set the object status code.
		$response->set_status( $status_code );

		// Set headers if missing.
		if ( ! headers_sent() ) {
			$response->header( 'Content-Type', 'application/json; charset=' . get_option( 'blog_charset' ) );
		}

		// Return the response.
		return $response;
	}
}
