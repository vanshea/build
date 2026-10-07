<?php
namespace SiteGround_Central\ThirdParty;

use SiteGround_Central\Site_Tools_Client\Site_Tools_Client;

class ThirdParty {
	/**
	 * Get the affiliate link, based on company id.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $slug The plugin/theme slug.
	 *
	 * @return bool|string  The affliate link if found. False otherwise.
	 */
	public static function get_affiliate_link( $slug ) {
		$sco_id  = get_option( 'sco_id', '4' );
		$content = file_get_contents( plugin_dir_path( __FILE__ ) . 'config/affiliate-links.json' );
		$links   = json_decode( $content, true );

		if ( ! array_key_exists( $slug, $links ) ) {
			return false;
		}

		if ( ! empty( $links[ $slug ][ $sco_id ] ) ) {
			return $links[ $slug ][ $sco_id ];
		}

		if ( ! empty( $links[ $slug ]['1'] ) ) {
			return $links[ $slug ]['1'];
		}

		return false;
	}

	/**
	 * Change WPForms upgrede link.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $url The upgrade url.
	 *
	 * @return string      Modified url.
	 */
	public function change_wpforms_upgrade_link( $url ) {
		$new_url = $this->get_affiliate_link( 'wpforms' );

		// Return the orignal url if the new is not found.
		if ( false === $new_url ) {
			return $url;
		}

		return $new_url;
	}

	/**
	 * Change Neve affiliate link.
	 *
	 * @since  3.0.0
	 *
	 * @return string The new upgrade link.
	 */
	public function change_neve_affiliate_link( $url ) {
		$new_url = $this->get_affiliate_link( 'neve' );

		// Return the orignal url if the new is not found.
		if ( false === $new_url ) {
			return $url;
		}

		return $new_url;
	}

	/**
	 * Change Neve affiliate link
	 *
	 * @since  3.0.0
	 *
	 * @param array $config The theme config.
	 *
	 * @return array The config with affiliate upgrade link.
	 */
	public function change_neve_affiliate_link_config( $config ) {
		$new_url = $this->get_affiliate_link( 'neve' );

		// Change the link.
		if ( false !== $new_url ) {
			$config['pro_link'] = $new_url;
		}
		return $config;
	}

	/**
	 * Remove Neve theme useful plugins tab
	 *
	 * @since  3.0.0
	 *
	 * @param  array $config The theme config.
	 *
	 * @return array         Modified config.
	 */
	public function remove_neve_useful_plugins( $config ) {
		unset( $config['useful_plugins'] );

		return $config;
	}

	/**
	 * Change Monsterinsights share a sale id.
	 *
	 * @since  3.0.0
	 *
	 * @return string      Modified url.
	 */
	public function change_monsterinsights_shareasale_id() {
		return 1;
	}

	/**
	 * Change Monsterinsights share a sale link.
	 *
	 * @since  3.3.3
	 *
	 * @return string      Modified url.
	 */
	public function change_monsterinsights_shareasale_link($link) {
		$link = $this->get_affiliate_link( 'google-analytics-for-wordpress' );
		return $this->get_affiliate_link( 'google-analytics-for-wordpress' );
	}

	/**
	 * Change Optinmonster upgrade link.
	 *
	 * @since  3.0.0
	 *
	 * @return string      Modified url.
	 */
	public function change_optin_monster_action_link() {
		return $this->get_affiliate_link( 'optinmonster' );
	}

	/**
	 * Change Envira Gallery upgrade link.
	 *
	 * @since  3.0.0
	 *
	 * @return string      Modified url.
	 */
	public function change_envira_shareasale_link() {
		return $this->get_affiliate_link( 'envira-gallery-lite' );
	}

	/**
	 * Change Astra upgrade link.
	 *
	 * @since  3.0.0
	 *
	 * @return string      Modified url.
	 */
	public function change_astra_affiliate_link() {
		return $this->get_affiliate_link( 'astra' );
	}

	/**
	 * Change TranslatePress affiliate link.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $link The url for the affiliate campaing.
	 *
	 * @return string The modified url containing the affiliate id.
	 */
	public function change_trp_affiliate_link( $link ) {
		// Get the affiliate id.
		$affiliate_id = $this->get_affiliate_link( 'translatepress-multilingual' );

		// Return the original link if affiliate id is not found.
		if ( empty( $affiliate_id ) ) {
			return $link;
		}

		return esc_url( add_query_arg( 'ref', $affiliate_id, $link ) );
	}

