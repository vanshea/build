<?php
namespace SiteGround_Central\Admin;

use SiteGround_Central\Activator\Activator;
use SiteGround_Central\Traits\Statistic_Trait;
use SiteGround_Central\Wizard\Wizard;
use SiteGround_Helper\Helper_Service;

/**
 * Handle all hooks for our custom admin pages.
 */
class Admin {
	use Statistic_Trait;
	/**
	 * SG Central pages.
	 *
	 * @since  3.0.0
	 *
	 * @var array
	 */
	public $plugin_pages = array(
		'siteground-wizard'                   => 'Wizard',
		'dashboard_page_siteground-dashboard' => 'Dashboard',
		'plugins_page_sg-plugin-install'      => 'Plugins',
		'appearance_page_sg-themes-install'   => 'Themes'
	);

	/**
	 * List of conflicting admin styles, to be dequeued.
	 *
	 * @var array
	 */
	public $dequeued_styles = array(
		'elegant', // Elegant Icons-set
	);

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 5.0.0
	 */
	public function admin_enqueue_styles() {
		// Bail if we are on different page.
		if ( false === $this->is_plugin_page() ) {
			return;
		}

		// Dequeue conflicting styles.
		foreach ( $this->dequeued_styles as $style ) {
			wp_dequeue_style( $style );
		}

		// Enqueue the style.
		wp_enqueue_style(
			'siteground-central-style',
			\SiteGround_Central\URL . '/assets/css/admin.min.css',
			array(),
			\SiteGround_Central\VERSION,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 3.0.0
	 */
	public function admin_enqueue_scripts() {
		// Bail if we are on different page.
		if ( false === $this->is_plugin_page() ) {
			return;
		}

		// Enqueue the script.
		wp_enqueue_script(
			'siteground-central-script',
			\SiteGround_Central\URL . '/assets/js/admin.min.js',
			array( 'jquery' ), // Dependencies.
			\SiteGround_Central\VERSION,
			true
		);
		// Enqueue the Thickbox library, allowing for the display of build-in pop-ups.
		add_thickbox();
	}

	/**
	 * Check if this is the SG Central page.
	 *
	 * @since  3.0.0
	 *
	 * @return bool True/False
	 */
	public function is_plugin_page() {
		// Bail if the page is not an admin screen.
		if ( ! is_admin() ) {
			return false;
		}

		$current_screen = \get_current_screen();

		if ( in_array( str_replace('admin_page_', '', $current_screen->id ), array_keys( $this->plugin_pages ) ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Wheather or not to redirect to the wizard page.
	 *
	 * @since 3.0.0
	 */
	public function wizard_redirect() {
		// Get the db value.
		$show_wizard = ! \is_multisite() ? get_option( Activator::SHOW_WIZARD ) : get_site_option( Activator::SHOW_WIZARD, 'no' );

		// Bail if wizard is already completed.
		if ( 'no' === $show_wizard ) {
			return;
		}

		// If we're already on the page or the user doesn't have permissions, return.
		if (
			( ! empty( $_GET['page'] ) && 'siteground-wizard' === $_GET['page'] ) ||
			is_network_admin() ||
			isset( $_GET['activate-multi'] ) ||
			! current_user_can( 'manage_options' )
		) {
			return;
		}

		// Finally redirect to the setup page.
		wp_safe_redirect( admin_url( 'index.php?page=siteground-wizard' ) );

		exit;
	}

	/**
	 * Display wizard page.
	 *
	 * @since  3.0.0
	 */
	public function display_wizard_page() {
		if ( ! is_user_logged_in() && ! current_user_can( 'administrator' ) ) {
			return;
		}
		$status = ! \is_multisite() ? get_option( 'siteground_wizard_installation_status' ) : get_site_option( 'siteground_wizard_installation_status' );

		// First check if we are in the wizard page at all, if not do nothing.
		if ( ! empty( $_GET['page'] ) && 'siteground-wizard' === $_GET['page'] ) {
			// Bail if we have successful installation already.
			if (
				! empty( $status ) &&
				'completed' === $status['status']
			) {
				! \is_multisite() ? wp_safe_redirect( 'admin.php?page=siteground-dashboard' ) : wp_safe_redirect( admin_url() );
				exit;
			}

			// Send shown_person statistics.
			self::send_statistics( 'wizard_shown_person' );

			include_once \SiteGround_Central\DIR . '/templates/wizard_template.php';
			exit;
		}
	}

	/**
	 * Add styles to WordPress admin head.
	 *
	 * @since  3.0.0
	 */
	public function admin_print_styles() {
		// Bail if we are on different page.
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		$current_screen = \get_current_screen();

		echo '<style>.notice { display:none!important; } .dashboard_page_custom-dashboard div#setting-error-tgmpa {display: none !important; }</style>';

		$data = array(
			'rest_base'           => untrailingslashit( get_rest_url( null, '/' ) ),
			'home_url'            => home_url(),
			'admin_url'           => admin_url(),
			'localeSlug'          => join( '-', explode( '_', \get_user_locale() ) ),
			'locale'              => self::get_i18n_data_json(),
			'wp_nonce'            => wp_create_nonce( 'wp_rest' ),
			'assetsPath'          => \SiteGround_Central\URL . '/assets/',
			'wizard_type'         => Wizard::get_wizard_name(),
		);

		// Check which page we are on and set it in the inline script, to pass it to the JS instance.
		switch( $current_screen->id ) {
			case 'dashboard_page_siteground-dashboard':
				$page = 'dashboard';
				break;
			case 'plugins_page_sg-plugin-install':
				$page = 'plugins';
				break;
			case 'appearance_page_sg-themes-install':
				$page = 'themes';
				break;
		}

		echo '<script>window.addEventListener("load", function(){ WPStarter.init({ domElementId: "sg-admin-container", page: "' . $page  .'", config:' . json_encode( $data ) . '})});</script>';
	}

	/**
	 * Register the top level page into the WordPress admin menu.
	 *
	 * @since 3.0.0
	 */
	public function add_plugin_pages() {
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		foreach ( $this->plugin_pages as $id => $title ) {
			if (
				is_multisite() &&
				! is_network_admin() &&
				array_key_exists( $id, $this->multisite_permissions ) &&
				0 === intval( get_site_option( $this->multisite_permissions[ $id ], 0 ) )
			) {
				continue;
			}

			\add_submenu_page(
				'',   // Parent slug.
				__( $title, 'siteground-wizard' ), // phpcs:ignore
				__( $title, 'siteground-wizard' ), // phpcs:ignore
				'manage_options',
				$id,
				array( $this, 'display_wizard_page' )
			);
		}
	}

	/**
	 * Get i18n strings as a JSON-encoded string
	 *
	 * @since 3.0.0
	 *
	 * @return string The locale as JSON
	 */
	public static function get_i18n_data_json() {
		$wp_filesystem = Helper_Service::setup_wp_filesystem();

		// Get the user locale.
		$locale = \get_user_locale();

		// Build the full path to the file.
		$i18n_json = \SiteGround_Central\DIR . '/languages/siteground-wizard' . '-' . $locale . '.json';

		// Check if the files exists and it's readable.
		if ( $wp_filesystem->is_file( $i18n_json ) && $wp_filesystem->is_readable( $i18n_json ) ) {
			// Get the locale data.
			$locale_data = $wp_filesystem->get_contents( $i18n_json );
			if ( $locale_data ) {
				return $locale_data;
			}
		}

		// Return valid empty Jed locale.
		return json_encode(
			array(
				'' => array(
					'domain' => 'siteground-wizard',
					'lang'   => is_admin() ? \get_user_locale() : \get_locale(),
				),
			)
		);
	}

	/**
	 * Loads the textdomain for the plugin.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Get the user locale.
		$locale = \get_user_locale();

		// Build the full path to the file.
		$i18n_mo = '/languages/siteground-wizard' . '-' . $locale . '.mo';

		\load_plugin_textdomain( 'siteground-wizard', false, '/wordpress-starter/languages/');
	}

	/**
	 * Sets the Woo Wizard option in regard to the plugins and active theme.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function set_woo_option() {
		// Check if Woo is installed before the wizard and if the theme is set to Storefront, if so - return WooWizard path.
		if (
			\is_plugin_active( 'woocommerce/woocommerce.php' ) &&
			'Storefront' === wp_get_theme()->Name
		) {
			update_option( 'sg_wp_starter_woo', 1 );
		} else {
			update_option( 'sg_wp_starter_woo', 0 );
		}

		return true;
	}

	/**
	 * Hides the Elementor ads in the media library.
	 *
	 * @since 3.0.3
	 *
	 */
	public function hide_elementor_ads_in_media_library() {

		$current_screen = \get_current_screen();

		// If we are on the Media Library page.
		if ( 'upload' === $current_screen->id ) {
			// Remove the Generate with Elementor AI button.
			wp_dequeue_script( 'elementor-ai-media-library' );
			// Remove the ads banner.
			remove_all_actions( 'admin_notices' );
		}
	}

	/**
	 * Hide the Neve's Starter Sites notice in the Themes section.
	 */
	public function hide_neve_notice_in_themes_section() {

		$current_screen = \get_current_screen();

		// If we are on the Themes page, filter out the Neve notice.
		if ( 'themes' === $current_screen->id ) {
			add_filter( 'neve_disable_starter_sites_admin_notice', '__return_true' );
		}
	}
}
