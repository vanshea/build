<?php
/**
 * Themes API class for managing WordPress themes via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for theme operations.
 */
class Themes extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'themes';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for listing and installing themes.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_themes' ),
					'permission_callback' => array( $this, 'get_themes_permissions_check' ),
					'args'                => $this->get_themes_args(),
					'description'         => 'Retrieves a list of all installed themes with their statuses.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_theme' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => $this->get_install_theme_args(),
					'description'         => 'Installs a new theme from WordPress.org.',
				),
				'schema' => array( $this, 'get_theme_schema' ),
			)
		);

		// Register endpoint for retrieving, activating, updating, and deleting a single theme.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<stylesheet>[^/]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_theme' ),
					'permission_callback' => array( $this, 'get_themes_permissions_check' ),
					'args'                => array(
						'stylesheet' => array(
							'description' => 'Theme stylesheet directory (slug).',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves detailed information about a specific theme.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'activate_theme' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => array(
						'stylesheet' => array(
							'description' => 'Theme stylesheet directory (slug).',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Activates a specific theme.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_theme' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => array(
						'stylesheet' => array(
							'description' => 'Theme stylesheet directory (slug).',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Updates a specific theme to the latest version.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_theme' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => array(
						'stylesheet' => array(
							'description' => 'Theme stylesheet directory (slug).',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific theme.',
				),
				'schema' => array( $this, 'get_theme_schema' ),
			)
		);

		// Register endpoint for batch operations on themes.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_install_themes' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => $this->get_batch_install_themes_args(),
					'description'         => 'Installs multiple themes in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_themes' ),
					'permission_callback' => array( $this, 'manage_themes_permissions_check' ),
					'args'                => $this->get_batch_delete_themes_args(),
					'description'         => 'Deletes multiple themes in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to get themes information.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_themes_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to manage themes.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to manage items, WP_Error object otherwise.
	 */
	public function manage_themes_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for retrieving themes.
	 *
	 * @return array
	 */
	protected function get_themes_args() {
		return array(
			'status' => array(
				'description' => 'Filter themes by status.',
				'type'        => 'string',
				'enum'        => array( 'active', 'inactive', 'all' ),
				'default'     => 'all',
				'required'    => false,
			),
			'search' => array(
				'description' => 'Search term to filter themes by name.',
				'type'        => 'string',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for installing a theme.
	 *
	 * @return array
	 */
	protected function get_install_theme_args() {
		return array(
			'slug'     => array(
				'description' => 'The theme slug from WordPress.org.',
				'type'        => 'string',
				'required'    => false,
			),
			'activate' => array(
				'description' => 'Whether to activate the theme after installation.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for batch installing themes.
	 *
	 * @return array
	 */
	protected function get_batch_install_themes_args() {
		return array(
			'themes' => array(
				'description' => 'List of themes to install.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'slug'     => array(
							'description' => 'The theme slug from WordPress.org.',
							'type'        => 'string',
							'required'    => false,
						),
						'activate' => array(
							'description' => 'Whether to activate the theme after installation.',
							'type'        => 'boolean',
							'default'     => false,
							'required'    => false,
						),
					),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting themes.
	 *
	 * @return array
	 */
	protected function get_batch_delete_themes_args() {
		return array(
			'themes' => array(
				'description' => 'List of theme stylesheets to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get theme schema.
	 *
	 * @return array
	 */
	public function get_theme_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'theme',
			'type'       => 'object',
			'properties' => array(
				'name'           => array(
					'description' => 'The name of the theme.',
					'type'        => 'string',
				),
				'stylesheet'     => array(
					'description' => 'The theme stylesheet directory.',
					'type'        => 'string',
				),
				'template'       => array(
					'description' => 'The theme template directory (parent theme).',
					'type'        => 'string',
				),
				'version'        => array(
					'description' => 'The version of the theme.',
					'type'        => 'string',
				),
				'description'    => array(
					'description' => 'The description of the theme.',
					'type'        => 'string',
				),
				'author'         => array(
					'description' => 'The author of the theme.',
					'type'        => 'string',
				),
				'author_uri'     => array(
					'description' => 'The website of the theme author.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'theme_uri'      => array(
					'description' => 'The website of the theme.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'status'         => array(
					'description' => 'The status of the theme.',
					'type'        => 'string',
					'enum'        => array( 'active', 'inactive', 'parent' ),
				),
				'requires_wp'    => array(
					'description' => 'Minimum required WordPress version.',
					'type'        => 'string',
				),
				'requires_php'   => array(
					'description' => 'Minimum required PHP version.',
					'type'        => 'string',
				),
				'is_child_theme' => array(
					'description' => 'Whether the theme is a child theme.',
					'type'        => 'boolean',
				),
				'screenshot'     => array(
					'description' => 'URL to the theme screenshot.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'update_version' => array(
					'description' => 'The available update version, if an update is available.',
					'type'        => 'string',
				),
			),
		);
	}

	/**
	 * Get batch schema.
	 *
	 * @return array
	 */
	public function get_batch_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'description' => 'Whether the batch operation was successful.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'data'    => array(
					'description' => 'Data returned by the batch operation.',
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get a list of themes
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_themes( $request ) {
		// Get all themes.
		$themes        = wp_get_themes();
		$current_theme = wp_get_theme();

		$status = $request['status'] ? $request['status'] : 'all';
		$search = $request['search'] ? sanitize_text_field( $request['search'] ) : '';

		if ( ! function_exists( '\wp_clean_themes_cache' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		if ( ! function_exists( '\wp_update_themes' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		// Force refresh of theme update information.
		\wp_clean_themes_cache();
		\delete_site_transient( 'update_themes' );
		\wp_update_themes();

		// Get available theme updates.
		$update_themes = get_site_transient( 'update_themes' );

		$data = array();

		foreach ( $themes as $stylesheet => $theme ) {
			// Skip if not matching search term.
			if ( ! empty( $search ) && stripos( $theme->get( 'Name' ), $search ) === false ) {
				continue;
			}

			$is_active = ( $current_theme->get_stylesheet() === $stylesheet );
			$is_parent = ( $current_theme->get_template() === $stylesheet && $current_theme->get_stylesheet() !== $stylesheet );

			// Filter by status if specified.
			if ( 'active' === $status && ! $is_active && ! $is_parent ) {
				continue;
			}
			if ( 'inactive' === $status && ( $is_active || $is_parent ) ) {
				continue;
			}

			// Set theme status.
			if ( $is_active ) {
				$theme_status = 'active';
			} elseif ( $is_parent ) {
				$theme_status = 'parent';
			} else {
				$theme_status = 'inactive';
			}

			$screenshot     = $theme->get_screenshot();
			$screenshot_url = $screenshot ? $screenshot : '';

			$theme_data = array(
				'name'           => $theme->get( 'Name' ),
				'stylesheet'     => $stylesheet,
				'template'       => $theme->get_template(),
				'version'        => $theme->get( 'Version' ),
				'description'    => $theme->get( 'Description' ),
				'author'         => $theme->get( 'Author' ),
				'author_uri'     => $theme->get( 'AuthorURI' ),
				'theme_uri'      => $theme->get( 'ThemeURI' ),
				'status'         => $theme_status,
				'requires_wp'    => $theme->get( 'RequiresWP' ),
				'requires_php'   => $theme->get( 'RequiresPHP' ),
				'is_child_theme' => $theme->parent() !== false,
				'screenshot'     => $screenshot_url,
			);

			// Add update_version if an update is available.
			if ( ! empty( $update_themes->response ) && isset( $update_themes->response[ $stylesheet ] ) ) {
				$theme_data['update_version'] = $update_themes->response[ $stylesheet ]['new_version'];
			}

			$data[] = $theme_data;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get a single theme
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_theme( $request ) {
		$stylesheet = $request['stylesheet'];
		$theme      = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$current_theme = wp_get_theme();

		$is_active = $current_theme->get_stylesheet() === $stylesheet;
		$is_parent = $current_theme->get_template() === $stylesheet && $current_theme->get_stylesheet() !== $stylesheet;

		// Set theme status.
		if ( $is_active ) {
			$theme_status = 'active';
		} elseif ( $is_parent ) {
			$theme_status = 'parent';
		} else {
			$theme_status = 'inactive';
		}

		$screenshot     = $theme->get_screenshot();
		$screenshot_url = $screenshot ? $screenshot : '';

		if ( ! function_exists( '\wp_clean_themes_cache' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		if ( ! function_exists( '\wp_update_themes' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		// Force refresh of theme update information.
		\wp_clean_themes_cache();
		\delete_site_transient( 'update_themes' );
		\wp_update_themes();

		// Get available theme updates.
		$update_themes = get_site_transient( 'update_themes' );

		$data = array(
			'name'           => $theme->get( 'Name' ),
			'stylesheet'     => $stylesheet,
			'template'       => $theme->get_template(),
			'version'        => $theme->get( 'Version' ),
			'description'    => $theme->get( 'Description' ),
			'author'         => $theme->get( 'Author' ),
			'author_uri'     => $theme->get( 'AuthorURI' ),
			'theme_uri'      => $theme->get( 'ThemeURI' ),
			'status'         => $theme_status,
			'requires_wp'    => $theme->get( 'RequiresWP' ),
			'requires_php'   => $theme->get( 'RequiresPHP' ),
			'is_child_theme' => $theme->parent() !== false,
			'screenshot'     => $screenshot_url,
		);

		// Add update_version if an update is available.
		if ( ! empty( $update_themes->response ) && isset( $update_themes->response[ $stylesheet ] ) ) {
			$data['update_version'] = $update_themes->response[ $stylesheet ]['new_version'];
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Install a theme
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function install_theme( $request ) {
		// Check if slug is provided.
		if ( empty( $request['slug'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please provide either a theme slug.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Include necessary files.
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/theme-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Setup upgrader.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Theme_Upgrader( $skin );

		// Install the theme.
		$result = false;

		if ( ! empty( $request['slug'] ) ) {
			// Install from WordPress.org.
			$api = themes_api(
				'theme_information',
				array(
					'slug'   => $request['slug'],
					'fields' => array(
						'sections'      => false,
						'rating'        => false,
						'ratings'       => false,
						'downloaded'    => false,
						'download_link' => true,
						'screenshots'   => false,
						'last_updated'  => false,
						'homepage'      => false,
						'tags'          => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $api->get_error_message(),
					),
					200
				);
			}

			$result = $upgrader->install( $api->download_link );
		}

		// Check for installation errors.
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				200
			);
		}

		if ( is_wp_error( $skin->result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $skin->result->get_error_message(),
				),
				200
			);
		}

		if ( $skin->get_errors()->has_errors() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $skin->get_error_messages(),
				),
				200
			);
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme installation failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Get the installed theme stylesheet directory.
		$stylesheet = $upgrader->theme_info();
		if ( ! $stylesheet ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme installation completed, but theme information could not be retrieved.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Activate the theme if requested.
		$activated = false;
		if ( $request['activate'] ) {
			$activation_result = $this->safe_switch_theme( $stylesheet->get_stylesheet() );
			if ( is_wp_error( $activation_result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $activation_result->get_error_message(),
					),
					200
				);
			}
			$activated = true;
		}

		// Get theme data.
		$theme = wp_get_theme( $stylesheet );

		// Log the activity.
		if ( $activated ) {
			/* translators: %s is the theme name. */
			$log_description = sprintf( __( 'Theme Installed: %s (Activated)', 'sg-ai-studio' ), $theme->get( 'Name' ) );
		} else {
			/* translators: %s is the theme name. */
			$log_description = sprintf( __( 'Theme Installed: %s', 'sg-ai-studio' ), $theme->get( 'Name' ) );
		}
		Activity_Log_Helper::add_log_entry( 'Themes', $log_description );

		$screenshot     = $theme->get_screenshot();
		$screenshot_url = $screenshot ? $screenshot : '';

		$data = array(
			'name'           => $theme->get( 'Name' ),
			'stylesheet'     => $stylesheet,
			'template'       => $theme->get_template(),
			'version'        => $theme->get( 'Version' ),
			'description'    => $theme->get( 'Description' ),
			'author'         => $theme->get( 'Author' ),
			'author_uri'     => $theme->get( 'AuthorURI' ),
			'theme_uri'      => $theme->get( 'ThemeURI' ),
			'status'         => $activated ? 'active' : 'inactive',
			'requires_wp'    => $theme->get( 'RequiresWP' ),
			'requires_php'   => $theme->get( 'RequiresPHP' ),
			'is_child_theme' => $theme->parent() !== false,
			'screenshot'     => $screenshot_url,
		);

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
				'message' => __( 'Theme installed successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			201
		);
	}

	/**
	 * Activate a theme.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function activate_theme( $request ) {
		$stylesheet = $request['stylesheet'];
		$theme      = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Check if theme is already active.
		$current_theme = wp_get_theme();
		if ( $current_theme->get_stylesheet() === $stylesheet ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme is already active.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Switch to the theme with error handling.
		$activation_result = $this->safe_switch_theme( $stylesheet );
		if ( is_wp_error( $activation_result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $activation_result->get_error_message(),
				),
				500
			);
		}

		// Get updated theme data.
		$theme = wp_get_theme( $stylesheet );

		// Log the activity.
		/* translators: %s is the theme name. */
		$log_description = sprintf( __( 'Theme Activated: %s', 'sg-ai-studio' ), $theme->get( 'Name' ) );
		Activity_Log_Helper::add_log_entry( 'Themes', $log_description );

		$screenshot     = $theme->get_screenshot();
		$screenshot_url = $screenshot ? $screenshot : '';

		$data = array(
			'name'       => $theme->get( 'Name' ),
			'stylesheet' => $stylesheet,
			'template'   => $theme->get_template(),
			'version'    => $theme->get( 'Version' ),
			'status'     => 'active',
			'screenshot' => $screenshot_url,
		);

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
				'message' => __( 'Theme activated successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Update a theme.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_theme( $request ) {
		$stylesheet = $request['stylesheet'];
		$theme      = \wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get current theme data.
		$version_before = $theme->get( 'Version' );

		// Include necessary files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';

		// Force refresh of theme update information.
		\wp_clean_themes_cache();
		\delete_site_transient( 'update_themes' );
		\wp_update_themes();

		// Check if theme is active before update.
		$current_theme = \wp_get_theme();
		$was_active    = $current_theme->get_stylesheet() === $stylesheet;

		// Setup upgrader.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Theme_Upgrader( $skin );

		// Perform the update.
		$result = $upgrader->upgrade( $stylesheet );

		// Check for update errors.
		if ( \is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				200
			);
		}

		if ( \is_wp_error( $skin->result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $skin->result->get_error_message(),
				),
				200
			);
		}

		if ( $skin->get_errors()->has_errors() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $skin->get_error_messages(),
				),
				200
			);
		}

		if ( is_null( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme is already up to date.', 'sg-ai-studio' ),
				),
				200
			);
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme update failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Re-activate theme if it was active before.
		if ( $was_active ) {
			$activation_result = $this->safe_switch_theme( $stylesheet );
			if ( is_wp_error( $activation_result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => sprintf(
							/* translators: %s is the error message. */
							__( 'Theme updated successfully but reactivation failed: %s', 'sg-ai-studio' ),
							$activation_result->get_error_message()
						),
					),
					500
				);
			}
		}

		// Get updated theme data.
		$theme          = \wp_get_theme( $stylesheet );
		$version_after  = $theme->get( 'Version' );
		$screenshot     = $theme->get_screenshot();
		$screenshot_url = $screenshot ? $screenshot : '';

		// Log the activity.
		/* translators: %1$s is the theme name, %2$s is the old version, %3$s is the new version. */
		$log_description = sprintf( __( 'Theme Updated: %1$s (from version %2$s to %3$s)', 'sg-ai-studio' ), $theme->get( 'Name' ), $version_before, $version_after );
		Activity_Log_Helper::add_log_entry( 'Themes', $log_description );

		$data = array(
			'name'           => $theme->get( 'Name' ),
			'stylesheet'     => $stylesheet,
			'template'       => $theme->get_template(),
			'version'        => $version_after,
			'description'    => $theme->get( 'Description' ),
			'author'         => $theme->get( 'Author' ),
			'author_uri'     => $theme->get( 'AuthorURI' ),
			'theme_uri'      => $theme->get( 'ThemeURI' ),
			'status'         => $was_active ? 'active' : 'inactive',
			'requires_wp'    => $theme->get( 'RequiresWP' ),
			'requires_php'   => $theme->get( 'RequiresPHP' ),
			'is_child_theme' => $theme->parent() !== false,
			'screenshot'     => $screenshot_url,
			'old_version'    => $version_before,
		);

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
				'message' => __( 'Theme updated successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Delete a theme.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_theme( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$stylesheet = $request['stylesheet'];
		$theme      = wp_get_theme( $stylesheet );

		if ( ! $theme->exists() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get theme data before deletion.
		$theme_data = array(
			'name'       => $theme->get( 'Name' ),
			'stylesheet' => $stylesheet,
			'version'    => $theme->get( 'Version' ),
		);

		// Check if the theme is active.
		$current_theme = wp_get_theme();
		if ( $current_theme->get_stylesheet() === $stylesheet ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Cannot delete the active theme.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Check if the theme is a parent of the active theme.
		if ( $current_theme->get_template() === $stylesheet ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Cannot delete the parent of the active theme.', 'sg-ai-studio' ),
				),
				200
			);
		}

		if ( ! function_exists( 'delete_theme' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}

		// Delete the theme.
		$result = \delete_theme( $stylesheet );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				200
			);
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Theme deletion failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Log the activity.
		/* translators: %s is the theme name. */
		$log_description = sprintf( __( 'Theme Deleted: %s', 'sg-ai-studio' ), $theme->get( 'Name' ) );
		Activity_Log_Helper::add_log_entry( 'Themes', $log_description );

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
				'message' => __( 'Theme deleted successfully.', 'sg-ai-studio' ),
				'data'    => $theme_data,
			),
			200
		);
	}

	/**
	 * Batch install themes.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_install_themes( $request ) {
		$themes  = $request['themes'];
		$results = array();
		$errors  = array();

		foreach ( $themes as $theme ) {
			// Create a new request for each theme.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add theme data to the request.
			if ( ! empty( $theme['slug'] ) ) {
				$sub_request->set_param( 'slug', $theme['slug'] );
			}
			if ( isset( $theme['activate'] ) ) {
				$sub_request->set_param( 'activate', $theme['activate'] );
			}

			// Install the theme.
			$response = $this->install_theme( $sub_request );

			$identifier = ! empty( $theme['slug'] ) ? $theme['slug'] : '';

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $identifier ] = $response->get_data();
			} else {
				$results[ $identifier ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'installed' => $results,
					'errors'    => $errors,
				),
			),
			$success ? 201 : 207
		);
	}

	/**
	 * Batch delete themes.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_themes( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$themes  = $request['themes'];
		$results = array();
		$errors  = array();

		foreach ( $themes as $stylesheet ) {
			// Create a new request for each theme.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $stylesheet );
			$sub_request->set_param( 'stylesheet', $stylesheet );

			// Delete the theme.
			$response = $this->delete_theme( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $stylesheet ] = $response->get_data();
			} else {
				$results[ $stylesheet ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'deleted' => $results,
					'errors'  => $errors,
				),
			),
			$success ? 200 : 207
		);
	}

	/**
	 * Safely switch theme with proper error handling.
	 *
	 * Unlike switch_theme() which uses wp_die() on errors, this method returns
	 * WP_Error objects for proper REST API error handling.
	 *
	 * @param string $stylesheet Theme stylesheet name to activate.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function safe_switch_theme( $stylesheet ) {
		// Validate theme exists.
		$theme = wp_get_theme( $stylesheet );
		if ( ! $theme->exists() ) {
			return new WP_Error(
				'theme_not_found',
				__( 'The requested theme does not exist.', 'sg-ai-studio' )
			);
		}

		// Check if theme has errors.
		$theme_errors = $theme->errors();
		if ( is_wp_error( $theme_errors ) ) {
			return new WP_Error(
				'theme_has_errors',
				sprintf(
					/* translators: %s is the error message. */
					__( 'Theme has errors: %s', 'sg-ai-studio' ),
					$theme_errors->get_error_message()
				)
			);
		}

		// Validate theme requirements if the function exists.
		if ( function_exists( 'validate_theme_requirements' ) ) {
			$requirements = validate_theme_requirements( $theme );
			if ( is_wp_error( $requirements ) ) {
				return new WP_Error(
					'theme_requirements_not_met',
					sprintf(
						/* translators: %s is the error message. */
						__( 'Theme requirements not met: %s', 'sg-ai-studio' ),
						$requirements->get_error_message()
					)
				);
			}
		}

		// For child themes, verify parent theme exists.
		if ( $theme->parent() ) {
			$parent_theme = $theme->parent();
			if ( ! $parent_theme->exists() ) {
				return new WP_Error(
					'parent_theme_not_found',
					sprintf(
						/* translators: %s is the parent theme name. */
						__( 'Parent theme not found: %s', 'sg-ai-studio' ),
						$theme->get_template()
					)
				);
			}
		}

		// Store the current theme for rollback if needed.
		$previous_theme      = wp_get_theme();
		$previous_stylesheet = get_option( 'stylesheet' );

		// Attempt to switch theme with error capture.
		// Since switch_theme() doesn't return errors, we need to wrap it
		// and verify the switch actually happened.
		try {
			// Suppress wp_die() by temporarily overriding the handler.
			add_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ), 1 );

			switch_theme( $stylesheet );

			// Remove the filter.
			remove_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ), 1 );

			// Verify the switch actually happened.
			$current_stylesheet = get_option( 'stylesheet' );
			if ( $current_stylesheet !== $stylesheet ) {
				// Rollback to previous theme.
				if ( $previous_stylesheet ) {
					switch_theme( $previous_stylesheet );
				}
				return new WP_Error(
					'theme_activation_failed',
					__( 'Theme activation failed. The theme may have fatal errors.', 'sg-ai-studio' )
				);
			}
		} catch ( Exception $e ) {
			// Rollback to previous theme.
			if ( $previous_stylesheet ) {
				switch_theme( $previous_stylesheet );
			}

			// Remove the filter if it's still there.
			remove_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ), 1 );

			return new WP_Error(
				'theme_activation_exception',
				sprintf(
					/* translators: %s is the error message. */
					__( 'Theme activation failed: %s', 'sg-ai-studio' ),
					$e->getMessage()
				)
			);
		}

		return true;
	}

	/**
	 * Custom wp_die handler that throws exceptions instead of dying.
	 *
	 * @return callable The custom handler.
	 */
	public function get_wp_die_handler() {
		return function ( $message, $title = '', $args = array() ) {
			if ( is_wp_error( $message ) ) {
				$message = $message->get_error_message();
			}
			throw new Exception( esc_html( $message ) );
		};
	}
}
