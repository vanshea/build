<?php

namespace SiteGround_Central\Rest\Controllers\V1;

class Rest_Themes extends Rest {
	/**
	 * Registers all routes for the themes.
	 *
	 * @since 3.0.0
	 */
	public function register_routes() {
		// Add the GET request.
		register_rest_route(
			'siteground-central/v1',
			'/themes/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_themes_data' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		register_rest_route(
			'siteground-central/v1',
			'/more-themes/',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_more_themes' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Retrieves all information about the themes page.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_themes_data() {
		// Prepare the tabs array.
		$tabs = array(
			'recommended' => $this->render_themes( 'recommended' ),
			'default'     => $this->render_themes( 'default' ),
			'upload'      => $this->render_upload(),
		);

		return self::send_response( $tabs );
	}

	/**
	 * Retrieves all themes based on a type and query.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $type Type of the themes to be returned.
	 * @param  array  $args Array with all the arguments for the themes.
	 *
	 * @return mixed        Array of themes.
	 */
	public function render_themes( $type, $args = array() ) {
		$args['per_page'] = 9;

		if ( ! empty( $args['searchType'] ) && 'search' === $args['searchType'] ) {
			$args['browse'] = 'popular';
		}

		if ( ! function_exists( 'themes_api' ) ) {
			include_once ABSPATH . '/wp-admin/includes/theme.php';
		}

		$themes = 'recommended' === $type ? $this->get_recommended_themes( $args ) : \themes_api( 'query_themes', $args );

		// Render each theme.
		foreach ( $themes->themes as $index => $theme ) {
			if ( 'recommended' === $type ) {
				$themes->themes[$index]['buttons_data'] = array(
					'button_text' => __( 'Install', 'siteground-wizard' ),
				);
			} else {
				$themes->themes[$index]->buttons_data = $this->get_buttons_data( $theme );
			}
		}

		return $themes->themes;
	}

	/**
	 * Check if theme is installed, if there is an update for the theme.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $theme The theme we are looping.
	 *
	 * @return string         The html content for the theme page.
	 */
	public function get_buttons_data( $theme ) {
		$installed_themes = array_map( 'strtolower', array_keys( \search_theme_directories() ) );
		// Define default status.
		$status = 'install';

		// Define default pdate status.
		$update_status = '';

		// Get info for theme.
		$installed_theme = \wp_get_theme( $theme->slug );

		if ( empty( $theme->version ) ) {
			$theme->version = 'latest_installed';
		}

		$customize_url = add_query_arg(
			array(
				'theme'  => urlencode( $theme->slug ),
				'return' => urlencode( admin_url( 'themes.php?page=sg-themes-install.php' ) ),
			),
			admin_url( 'customize.php' )
		);

		$activate_url = add_query_arg(
			array(
				'action'     => 'activate',
				'stylesheet' => urlencode( $theme->slug ),
			),
			self_admin_url( 'themes.php' )
		);

		// Prepare the install url.
		$install_url = add_query_arg(
			array(
				'action' => 'install-theme',
				'theme'  => $theme->slug,
			),
			self_admin_url( 'update.php' )
		);

		// Prepare the update url.
		$update_url = add_query_arg(
			array(
				'action' => 'upgrade-theme',
				'theme'  => $theme->slug,
			),
			self_admin_url( 'update.php' )
		);

		// Check if theme exist and its status.
		if ( $installed_theme->exists() ) {
			if ( version_compare( $installed_theme->get( 'Version' ), $theme->version, '>=' ) ) {
				$status = 'latest_installed';
			} else {
				$update_status = array(
					'update_button_text' => __( "Update", "siteground-wizard" ),
					'update_button_url'  => esc_url( wp_nonce_url( $update_url, 'upgrade-theme_' . $theme->slug ) ),
				);
			}
		}

		if ( in_array( $installed_theme->template, $installed_themes ) ) {
			$status = 'activate';
		}

		if ( $installed_theme->get_stylesheet() === wp_get_theme()->get_stylesheet() ) {
			$status = 'customize';
		}

		switch ( $status ) {
			case 'latest_installed':
				$text = __( "Installed", "siteground-wizard" );
				break;
			case 'activate':
				$text = __( "Activate", "siteground-wizard" );
				$url = esc_url( wp_nonce_url( $activate_url, 'switch-theme_' . $theme->slug ) );
				break;
			case 'customize':
				$text = __( "Live Preview", "siteground-wizard" );
				$url = esc_url( $customize_url );
				break;
			case 'install':
			default:
				$text = __( "Install", "siteground-wizard" );
				$url = esc_url( wp_nonce_url( $install_url, 'install-theme_' . $theme->slug ) );
				$nonce = wp_create_nonce( 'updates', 'install-theme_' . $theme->slug );
				break;
		}

		return array(
			'button_text' => $text,
			'button_url'  => html_entity_decode( $url ),
			'nonce'       => $nonce,
			'update'      => $update_status,
		);
	}

	/**
	 * Retrieves themes based on arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request     Request object.
	 *
	 * @return WP_Error|WP_REST_Response  List of themes based on the request's arguments.
	 */
	public function get_more_themes( $request ) {
		$args = $request->get_params( $request );
		return self::send_response( $this->render_themes( 'default', $args ) );
	}

	/**
	 * Retrieves all recommended themes from the SG endpoint.
	 *
	 * @since 3.0.0
	 *
	 * @return object All the theme items in the SG endpoint.
	 */
	public function get_recommended_themes() {
		$themes = (object) array();

		$response = wp_remote_get( \SiteGround_Central\SG_WPAPI_URL . '/sg-themes', array( 'sslverify' => false ) );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$themes->themes = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		return $themes;
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
				'title' => __( 'Upload Your Theme', 'siteground-wizard' ),
				'subtitle' => __( 'If you have a theme in a .zip format, you may install it by uploading it here.', 'siteground-wizard' ),
				'file_chooser_text' => __( 'Choose file', 'siteground-wizard' ),
				'form_url' => self_admin_url( 'update.php?action=upload-theme' ),
				'theme_nonce' => wp_create_nonce( 'theme-upload' ),
			)
		);
	}
}