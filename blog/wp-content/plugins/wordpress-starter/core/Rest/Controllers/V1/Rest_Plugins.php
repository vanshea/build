<?php

namespace SiteGround_Central\Rest\Controllers\V1;

use SiteGround_Central\Traits\Sco_Exclude_Trait;

class Rest_Plugins extends Rest {
	use Sco_Exclude_Trait;

	/**
	 * Registers all routes for the plugins.
	 *
	 * @since 3.0.0
	 */
	public function register_routes() {
		// Add the GET request.
		register_rest_route(
			'siteground-central/v1',
			'/plugins/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plugins_data' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		register_rest_route(
			'siteground-central/v1',
			'/more-plugins/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_more_plugins' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Retrieves all information about the plugins page.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_plugins_data() {
		$tabs = array(
			'recommended' => $this->render_plugins( 'recommended' ),
			'default'     => $this->render_plugins( 'default' ),
			'upload'      => $this->render_upload(),
		);

		return self::send_response( $tabs );
	}

	/**
	 * Retrieves all installed plugins.
	 *
	 * @since 3.0.0
	 *
	 * @return array All installed plugins on the site.
	 */
	public function get_installed_plugins() {
		$plugins = array();

		$plugin_info = get_site_transient( 'update_plugins' );
		if ( isset( $plugin_info->no_update ) ) {
			foreach ( $plugin_info->no_update as $plugin ) {
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		if ( isset( $plugin_info->response ) ) {
			foreach ( $plugin_info->response as $plugin ) {
				$plugins[ $plugin->slug ] = $plugin;
			}
		}

		return $plugins;
	}

	/**
	 * Retrieves all plugins based on a type and query.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $type Type of the plugins to be returned.
	 * @param  array  $args Array with all the arguments for the plugins.
	 *
	 * @return mixed        Array of plugins.
	 */
	public function render_plugins( $type, $args = array() ) {
		$args = array_merge(
			$args,
			array(
				'per_page'          => 6,
				'installed_plugins' => array_keys( $this->get_installed_plugins() ),
			)
		);

		if ( ! function_exists( 'plugins_api' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		}

		$plugins = 'recommended' === $type ? $this->get_recommended_plugins( $args ) : \plugins_api( 'query_plugins', $args );

		// Render each plugin.
		foreach ( $plugins->plugins as $index => $plugin ) {
			if (
				! empty( $plugin['category'] ) &&
				( 'system' === $plugin['category'] )
			) {
				unset( $plugins->plugins[ $index ] );
				continue;
			}

			if ( ! empty( $plugin['short_description'] ) ) {
				$plugins->plugins[ $index ]['short_description'] = $plugin['short_description'];
			} else {
				$user_locale = get_user_locale( wp_get_current_user() );

				switch ( $user_locale ) {
					case 'es_ES':
						$key = 2;
						break;
					case 'it_IT':
						$key = 1;
						break;
					case 'de_DE':
						$key = 3;
						break;
					default:
						$key = 0;
						break;
				}

				$plugins->plugins[ $index ]['short_description'] = $plugin['description'][ $key ]['description'];
			}

			$plugins->plugins[ $index ]['install_button_text'] = $this->maybe_installed( $plugin );
			$plugins->plugins[ $index ]['compatible'] = $this->check_compatibility( $plugin );
			$plugins->plugins[ $index ]['learn_more_url'] = \self_admin_url( 'plugin-install.php?tab=plugin-information&amp;sg-central-preview=1&amp;plugin=' . $plugin['slug'] . '&amp;TB_iframe=true&amp;width=780&amp;height=680' );
			// Decode HTML entities from every field of the plugin, in order to clean up the output.
			array_walk_recursive(
				$plugins->plugins[ $index ],
				function ( &$value ) {
					$value = html_entity_decode( $value, ENT_QUOTES );
				}
			);
		}
		return $plugins->plugins;
	}

	/**
	 * Check if plugin is installed, if there is an update for the plugin
	 *
	 * @since  3.0.0
	 *
	 * @param  array $plugin  The plugin we are looping.
	 *
	 * @return array          Array with the button text and url.
	 */
	public function maybe_installed( $plugin ) {
		if ( empty( $plugin['version'] ) ) {
			$plugin['version'] = 'latest';
		}

		$plugin_info = install_plugin_install_status( $plugin );

		$activate_url = admin_url( 'admin-ajax.php?action=siteground_wizard_activate_plugin&plugin=' . $plugin['slug'] );

		switch ( $plugin_info['status'] ) {
			case 'update_available':
				$update_url = admin_url( 'admin-ajax.php?action=siteground_wizard_update_plugin&plugin=' . $plugin['slug'] );
				$url = add_query_arg( 'nonce', wp_create_nonce( $plugin['slug'], $update_url ), $update_url );
				$text = __( 'Update', 'siteground-wizard' );
				break;
			case 'install':
				$install_url = admin_url( 'admin-ajax.php?action=siteground_wizard_install_plugin&plugin=' . $plugin['slug'] );
				$url = add_query_arg( 'nonce', wp_create_nonce( $plugin['slug'], $install_url ), $install_url );
				$text = __( 'Install', 'siteground-wizard' );
				break;
			case 'latest_installed':
			case 'newer_installed':
				// Check if the plugin is inactive.
				if ( false === is_plugin_active( $plugin_info['file'] ) ) {
					$text = __( 'Activate', 'siteground-wizard' );
					$url = add_query_arg( 'nonce', wp_create_nonce( $plugin['slug'], $activate_url ), $activate_url );
					break;
				}
				$text = __( 'Active', 'siteground-wizard' );
				break;
		}

		return array(
			'button_text' => $text,
			'button_url'  => $url,
		);
	}

	/**
	 * Retrieves plugins based on arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request     Request object.
	 *
	 * @return WP_Error|WP_REST_Response  List of plugins based on the request's arguments.
	 */
	public function get_more_plugins( $request ) {
		$args = $request->get_params( $request );
		return self::send_response( $this->render_plugins( 'default', $args ) );
	}

	/**
	 * Retrieves all recommended plugins from the SG endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return object All the plugin items in the SG endpoint.
	 */
	public function get_recommended_plugins() {
		$plugins = (object) array();
		$response = wp_remote_get( \SiteGround_Central\SG_WPAPI_URL . '/sg-plugins', array( 'sslverify' => false ) );

		// Bail if we do not retrieve a response.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $plugins;
		}

		// Decode the response.
		$plugins_list = json_decode( wp_remote_retrieve_body( $response ), true );

		$plugins_list = $this->maybe_exclude_items(
			$this->sco_plugin_excludes,
			$plugins_list
		);

		$plugins_list = $this->maybe_exclude_items_for_lang(
			$this->lang_plugin_excludes,
			$plugins_list
		);

		$plugins->plugins = $plugins_list;

		return $plugins;
	}

	/**
	 * Helps with rendering of the Upload tab.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_Error|\WP_REST_Response Array with the fields values and URLs.
	 */
	public function render_upload() {
		return self::send_response(
			array(
				'install_button_text' => __( 'Install Now', 'siteground-wizard' ),
				'title' => __( 'Upload Your Plugin', 'siteground-wizard' ),
				'subtitle' => __( 'If you have a plugin in a .zip format, you may install it by uploading it here.', 'siteground-wizard' ),
				'file_chooser_text' => __( 'Choose file', 'siteground-wizard' ),
				'form_url' => self_admin_url( 'update.php?action=upload-plugin' ),
				'plugin_nonce' => wp_create_nonce( 'plugin-upload' ),
			)
		);
	}

	/**
	 * Check if plugins is compatible with the current WP version.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $plugin Plugin info.
	 *
	 * @return bool         If plugin is compatible
	 */
	public function check_compatibility( $plugin ) {
		$requires_wp  = isset( $plugin['requires'] ) ? $plugin['requires'] : null;

		$compatible_wp  = is_wp_version_compatible( $requires_wp );
		$tested_wp      = ( empty( $plugin['tested'] ) || version_compare( get_bloginfo( 'version' ), $plugin['tested'], '<=' ) );

		if ( ! $tested_wp ) {
			return __( '<b class="sg-with-color sg-with-color--color-warning">Untested</b> with your version of WordPress', 'siteground-wizard' );
		} elseif ( ! $compatible_wp ) {
			return __( '<b>Incompatible</b> with your version of WordPress', 'siteground-wizard' );
		} else {
			return __( '<b>Compatible</b> with your version of WordPress', 'siteground-wizard' );
		}
	}

}
