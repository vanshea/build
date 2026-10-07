<?php
/**
 * Core API class for managing WordPress core operations via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use Core_Upgrader;
use Language_Pack_Upgrader;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WordPress core operations.
 */
class Core extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'core';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for WordPress core update.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/core-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_core' ),
				'permission_callback' => array( $this, 'manage_core_permissions_check' ),
				'description'         => 'Updates WordPress core to the latest version.',
			)
		);

		// Register endpoint for language pack update.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/language-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_language_packs' ),
				'permission_callback' => array( $this, 'manage_core_permissions_check' ),
				'description'         => 'Updates WordPress language packs.',
			)
		);

		// Register endpoint for clearing cache.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/clear-cache',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => array( $this, 'manage_core_permissions_check' ),
				'description'         => 'Clears WordPress and SiteGround caches.',
			)
		);
	}

	/**
	 * Check if a user has permission to manage WordPress core.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function manage_core_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Update WordPress core to the latest version.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_core( $request ) {
		// Check if powermode is enabled.
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}

		// Include necessary files.
		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! class_exists( 'Core_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Check for updates.
		\wp_version_check();
		$updates = \get_core_updates();

		if ( ! is_array( $updates ) || empty( $updates ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No core updates available.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Get the latest update.
		$update = reset( $updates );

		if ( ! isset( $update->response ) || 'upgrade' !== $update->response ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'WordPress core is already up to date.', 'sg-ai-studio' ),
					'data'    => array(
						'current_version' => get_bloginfo( 'version' ),
					),
				),
				200
			);
		}

		// Create upgrader instance.
		$upgrader = new Core_Upgrader(
			new \WP_Ajax_Upgrader_Skin()
		);

		// Perform the upgrade.
		$result = $upgrader->upgrade( $update );

		if ( is_wp_error( $result ) ) {
			// Log the activity.
			/* translators: %s is the error message. */
			$log_description = sprintf( __( 'Core Update Failed: %s', 'sg-ai-studio' ), $result->get_error_message() );
			Activity_Log_Helper::add_log_entry( 'Core', $log_description );

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				200
			);
		}

		// Get the new version.
		$new_version = $update->version;

		// Log the activity.
		/* translators: %s is the new WordPress version. */
		$log_description = sprintf( __( 'Core Updated: WordPress updated to version %s', 'sg-ai-studio' ), $new_version );
		Activity_Log_Helper::add_log_entry( 'Core', $log_description );
		

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
				'message' => __( 'WordPress core updated successfully.', 'sg-ai-studio' ),
				'data'    => array(
					'new_version' => $new_version,
				),
			),
			200
		);
	}

	/**
	 * Update WordPress language packs.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function update_language_packs( $request ) {
		// Check if powermode is enabled.
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}

		// Include necessary files.
		if ( ! function_exists( 'wp_get_translation_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		if ( ! class_exists( 'Language_Pack_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Check for language pack updates.
		// \wp_update_translations();
		\delete_site_transient( 'update_plugins' );
		\delete_site_transient( 'update_themes' );
		\delete_site_transient( 'update_core' );
		\wp_update_themes();
		\wp_version_check();
		\wp_update_plugins();

		$updates = \wp_get_translation_updates();

		if ( empty( $updates ) ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'All language packs are up to date.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Create upgrader instance.
		$upgrader = new Language_Pack_Upgrader(
			new \WP_Ajax_Upgrader_Skin()
		);

		// Perform the bulk upgrade.
		$result = $upgrader->bulk_upgrade( $updates );

		if ( is_wp_error( $result ) ) {
			// Log the activity.
			/* translators: %s is the error message. */
			$log_description = sprintf( __( 'Language Pack Update Failed: %s', 'sg-ai-studio' ), $result->get_error_message() );
			Activity_Log_Helper::add_log_entry( 'Core', $log_description );

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				207
			);
		}

		// Count successful updates.
		$updated_count = 0;
		$failed_count  = 0;

		if ( is_array( $result ) ) {
			foreach ( $result as $update_result ) {
				if ( ! is_wp_error( $update_result ) && $update_result ) {
					$updated_count++;
				} else {
					$failed_count++;
				}
			}
		}

		// Log the activity.
		/* translators: %d is the number of language packs updated. */
		$log_description = sprintf( __( 'Language Packs Updated: %d language pack(s) updated successfully', 'sg-ai-studio' ), $updated_count );
		Activity_Log_Helper::add_log_entry( 'Core', $log_description );

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
				'message' => sprintf(
					/* translators: %d is the number of language packs updated. */
					__( '%d language pack(s) updated successfully.', 'sg-ai-studio' ),
					$updated_count
				),
				'data'    => array(
					'updated' => $updated_count,
					'failed'  => $failed_count,
				),
			),
			200
		);
	}

	/**
	 * Clear WordPress and SiteGround caches.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function clear_cache( $request ) {
		// Check if powermode is enabled.
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		// Log the activity.
		$log_description = __( 'Cache Cleared: All caches have been cleared', 'sg-ai-studio' );
		Activity_Log_Helper::add_log_entry( 'Core', $log_description );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Cache cleared successfully.', 'sg-ai-studio' ),
			),
			200
		);
	}
}
