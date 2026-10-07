<?php
/**
 * Plugins API class for managing WordPress plugins via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for plugin operations.
 */
class Plugins extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'plugins';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for listing and installing plugins.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => array( $this, 'get_plugins_permissions_check' ),
					'args'                => $this->get_plugins_args(),
					'description'         => 'Retrieves a list of all installed plugins with their statuses.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_plugin' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'description'         => 'Installs a new plugin from WordPress.org.',
				),
				'schema' => array( $this, 'get_plugin_schema' ),
			)
		);

		// Register endpoint for retrieving, activating, deactivating, updating, and deleting a single plugin.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<slug>(?!batch$)[^/]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_plugin' ),
					'permission_callback' => array( $this, 'get_plugins_permissions_check' ),
					'args'                => array(
						'slug' => array(
							'description' => 'Unique slug for the plugin.',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves detailed information about a specific plugin.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'toggle_plugin' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => array(
						'slug'         => array(
							'description' => 'Unique slug for the plugin.',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Activates or deactivates a specific plugin.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_plugin' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => array(
						'slug' => array(
							'description' => 'Unique slug for the plugin.',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Updates a specific plugin to the latest version.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_plugin' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => array(
						'slug' => array(
							'description' => 'Unique slug for the plugin.',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific plugin.',
				),
				'schema' => array( $this, 'get_plugin_schema' ),
			)
		);

		// Register endpoint for batch operations on plugins.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_install_plugins' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => $this->get_batch_install_plugins_args(),
					'description'         => 'Installs multiple plugins in a single request.',
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'batch_toggle_plugins' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => $this->get_batch_activate_plugins_args(),
					'description'         => 'Activates or deactivates multiple plugins in a single request.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_plugins' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => $this->get_batch_update_plugins_args(),
					'description'         => 'Updates multiple plugins in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_plugins' ),
					'permission_callback' => array( $this, 'manage_plugins_permissions_check' ),
					'args'                => $this->get_batch_delete_plugins_args(),
					'description'         => 'Deletes multiple plugins in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}


	/**
	 * Check if a user has permission to get plugins information
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_plugins_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to manage plugins
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to manage items, WP_Error object otherwise.
	 */
	public function manage_plugins_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for retrieving plugins
	 *
	 * @return array
	 */
	protected function get_plugins_args() {
		return array(
			'status' => array(
				'description' => 'Filter plugins by status.',
				'type'        => 'string',
				'enum'        => array( 'active', 'inactive', 'all' ),
				'default'     => 'all',
				'required'    => false,
			),
			'search' => array(
				'description' => 'Search term to filter plugins by name.',
				'type'        => 'string',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for installing a plugin
	 *
	 * @return array
	 */
	protected function get_install_plugin_args() {
		return array(
			'slug'             => array(
				'description' => 'The plugin slug from WordPress.org.',
				'type'        => 'string',
				'required'    => false,
			),
			'activate'         => array(
				'description' => 'Whether to activate the plugin after installation.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'network_activate' => array(
				'description' => 'Whether to network activate the plugin after installation (multisite only).',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for batch installing plugins
	 *
	 * @return array
	 */
	protected function get_batch_install_plugins_args() {
		return array(
			'plugins' => array(
				'description' => 'List of plugins to install.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'slug'             => array(
							'description' => 'The plugin slug from WordPress.org.',
							'type'        => 'string',
							'required'    => false,
						),
						'activate'         => array(
							'description' => 'Whether to activate the plugin after installation.',
							'type'        => 'boolean',
							'default'     => false,
							'required'    => false,
						),
						'network_activate' => array(
							'description' => 'Whether to network activate the plugin after installation (multisite only).',
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
	 * Get arguments for batch activating plugins
	 *
	 * @return array
	 */
	protected function get_batch_activate_plugins_args() {
		return array(
			'plugins'      => array(
				'description' => 'List of plugins to toggle activation status.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'slug'   => array(
							'description' => 'Plugin slug.',
							'type'        => 'string',
							'required'    => true,
						),
						'status' => array(
							'description' => 'The desired status of the plugin (active or inactive).',
							'type'        => 'string',
							'enum'        => array( 'active', 'inactive' ),
							'required'    => true,
						),
					),
				),
				'required'    => true,
			),
			'network_wide' => array(
				'description' => 'Whether to apply the status changes network-wide (multisite only).',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for batch updating plugins
	 *
	 * @return array
	 */
	protected function get_batch_update_plugins_args() {
		return array(
			'plugins' => array(
				'description' => 'List of plugin slugs to update.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting plugins
	 *
	 * @return array
	 */
	protected function get_batch_delete_plugins_args() {
		return array(
			'plugins' => array(
				'description' => 'List of plugin slugs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get plugin schema
	 *
	 * @return array
	 */
	public function get_plugin_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'plugin',
			'type'       => 'object',
			'properties' => array(
				'name'           => array(
					'description' => 'The name of the plugin.',
					'type'        => 'string',
				),
				'slug'           => array(
					'description' => 'The slug of the plugin.',
					'type'        => 'string',
				),
				'plugin_file'    => array(
					'description' => 'The path to the main plugin file relative to the plugins directory.',
					'type'        => 'string',
				),
				'version'        => array(
					'description' => 'The version of the plugin.',
					'type'        => 'string',
				),
				'description'    => array(
					'description' => 'The description of the plugin.',
					'type'        => 'string',
				),
				'author'         => array(
					'description' => 'The author of the plugin.',
					'type'        => 'string',
				),
				'author_uri'     => array(
					'description' => 'The website of the plugin author.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'plugin_uri'     => array(
					'description' => 'The website of the plugin.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'status'         => array(
					'description' => 'The status of the plugin.',
					'type'        => 'string',
					'enum'        => array( 'active', 'inactive', 'network-active' ),
				),
				'requires_wp'    => array(
					'description' => 'Minimum required WordPress version.',
					'type'        => 'string',
				),
				'requires_php'   => array(
					'description' => 'Minimum required PHP version.',
					'type'        => 'string',
				),
				'network'        => array(
					'description' => 'Whether the plugin can be network activated.',
					'type'        => 'boolean',
				),
				'update_version' => array(
					'description' => 'The available update version, if an update is available.',
					'type'        => 'string',
				),
			),
		);
	}

	/**
	 * Get batch schema
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
	 * Get a list of plugins
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_plugins( $request ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins                = get_plugins();
		$active_plugins         = get_option( 'active_plugins', array() );
		$network_active_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', array() ) : array();

		$status = $request['status'] ? $request['status'] : 'all';
		$search = $request['search'] ? sanitize_text_field( $request['search'] ) : '';

		if ( ! function_exists( '\wp_clean_plugins_cache' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( '\wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}
		// Force refresh of plugin update information.
		\wp_clean_plugins_cache();
		\delete_site_transient( 'update_plugins' );
		\wp_update_plugins();

		// Get available plugin updates.
		$update_plugins = get_site_transient( 'update_plugins' );

		$data = array();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			// Skip if not matching search term.
			if ( ! empty( $search ) && stripos( $plugin_data['Name'], $search ) === false ) {
				continue;
			}

			$is_active         = in_array( $plugin_file, $active_plugins, true );
			$is_network_active = is_multisite() && array_key_exists( $plugin_file, $network_active_plugins );

			// Filter by status if specified.
			if ( 'active' === $status && ! $is_active && ! $is_network_active ) {
				continue;
			}
			if ( 'inactive' === $status && ( $is_active || $is_network_active ) ) {
				continue;
			}

			// Set plugin status.
			if ( $is_active ) {
				$plugin_status = 'active';
			} elseif ( $is_network_active ) {
				$plugin_status = 'network-active';
			} else {
				$plugin_status = 'inactive';
			}

			// Get plugin directory name (slug).
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			$plugin_info = array(
				'name'         => $plugin_data['Name'],
				'slug'         => $plugin_slug,
				'plugin_file'  => $plugin_file,
				'version'      => $plugin_data['Version'],
				'description'  => $plugin_data['Description'],
				'author'       => $plugin_data['Author'],
				'author_uri'   => $plugin_data['AuthorURI'],
				'plugin_uri'   => $plugin_data['PluginURI'],
				'status'       => $plugin_status,
				'requires_wp'  => isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '',
				'requires_php' => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
				'network'      => isset( $plugin_data['Network'] ) ? (bool) $plugin_data['Network'] : false,
			);

			// Add update_version if an update is available.
			if ( ! empty( $update_plugins->response ) && isset( $update_plugins->response[ $plugin_file ] ) ) {
				$plugin_info['update_version'] = $update_plugins->response[ $plugin_file ]->new_version;
			}

			$data[] = $plugin_info;
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
	 * Get a single plugin
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_plugin( $request ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$slug                   = $request['slug'];
		$plugins                = get_plugins();
		$active_plugins         = get_option( 'active_plugins', array() );
		$network_active_plugins = is_multisite() ? get_site_option( 'active_sitewide_plugins', array() ) : array();

		// Find the plugin by slug.
		$plugin_file = null;
		$plugin_data = null;

		foreach ( $plugins as $file => $data ) {
			$plugin_slug = dirname( $file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $file, '.php' );
			}

			if ( $plugin_slug === $slug ) {
				$plugin_file = $file;
				$plugin_data = $data;
				break;
			}
		}

		if ( ! $plugin_file ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Set plugin status.
		$is_active         = in_array( $plugin_file, $active_plugins, true );
		$is_network_active = is_multisite() && array_key_exists( $plugin_file, $network_active_plugins );

		if ( $is_active ) {
			$plugin_status = 'active';
		} elseif ( $is_network_active ) {
			$plugin_status = 'network-active';
		} else {
			$plugin_status = 'inactive';
		}

		if ( ! function_exists( '\wp_clean_plugins_cache' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( '\wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-includes/update.php';
		}

		// Force refresh of plugin update information.
		\wp_clean_plugins_cache();
		\delete_site_transient( 'update_plugins' );
		\wp_update_plugins();

		// Get available plugin updates.
		$update_plugins = get_site_transient( 'update_plugins' );

		$data = array(
			'name'         => $plugin_data['Name'],
			'slug'         => $slug,
			'plugin_file'  => $plugin_file,
			'version'      => $plugin_data['Version'],
			'description'  => $plugin_data['Description'],
			'author'       => $plugin_data['Author'],
			'author_uri'   => $plugin_data['AuthorURI'],
			'plugin_uri'   => $plugin_data['PluginURI'],
			'status'       => $plugin_status,
			'requires_wp'  => isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '',
			'requires_php' => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
			'network'      => isset( $plugin_data['Network'] ) ? (bool) $plugin_data['Network'] : false,
		);

		// Add update_version if an update is available.
		if ( ! empty( $update_plugins->response ) && isset( $update_plugins->response[ $plugin_file ] ) ) {
			$data['update_version'] = $update_plugins->response[ $plugin_file ]->new_version;
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
	 * Install a plugin
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function install_plugin( $request ) {
		// Check if slug is provided.
		if ( empty( $request['slug'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please provide either a plugin slug.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Include necessary files.
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Setup upgrader.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

		// Install the plugin.
		$result = false;

		if ( ! empty( $request['slug'] ) ) {
			// Install from WordPress.org.
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $request['slug'],
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
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
					'message' => __( 'Plugin installation failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Get the installed plugin file.
		$plugin_file = $upgrader->plugin_info();

		// Activate the plugin if requested.
		$activated = false;
		if ( $request['activate'] ) {
			$network_wide    = ! empty( $request['network_activate'] ) && is_multisite();
			$activate_result = activate_plugin( $plugin_file, '', $network_wide );

			if ( is_wp_error( $activate_result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $activate_result->get_error_message(),
					),
					200
				);
			}

			$activated = true;
		}

		// Get plugin data.
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Get plugin directory name (slug).
		$plugin_slug = dirname( $plugin_file );
		if ( '.' === $plugin_slug ) {
			$plugin_slug = basename( $plugin_file, '.php' );
		}

		// Log the activity.
		if ( $activated ) {
			// translators: %s is the plugin name.
			$log_description = sprintf( __( 'Plugin Installed: %s (Activated)', 'sg-ai-studio' ), $plugin_data['Name'] );
		} else {
			// translators: %s is the plugin name.
			$log_description = sprintf( __( 'Plugin Installed: %s', 'sg-ai-studio' ), $plugin_data['Name'] );
		}
		Activity_Log_Helper::add_log_entry( 'Plugins', $log_description );

		$data = array(
			'name'         => $plugin_data['Name'],
			'slug'         => $plugin_slug,
			'plugin_file'  => $plugin_file,
			'version'      => $plugin_data['Version'],
			'description'  => $plugin_data['Description'],
			'author'       => $plugin_data['Author'],
			'author_uri'   => $plugin_data['AuthorURI'],
			'plugin_uri'   => $plugin_data['PluginURI'],
			'status'       => $activated ? 'active' : 'inactive',
			'requires_wp'  => isset( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : '',
			'requires_php' => isset( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : '',
			'network'      => isset( $plugin_data['Network'] ) ? (bool) $plugin_data['Network'] : false,
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
				'message' => __( 'Plugin installed successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			201
		);
	}

	/**
	 * Toggle a plugin's activation status.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function toggle_plugin( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$slug         = $request['slug'];
		$status       = $request['status'];
		$network_wide = ! empty( $request['network_wide'] ) && is_multisite();

		if ( 'sg-ai-studio' === $slug ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Not allowed', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Find the plugin file by slug.
		$plugin_file = $this->get_plugin_file_by_slug( $slug );

		if ( ! $plugin_file ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$is_active = is_plugin_active( $plugin_file ) || ( $network_wide && is_plugin_active_for_network( $plugin_file ) );

		// Check if the plugin is already in the requested status.
		if ( 'active' === $status && $is_active ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin is already active.', 'sg-ai-studio' ),
				),
				200
			);
		} elseif ( 'inactive' === $status && ! $is_active ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin is already inactive.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Toggle the plugin status.
		$result = null;
		if ( 'active' === $status ) {
			// Activate the plugin.
			$result     = activate_plugin( $plugin_file, '', $network_wide );
			$message    = __( 'Plugin activated successfully.', 'sg-ai-studio' );
			$new_status = $network_wide ? 'network-active' : 'active';
		} elseif ( 'inactive' === $status ) {
			// Deactivate the plugin.
			deactivate_plugins( $plugin_file, false, $network_wide );
			$message    = __( 'Plugin deactivated successfully.', 'sg-ai-studio' );
			$new_status = 'inactive';
		} else {
			$message = __( 'Plugin status not changed.', 'sg-ai-studio' );
		}

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				200
			);
		}

		// Get updated plugin data.
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Log the activity.
		if ( 'active' === $status ) {
			// translators: %s is the plugin name.
			$log_description = sprintf( __( 'Plugin Activated: %s', 'sg-ai-studio' ), $plugin_data['Name'] );
		} else {
			// translators: %s is the plugin name.
			$log_description = sprintf( __( 'Plugin Deactivated: %s', 'sg-ai-studio' ), $plugin_data['Name'] );
		}
		Activity_Log_Helper::add_log_entry( 'Plugins', $log_description );

		$data = array(
			'name'        => $plugin_data['Name'],
			'slug'        => $slug,
			'plugin_file' => $plugin_file,
			'version'     => $plugin_data['Version'],
			'status'      => $new_status,
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
				'message' => $message,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Update a plugin.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_plugin( $request ) {
		if ( ! function_exists( '\et_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$slug = $request['slug'];

		if ( 'sg-ai-studio' === $slug ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Not allowed', 'sg-ai-studio' ),
				),
				403
			);
		}
		// Find the plugin file by slug.
		$plugin_file = $this->get_plugin_file_by_slug( $slug );

		if ( ! $plugin_file ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get current plugin data.
		$plugin_data_before = \get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Include necessary files.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';

		// Force refresh of plugin update information.
		\wp_clean_plugins_cache();
		\delete_site_transient( 'update_plugins' );
		\wp_update_plugins();

		// Check if plugin is active before update.
		$was_active         = \is_plugin_active( $plugin_file );
		$was_network_active = \is_multisite() && \is_plugin_active_for_network( $plugin_file );

		// Setup upgrader.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

		// Perform the update.
		$result = $upgrader->upgrade( $plugin_file );

		// Check for update errors.
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

		if ( is_null( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin is already up to date.', 'sg-ai-studio' ),
				),
				200
			);
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin update failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Re-activate plugin if it was active before.
		if ( $was_active || $was_network_active ) {
			$network_wide = $was_network_active;
			\activate_plugin( $plugin_file, '', $network_wide, true );
		}

		// Get updated plugin data.
		$plugin_data_after = \get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Determine status.
		if ( $was_active ) {
			$status = 'active';
		} elseif ( $was_network_active ) {
			$status = 'network-active';
		} else {
			$status = 'inactive';
		}

		// Log the activity.
		$log_description = sprintf(
			/* translators: %1$s is the plugin name, %2$s is the old version, %3$s is the new version. */
			__( 'Plugin Updated: %1$s (from version %2$s to %3$s)', 'sg-ai-studio' ),
			$plugin_data_after['Name'],
			$plugin_data_before['Version'],
			$plugin_data_after['Version']
		);
		Activity_Log_Helper::add_log_entry( 'Plugins', $log_description );

		$data = array(
			'name'         => $plugin_data_after['Name'],
			'slug'         => $slug,
			'plugin_file'  => $plugin_file,
			'version'      => $plugin_data_after['Version'],
			'description'  => $plugin_data_after['Description'],
			'author'       => $plugin_data_after['Author'],
			'author_uri'   => $plugin_data_after['AuthorURI'],
			'plugin_uri'   => $plugin_data_after['PluginURI'],
			'status'       => $status,
			'requires_wp'  => isset( $plugin_data_after['RequiresWP'] ) ? $plugin_data_after['RequiresWP'] : '',
			'requires_php' => isset( $plugin_data_after['RequiresPHP'] ) ? $plugin_data_after['RequiresPHP'] : '',
			'network'      => isset( $plugin_data_after['Network'] ) ? (bool) $plugin_data_after['Network'] : false,
			'old_version'  => $plugin_data_before['Version'],
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
				'message' => __( 'Plugin updated successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Delete a plugin.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_plugin( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$slug = $request['slug'];

		if ( 'sg-ai-studio' === $slug ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Not allowed', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Find the plugin file by slug.
		$plugin_file = $this->get_plugin_file_by_slug( $slug );

		if ( ! $plugin_file ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Plugin not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get plugin data before deletion.
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );

		// Check if the plugin is active.
		if ( is_plugin_active( $plugin_file ) || is_plugin_active_for_network( $plugin_file ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Cannot delete an active plugin. Please deactivate it first.', 'sg-ai-studio' ),
				),
				200
			);
		}

		// Delete the plugin.
		$result = \delete_plugins( array( $plugin_file ) );

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
					'message' => __( 'Plugin deletion failed.', 'sg-ai-studio' ),
				),
				200
			);
		}

		$data = array(
			'name'        => $plugin_data['Name'],
			'slug'        => $slug,
			'plugin_file' => $plugin_file,
		);

		// Log the activity.
		/* translators: %s is the plugin name. */
		$log_description = sprintf( __( 'Plugin Deleted: %s', 'sg-ai-studio' ), $plugin_data['Name'] );
		Activity_Log_Helper::add_log_entry( 'Plugins', $log_description );

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
				'message' => __( 'Plugin deleted successfully.', 'sg-ai-studio' ),
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Batch install plugins.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_install_plugins( $request ) {
		$plugins = $request['plugins'];
		$results = array();
		$errors  = array();

		foreach ( $plugins as $plugin ) {
			// Create a new request for each plugin.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add plugin data to the request.
			if ( ! empty( $plugin['slug'] ) ) {
				$sub_request->set_param( 'slug', $plugin['slug'] );
			}
			if ( isset( $plugin['activate'] ) ) {
				$sub_request->set_param( 'activate', $plugin['activate'] );
			}
			if ( isset( $plugin['network_activate'] ) ) {
				$sub_request->set_param( 'network_activate', $plugin['network_activate'] );
			}

			// Install the plugin.
			$response = $this->install_plugin( $sub_request );

			$identifier = ! empty( $plugin['slug'] ) ? $plugin['slug'] : '';

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
	 * Batch toggle plugins activation status
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_toggle_plugins( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$plugins      = $request['plugins'];
		$network_wide = ! empty( $request['network_wide'] ) && is_multisite();
		$results      = array();
		$errors       = array();

		foreach ( $plugins as $plugin ) {
			// Create a new request for each plugin.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base . '/' . $plugin['slug'] );
			$sub_request->set_param( 'slug', $plugin['slug'] );
			$sub_request->set_param( 'status', $plugin['status'] );
			$sub_request->set_param( 'network_wide', $network_wide );

			// Toggle the plugin status.
			$response = $this->toggle_plugin( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $plugin['slug'] ] = $response->get_data();
			} else {
				$results[ $plugin['slug'] ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Group results by status (activated and deactivated).
		$activated   = array();
		$deactivated = array();

		foreach ( $results as $slug => $data ) {
			if ( 'active' === $data['status'] || 'network-active' === $data['status'] ) {
				$activated[ $slug ] = $data;
			} else {
				$deactivated[ $slug ] = $data;
			}
		}

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'activated'   => $activated,
					'deactivated' => $deactivated,
					'errors'      => $errors,
				),
			),
			$success ? 200 : 207
		);
	}

	/**
	 * Batch delete plugins
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_plugins( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$plugins = $request['plugins'];
		$results = array();
		$errors  = array();

		foreach ( $plugins as $plugin_slug ) {
			// Create a new request for each plugin.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $plugin_slug );
			$sub_request->set_param( 'slug', $plugin_slug );

			// Delete the plugin.
			$response = $this->delete_plugin( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $plugin_slug ] = $response->get_data();
			} else {
				$results[ $plugin_slug ] = $response->get_data()['data'];
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
	 * Batch update plugins
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_plugins( $request ) {
		$plugins = $request['plugins'];
		$results = array();
		$errors  = array();

		foreach ( $plugins as $plugin_slug ) {
			// Create a new request for each plugin.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $plugin_slug );
			$sub_request->set_param( 'slug', $plugin_slug );

			// Update the plugin.
			$response = $this->update_plugin( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $plugin_slug ] = $response->get_data();
			} else {
				$results[ $plugin_slug ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'updated' => $results,
					'errors'  => $errors,
				),
			),
			$success ? 200 : 207
		);
	}

	/**
	 * Get plugin file by slug
	 *
	 * @param string $slug Plugin slug.
	 * @return string|false Plugin file path or false if not found.
	 */
	private function get_plugin_file_by_slug( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_slug = dirname( $plugin_file );
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			if ( $plugin_slug === $slug ) {
				return $plugin_file;
			}
		}

		return false;
	}

}