	/**
	 * Change All In One SEO affiliate link.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $link The url for the affiliate campaign.
	 *
	 * @return string      Modified url.
	 */
	public function change_aioseo_affiliate_link( $link ) {
		// Get the affiliate id.
		$affiliate_link = $this->get_affiliate_link( 'all-in-one-seo-pack' );

		if ( empty( $affiliate_link ) ) {
			return $link;
		}

		return $affiliate_link;
	}

	/**
	 * Change Kubio builder affiliate links.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $go_paths Array containing all paths.
	 *
	 * @return array $go_paths Array containing modified paths.
	 */
	public function change_kubio_affiliate_link( $go_paths ) {
		$go_paths['upgrade'] = $this->get_affiliate_link( 'kubio' );

		return $go_paths;
	}

	/**
	 * Configure the options for other plugins.
	 *
	 * @since  3.0.0
	 */
	public static function configure_other_plugins() {
		$options = array(
			'enable_cache',
			'autoflush_cache',
			'optimize_html',
			'optimize_javascript',
			'optimize_javascript_async',
			'optimize_css',
			'combine_css',
			'combine_google_fonts',
			'disable_emojis',
			'lazyload_images',
		);

		foreach ( $options as $option ) {
			update_option( 'siteground_optimizer_' . $option, 1 );
		}

		update_option( 'siteground_optimizer_excluded_lazy_load_media_types', array( 'lazyload_shortcodes' ) );

		$transients = array(
			'fs_plugin_foogallery_activated',
			'fs_plugin_ocean-posts-slider_activated',
			'fs_plugin_the-events-calendar_activated',
		);

		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}

		// Remove the AIOSEO redirect.
		delete_option( '_aioseo_cache_activation_redirect' );
		delete_option( '_aioseo_cache_expiration_activation_redirect' );
		update_option( 'themeisle_blocks_settings_redirect', 0 );
		update_option( 'aioseo_activation_redirect', true );

		// Remove Optin Monster transient for wizard and add skip option.
		update_option( 'optin_monster_api_activation_redirect_disabled', true );
		delete_transient( 'optin_monster_api_activation_redirect' );

		// Remove MonsterInsights transient for wizard.
		delete_transient( '_monsterinsights_activation_redirect' );

		// Add the iubenda api key if plugin is added by starter.
		if ( 1 === (int) get_option( 'siteground_wizard_installed_iubenda', 0 ) ) {
			update_option( 'iubenda_api_key', '696089dc3e57257372e05473114cb13367e75e53' );
		}

		// Flushing caches after modifying options.
		wp_cache_flush();
	}

	/**
	 * Get the active recommended plugins data.
	 *
	 * @since  3.0.0
	 *
	 * @return array  The plugins data.
	 */
	public static function get_active_plugins_data() {
		$plugins            = array();
		$functionality_data = file_get_contents( plugin_dir_path( __FILE__ ) . 'config/functionality.json' );
		$functionality      = json_decode( $functionality_data, true );

		foreach ( $functionality['active_plugins'] as $plugin_data ) {
			if ( ! is_plugin_active( $plugin_data['plugin_name'] ) ) {
				continue;
			}

			$plugins[] = $plugin_data;
		}

		return $plugins;
	}

	/**
	 * Check the service company in ST and replace it if needed.
	 *
	 * @since 3.0.0
	 */
	public function check_service_company() {
		// Prepare arguments.
		$args = array(
			'api'      => 'site',
			'cmd'      => 'list',
			'params'   => (object) array(),
			'settings' => array(
				'json'        => 1,
				'show_fields' => array(
					'features',
				),
			),
		);

		$result = Site_Tools_Client::call_site_tools_client( $args );

		// Bail if we do not get the result.
		if ( ! $result || empty( $result['json']['features']['sco_id'] ) ) {
			return false;
		}

		// Bail if the option is already set to the correct sco_id.
		if ( intval( get_option( 'sco_id', '4' ) ) === intval( $result['json']['features']['sco_id'] ) ) {
			return true;
		}

		update_option( 'sco_id', $result['json']['features']['sco_id'] );
	}
}