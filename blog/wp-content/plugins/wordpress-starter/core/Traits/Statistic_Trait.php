<?php
namespace SiteGround_Central\Traits;

/**
 * Trait used for REST_API checks.
 */
trait Statistic_Trait {

	/**
	 * Send statistics to the Siteground api.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $event   The event that should be sent.
	 *
	 * @return array|\WP_Error Response object.
	 */
	public static function send_statistics( $event ) {
		// Set API URL.
		$url = \SiteGround_Central\SG_WPAPI_URL . '/statistics';

		// Set body for request.
		$body = array( 'event' => $event );

		// Return the response from the API.
		return self::call_sg_api( $url, json_encode( $body ) );
	}

	/**
	 * Send a request to the SG API.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url      The requested URL.
	 * @param string $body     The body of the request.
	 *
	 * @return array|\WP_Error Response object.
	 */
	public static function call_sg_api( $url, $body ) {
		// Send the POST request.
		$response = wp_remote_post(
			$url,
			array(
				'method'   => 'POST',
				'timeout'  => 45,
				'blocking' => true,
				'headers'  => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				),
				'body' => $body,
				'sslverify' => false,
			)
		);

		return $response;
	}

	/**
	 * Retrieve the user's IP address.
	 *
	 * @since 3.0.0
	 *
	 * @return string The user's IP address.
	 */
	public static function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}
