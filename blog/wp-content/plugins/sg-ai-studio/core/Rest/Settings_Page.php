<?php
/**
 * Settings Page API class for managing plugin settings via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\HelperAuth\SignApiClient;

/**
 * Handles REST API endpoints for plugin settings operations.
 */
class Settings_Page extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'settings-page';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register powermode control endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/powermode',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_powermode' ),
					'permission_callback' => array( $this, 'ai_studio_powermode_permissions_check' ),
					'description'         => 'Retrieves the current powermode setting.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_powermode' ),
					'permission_callback' => array( $this, 'ai_studio_powermode_permissions_check' ),
					'description'         => 'Updates the powermode setting.',
				),
			)
		);

		// Register connected status endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/connected',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_connected' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Retrieves the current connected status.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_connected' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Updates the connected status.',
				),
			)
		);

		// Register disconnect endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/disconnect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'disconnect' ),
				'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
				'description'         => 'Disconnect the site and clean up all plugin data.',
			)
		);
	}

	/**
	 * Check if a user has permission to update powermode setting
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update items, WP_Error object otherwise.
	 */
	public function ai_studio_powermode_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->check_jwt_authorization( $request );
		}
		return true;
	}

	/**
	 * Check if a user has permission to update plugin's setting
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update items, WP_Error object otherwise.
	 */
	public function ai_studio_settings_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get powermode setting
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_powermode( $request ) {
		$enabled = get_option( 'sg_ai_studio_powermode', false );

		return new WP_REST_Response(
			array(
				'enabled' => (bool) $enabled,
			),
			200
		);
	}

	/**
	 * Update powermode setting
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_powermode( $request ) {
		$enabled = (bool) $request->get_param( 'enabled' );
		if( get_option( 'sg_ai_studio_connected', false ) === false ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Your site is not connected.', 'sg-ai-studio' ),
				),
				403
			);
		}
		// Update the option.
		$updated = update_option( 'sg_ai_studio_powermode', $enabled );

		if ( (bool) get_option( 'sg_ai_studio_powermode' ) === $enabled ) {
			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'enabled' => $enabled,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'message' => __( 'Failed to update powermode setting.', 'sg-ai-studio' ),
				),
				500
			);
		}
	}

	/**
	 * Check if powermode is enabled
	 *
	 * @return bool True if powermode is enabled, false otherwise.
	 */
	public static function is_powermode_enabled() {
		return (bool) get_option( 'sg_ai_studio_powermode', false );
	}

	/**
	 * Get connected status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_connected( $request ) {
		$connected = get_option( 'sg_ai_studio_connected', false );

		return new WP_REST_Response(
			array(
				'connected' => (bool) $connected,
			),
			200
		);
	}

	/**
	 * Update connected status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_connected( $request ) {
		$connected = (bool) $request->get_param( 'connected' );

		// Update the option.
		$updated = update_option( 'sg_ai_studio_connected', $connected );

		if ( (bool) get_option( 'sg_ai_studio_connected' ) === $connected ) {
			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'message' => __( 'Connected status has been updated successfully.', 'sg-ai-studio' ),
					'connected' => $connected,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to update connected status.', 'sg-ai-studio' ),
				),
				500
			);
		}
	}

	/**
	 * Check if site is connected
	 *
	 * @return bool True if site is connected, false otherwise.
	 */
	public static function is_connected() {
		return (bool) get_option( 'sg_ai_studio_connected', false );
	}

	/**
	 * Disconnect the site and clean up all plugin data
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function disconnect( $request ) {
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
			// API disconnect call failed - continuing with local cleanup.
		}

		$result = Helper::cleanup_plugin_data();

		// Prepare response.
		if ( $result['success'] ) {
			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Successfully disconnected and cleaned up all SG AI Studio data.', 'sg-ai-studio' ),
				),
				200
			);
		} else {
			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Disconnect completed with some errors during local cleanup.', 'sg-ai-studio' ),
					'errors'  => $result['errors'],
				),
				207
			);
		}
	}

}
