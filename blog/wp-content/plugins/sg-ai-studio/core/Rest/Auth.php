<?php
/**
 * REST API Authentication class
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use SG_AI_Studio\HelperAuth\SignApiClient;
use SG_AI_Studio\HelperAuth\SignApiAuthException;
use SG_AI_Studio\HelperAuth\SignApiServerException;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API Authentication
 */
class Auth {
	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	private $namespace = 'sg-ai-studio';

	/**
	 * Constructor
	 */
	public function __construct() {
	}


	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register REST API route for init-auth endpoint.
		register_rest_route(
			$this->namespace,
			'/init-auth',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'init_auth' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Register REST API route for generate-token endpoint.
		register_rest_route(
			$this->namespace,
			'/generate-token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_token' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Handle init-auth request
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The REST response.
	 */
	public function init_auth( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid security token.', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Get the base64 encoded data parameter.
		$encoded_data = isset( $request['data'] ) ? esc_attr( $request['data'] ) : '';

		if ( empty( $encoded_data ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Data parameter is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Base64 decode the data.
		$decoded_data = base64_decode( $encoded_data );

		if ( false === $decoded_data ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid token.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Split by colon to get client_id and link_id.
		$parts = explode( ':', $decoded_data );

		if ( count( $parts ) !== 2 ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid token.', 'sg-ai-studio' ),
				),
				400
			);
		}

		$client_id = trim( $parts[0] );
		$link_id   = trim( $parts[1] );

		if ( empty( $client_id ) || empty( $link_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid token.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Save client_id and link_id as site options.
		$client_id_saved = update_option( 'sg_ai_studio_client_id', $client_id );
		$link_id_saved   = update_option( 'sg_ai_studio_link_id', $link_id );

		// Save the connected URL as base64 encoded value.
		$site_url         = get_site_url();
		$encoded_site_url = base64_encode( $site_url );
		update_option( 'sg_ai_studio_connected_url', $encoded_site_url );

		// Make REST API call to dynamic-keys service using Helper.
		$api_result = \SG_AI_Studio\Helper\Helper::init_client_auth( $client_id, $link_id );
		// Check if API call failed.
		if ( ! $api_result['success'] ) {
			return new \WP_REST_Response(
				array(
					'success'      => false,
					'message'      => $api_result['message'],
					'error'        => isset( $api_result['error'] ) ? $api_result['error'] : null,
					'api_response' => isset( $api_result['api_response'] ) ? $api_result['api_response'] : null,
					'status_code'  => isset( $api_result['status_code'] ) ? $api_result['status_code'] : null,
				),
				500
			);
		}

		$auth_token = \SG_AI_Studio\Helper\Helper::generate_ai_studio_token();

		if ( Helper::is_staging_environment() ) {
			$api_url = 'https://api.staging.studio.siteground.ai/api/v1/wp/wp-ping';
		} else {
			$api_url = 'https://api.studio.siteground.ai/api/v1/wp/wp-ping';
		}

		// Call AI Studio Backend API to ping.
		$api_response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'timeout' => 30,
			)
		);

		// Check if API call failed.
		if ( is_wp_error( $api_response ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to connect to AI Studio API.', 'sg-ai-studio' ),
					'error'   => $api_response->get_error_message(),
				),
				500
			);
		} else {
			update_option( 'sg_ai_studio_connected', true );
		}

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Authentication initialized successfully.', 'sg-ai-studio' ),
			),
			200
		);
	}

	/**
	 * Generate authentication token using HelperAuth
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The REST response.
	 */
	public function generate_token( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid security token.', 'sg-ai-studio' ),
				),
				403
			);
		}

		try {
			// Check if the connected URL matches the current site URL.
			$connected_url_encoded = get_option( 'sg_ai_studio_connected_url', '' );
			$connected_url         = base64_decode( $connected_url_encoded );
			$current_url           = get_site_url();
			$client_id             = get_option( 'sg_ai_studio_client_id', '' );

			// Normalize URLs: strip protocol and www. prefix for comparison.
			$connected_url = preg_replace( '/^https?:\/\/(www\.)?/i', '', $connected_url );
			$current_url   = preg_replace( '/^https?:\/\/(www\.)?/i', '', $current_url );

			// Normalize IDN (Internationalized Domain Names) to ASCII/Punycode for comparison.
			if ( function_exists( 'idn_to_ascii' ) ) {
				$connected_url_ascii = idn_to_ascii( $connected_url, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
				$connected_url       = false !== $connected_url_ascii ? $connected_url_ascii : $connected_url;
				$current_url_ascii   = idn_to_ascii( $current_url, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 );
				$current_url         = false !== $current_url_ascii ? $current_url_ascii : $current_url;
			}

			// If URLs don't match, cleanup plugin data.
			if ( $connected_url !== $current_url ) {
				return new \WP_REST_Response(
					array(
						'message' => 'Site URL has changed. Please re-authenticate.',
					),
					401
				);
			}

			// Generate the authentication token.
			$token = Helper::generate_ai_studio_token();

			return new \WP_REST_Response(
				array(
					'token'      => $token,
					'client_id'  => $client_id,
					'expires_in' => 3600,
				),
				200
			);

		} catch ( SignApiAuthException $e ) {
			return new \WP_REST_Response(
				array(
					'message'    => __( 'Authentication error: ', 'sg-ai-studio' ) . $e->getMessage(),
					'error_code' => $e->getStatusCode(),
				),
				400
			);
		} catch ( SignApiServerException $e ) {
			return new \WP_REST_Response(
				array(
					'message'    => __( 'Server error: ', 'sg-ai-studio' ) . $e->getMessage(),
					'error_code' => $e->getStatusCode(),
				),
				500
			);
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Unexpected error: ', 'sg-ai-studio' ) . $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Update domain when primary domain changes
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The REST response.
	 */
	public function update_domain( $request ) {
		// Get the new_domain from request body.
		$new_domain = $request->get_param( 'new_domain' );

		if ( empty( $new_domain ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'new_domain parameter is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Sanitize the domain.
		$new_domain = sanitize_text_field( $new_domain );

		// Ensure the domain has a protocol, default to https if missing.
		if ( ! preg_match( '/^https?:\/\//i', $new_domain ) ) {
			$new_domain = 'https://' . $new_domain;
		}

		// Base64 encode the new domain.
		$encoded_domain = base64_encode( $new_domain );

		// Update the option.
		$updated = update_option( 'sg_ai_studio_connected_url', $encoded_domain );

		if ( ! $updated && get_option( 'sg_ai_studio_connected_url' ) !== $encoded_domain ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to update domain.', 'sg-ai-studio' ),
				),
				500
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Domain updated successfully.', 'sg-ai-studio' ),
			),
			200
		);
	}
}
