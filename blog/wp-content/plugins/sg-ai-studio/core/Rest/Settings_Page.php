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

		// Register provider connected status endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/provider-connected',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_provider_connected' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Retrieves the current provider connected status.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_provider_connected' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Updates the provider connected status.',
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

		// Register auto-connect endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/auto-connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'auto_connect' ),
				'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
				'description'         => 'Auto-connect to AI Studio on SiteGround sites.',
			)
		);

		// Register provider disconnect endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/disconnect-provider',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'disconnect_provider' ),
				'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
				'description'         => 'Disconnect AI Studio as a provider from WP Connectors without affecting plugin connection.',
			)
		);

		// Register provider reconnect endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/reconnect-provider',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reconnect_provider' ),
				'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
				'description'         => 'Reconnect AI Studio as a provider to WP Connectors.',
			)
		);

		// Register Gutenberg actions toggle endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/gutenberg-actions',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_gutenberg_actions_status' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Retrieves the current Gutenberg actions status.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_gutenberg_actions_status' ),
					'permission_callback' => array( $this, 'ai_studio_settings_permissions_check' ),
					'description'         => 'Updates the Gutenberg actions status (enable/disable).',
				),
			)
		);

		// Register chat bubble visibility endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/chat-bubble',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_chat_bubble' ),
					'permission_callback' => array( $this, 'ai_studio_powermode_permissions_check' ),
					'description'         => 'Retrieves the current chat bubble visibility state.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_chat_bubble' ),
					'permission_callback' => array( $this, 'ai_studio_powermode_permissions_check' ),
					'description'         => 'Updates the chat bubble visibility state.',
				),
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
		$provider_connected = get_option( 'sg_ai_studio_provider_connected', true );

		if ( ! $connected ) {
			return new WP_REST_Response(
				array(
					'connected' => false,
					'provider_connected' => false,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'connected' => (bool) $connected,
				'provider_connected' => (bool) $provider_connected,
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
	 * Get provider connected status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_provider_connected( $request ) {
		$provider_connected = get_option( 'sg_ai_studio_provider_connected', false );

		if ( ! get_option( 'sg_ai_studio_connected', false ) ) {
			return new WP_REST_Response(
				array(
					'provider_connected' => false,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'provider_connected' => (bool) $provider_connected,
			),
			200
		);
	}

	/**
	 * Update provider connected status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_provider_connected( $request ) {
		$enabled = (bool) $request->get_param( 'connected' );
		// Update the option.
		update_option( 'sg_ai_studio_provider_connected', $enabled, 'yes' );

		if ( (bool) get_option( 'sg_ai_studio_provider_connected' ) === $enabled ) {
			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'message'            => __( 'Provider connected status has been updated successfully.', 'sg-ai-studio' ),
					'provider_connected' => $enabled,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to update provider connected status.', 'sg-ai-studio' ),
				),
				500
			);
		}
	}

	/**
	 * Check if provider is connected
	 *
	 * @return bool True if provider is connected, false otherwise.
	 */
	public static function is_provider_connected() {
		return (bool) get_option( 'sg_ai_studio_provider_connected', true );
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

	/**
	 * Auto-connect to AI Studio on SiteGround sites.
	 *
	 * Uses Site Tools client to register with AI Studio and fetch credentials.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function auto_connect( $request ) {
		// Load Site Tools classes (manual require - plugin doesn't use autoloading).
		require_once plugin_dir_path( __DIR__ ) . 'Site_Tools_Client/Site_Tools_Client.php';
		require_once plugin_dir_path( __DIR__ ) . 'Site_Tools_Client/Connect_Agent.php';

		// Attempt to connect to the AI Studio.
		$result = \SG_AI_Studio\Site_Tools_Client\Connect_Agent::connect();

		// Check if the connection was successful.
		if ( false === $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to connect to AI Studio. Please try again later.', 'siteground-wizard' ),
				),
				500
			);
		}

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}
		update_option( 'sg_ai_studio_provider_connected', true );

		// Return success response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Successfully connected to AI Studio.', 'siteground-wizard' ),
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Disconnect AI Studio as a provider from WP Connectors.
	 *
	 * Sets sg_ai_studio_provider_connected to false, removing AI Studio from
	 * the WordPress AI Client provider registry without affecting the underlying
	 * plugin connection or credentials.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function disconnect_provider( $request ) {
		// Verify plugin is connected first.
		if ( ! self::is_connected() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'AI Studio is not connected. Please connect first.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Disconnect the provider.
		update_option( 'sg_ai_studio_provider_connected', false );
		set_transient( 'sg_ai_studio_provider_disconnected', true, 60 );

		// Clear caches to reflect the change.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'AI Studio provider disconnected successfully. The plugin remains connected.', 'sg-ai-studio' ),
			),
			200
		);

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Failed to disconnect provider.', 'sg-ai-studio' ),
			),
			500
		);
	}

	/**
	 * Reconnect AI Studio as a provider to WP Connectors.
	 *
	 * Sets sg_ai_studio_provider_connected to true, re-registering AI Studio
	 * in the WordPress AI Client provider registry.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function reconnect_provider( $request ) {
		// Verify plugin is connected first.
		if ( ! self::is_connected() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'AI Studio is not connected. Please connect first.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Reconnect the provider.
		update_option( 'sg_ai_studio_provider_connected', true );
		set_transient( 'sg_ai_studio_provider_reconnected', true, 60 );

		// Clear caches to reflect the change.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'AI Studio provider reconnected successfully.', 'sg-ai-studio' ),
			),
			200
		);
	}

	/**
	 * Get Gutenberg actions status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_gutenberg_actions_status( $request ) {
		$enabled = get_option( 'sg_ai_studio_gutenberg_actions', false );

		return new WP_REST_Response(
			array(
				'enabled' => (bool) $enabled,
			),
			200
		);
	}

	/**
	 * Update Gutenberg actions status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_gutenberg_actions_status( $request ) {
		$enabled = (bool) $request->get_param( 'enabled' );

		// Update the option.
		update_option( 'sg_ai_studio_gutenberg_actions', (int) $enabled );

		return new WP_REST_Response(
			array(
				'enabled' => $enabled,
			),
			200
		);
	}

	/**
	 * Check if Gutenberg actions are enabled
	 *
	 * @return bool True if Gutenberg actions are enabled, false otherwise.
	 */
	public static function is_gutenberg_actions_enabled() {
		return (bool) get_option( 'sg_ai_studio_gutenberg_actions', false );
	}

	/**
	 * Get chat bubble visibility state
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function get_chat_bubble( $request ) {
		return new WP_REST_Response(
			array(
				'admin_hidden' => (bool) get_option( 'sg_ai_studio_chat_bubble_admin_hidden', false ),
				'fe_hidden'    => (bool) get_option( 'sg_ai_studio_chat_bubble_fe_hidden', false ),
			),
			200
		);
	}

	/**
	 * Update chat bubble visibility state
	 *
	 * Accepts either or both fields: admin_hidden, fe_hidden.
	 * Only acts on fields present in the request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function update_chat_bubble( $request ) {
		$updated_fields = array();

		// Handle admin_hidden field if present.
		if ( $request->has_param( 'admin_hidden' ) ) {
			$admin_hidden = (bool) $request->get_param( 'admin_hidden' );
			update_option( 'sg_ai_studio_chat_bubble_admin_hidden', (int) $admin_hidden );
			$updated_fields['admin_hidden'] = $admin_hidden;
		}

		// Handle fe_hidden field if present.
		if ( $request->has_param( 'fe_hidden' ) ) {
			$fe_hidden = (bool) $request->get_param( 'fe_hidden' );
			update_option( 'sg_ai_studio_chat_bubble_fe_hidden', (int) $fe_hidden );
			$updated_fields['fe_hidden'] = $fe_hidden;
		}

		// If no recognized fields were provided.
		if ( empty( $updated_fields ) ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'No valid fields provided.', 'sg-ai-studio' ),
				),
				400
			);
		}

		return new WP_REST_Response( $updated_fields, 200 );
	}

	/**
	 * Check if chat bubble is hidden in admin
	 *
	 * @return bool True if admin bubble is hidden, false if visible (default).
	 */
	public static function is_chat_bubble_admin_hidden() {
		return (bool) get_option( 'sg_ai_studio_chat_bubble_admin_hidden', false );
	}

	/**
	 * Check if chat bubble is hidden on front-end
	 *
	 * @return bool True if FE bubble is hidden, false if visible (default).
	 */
	public static function is_chat_bubble_fe_hidden() {
		return (bool) get_option( 'sg_ai_studio_chat_bubble_fe_hidden', false );
	}

}
