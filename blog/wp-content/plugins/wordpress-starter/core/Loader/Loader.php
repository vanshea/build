<?php
namespace SiteGround_Central\Loader;

use SiteGround_Central\Installer\Installer;
use SiteGround_Central\Pages\Dashboard;
use SiteGround_Central\Pages\Plugins;
use SiteGround_Central\Pages\Themes;
use SiteGround_Central\Rest\Rest_Server;
use SiteGround_Central\Admin\Admin;
use SiteGround_Central\ThirdParty\ThirdParty;
use SiteGround_Central\Updater\Updater;
use SiteGround_Central\Cli\Cli;
/**
 * Loader functions and main initialization class.
 */
class Loader {
	/**
	 * Local Variables.
	 */
	public $rest_server;
	public $admin;
	public $dashboard;
	public $themes;
	public $plugins;
	public $third_party;
	public $updater;
	public $installer;
	public $cli;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->add_hooks();
	}

	/**
	 * Load the main plugin dependencies.
	 *
	 * @since  3.0.0
	 */
	public function load_dependencies() {
		$this->rest_server = new Rest_Server();
		$this->admin       = new Admin();
		$this->themes      = new Themes();
		$this->plugins     = new Plugins();
		$this->third_party = new ThirdParty();
		$this->updater     = new Updater();
		$this->installer   = new Installer();
		$this->cli         = new Cli();
	}

	/**
	 * Add the hooks that the plugin will use to do the magic.
	 *
	 * @since  3.0.0
	 */
	public function add_hooks() {
		$this->add_rest_server_hooks();
		$this->add_admin_hooks();
		$this->add_pages_hooks();
		$this->add_third_party_hooks();
		$this->add_installer_hooks();
		$this->add_cli_hooks();
	}

	/**
	 * Add the REST API hooks.
	 *
	 * @since 3.0.0
	 */
	public function add_rest_server_hooks() {
		add_action( 'rest_api_init', array( $this->rest_server, 'register_rest_routes' ) );
	}

	/**
	 * Add the admin hooks.
	 *
	 * @since 3.0.0
	 */
	public function add_admin_hooks() {
		if ( false === get_option( 'sg_wp_starter_woo', false ) ) {
			add_action( 'admin_init', array( $this->admin, 'set_woo_option' ) );
		}

		add_action( 'admin_init', array( $this->admin, 'wizard_redirect' ) );
		add_action( 'wp_loaded', array( $this->admin, 'display_wizard_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'admin_enqueue_styles' ), 111 );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this->admin, 'add_plugin_pages' ) );
		add_action( 'admin_print_styles', array( $this->admin, 'admin_print_styles' ) );
		add_action( 'after_setup_theme', array( $this->admin, 'load_textdomain' ), 9 );
		add_action( 'admin_notices', array( $this->admin, 'hide_elementor_ads_in_media_library' ) );
		add_action( 'admin_notices', array( $this->admin, 'hide_neve_notice_in_themes_section' ), 0 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_styles' );
	}

	/**
	 * Add pages hooks.
	 *
	 * @since 3.0.0
	 */
	public function add_pages_hooks() {
		// Do not show any custom pages on multisite installations.
		if ( \is_multisite() ) {
			return;
		}

		// Themes page actions and filters.
		// add_action( 'admin_menu', array( $this->themes, 'admin_menu' ) );
		// add_filter( 'admin_url', array( $this->themes, 'replace_submenu_button_link' ), 10, 2 );
		// add_filter( 'custom_menu_order', '__return_true' );
		// add_filter( 'menu_order', array( $this->themes, 'reorder_submenu_pages' ) );
		// add_action( 'submenu_file', array( $this->themes, 'highlight_submenu_menu_item' ) );

		// Plugins page actions and filters.
		// add_action( 'admin_menu', array( $this->plugins, 'admin_menu' ) );
		// add_filter( 'custom_menu_order', '__return_true' );
		// add_filter( 'menu_order', array( $this->plugins, 'reorder_submenu_pages' ) );
		// add_action( 'admin_menu', array( $this->plugins, 'remove_original_page' ), 999 );
		// add_action( 'admin_body_class', array( $this->plugins, 'change_plugin_info_modal' ) );

		// Dashboard filters
	}

	/**
	 * Add third-party plugins and themes hooks
	 *
	 * @since 3.0.0
	 */
	public function add_third_party_hooks() {
		add_filter( 'wpforms_upgrade_link', array( $this->third_party, 'change_wpforms_upgrade_link' ) );
		add_filter( 'neve_upgrade_link_from_child_theme_filter', array( $this->third_party, 'change_neve_affiliate_link' ) );
		add_filter( 'neve_filter_onboarding_data', array( $this->third_party, 'change_neve_affiliate_link_config' ) );
		add_filter( 'ti_about_config', array( $this->third_party, 'remove_neve_useful_plugins' ) );

		// Temp solution, until the awesomemotive provide a way to change the entire link.
		add_filter( 'optin_monster_action_link', array( $this->third_party, 'change_optin_monster_action_link' ) );
		add_filter( 'monsterinsights_shareasale_id', array( $this->third_party, 'change_monsterinsights_shareasale_id' ) );
		add_filter( 'monsterinsights_shareasale_redirect_entire_url', array( $this->third_party, 'change_monsterinsights_shareasale_link' ) );
		add_filter( 'envira_gallery_shareasale_entire_redirect_url', array( $this->third_party, 'change_envira_shareasale_link' ) );

		// Check if the theme is added by the Wizard or via the recommended.
		if ( 1 === (int) get_option( 'siteground_wizard_installed_astra_theme', 0 ) ) {
			add_filter( 'astra_get_pro_url', array( $this->third_party, 'change_astra_affiliate_link' ) );
		}

		add_filter( 'option_st_start_onboarding', '__return_false' );
		add_filter( 'connect_url', '__return_false' );
		add_filter( 'trp_affiliate_link', array( $this->third_party, 'change_trp_affiliate_link' ) );
		add_filter( 'aioseo_upgrade_link', array( $this->third_party, 'change_aioseo_affiliate_link' ) );
		add_action( 'wp_login', array( $this->third_party, 'check_service_company' ) );

		// Disable the Kubio starter sites option and add affiliate link.
		if ( 1 === (int) get_option( 'siteground_wizard_installed_kubio', 0 ) ) {
			add_filter( 'kubio/starter-sites/enabled', '__return_false' );
			add_filter( 'kubio/kubio_go_link_paths', array( $this->third_party, 'change_kubio_affiliate_link' ) );
			add_filter( 'pre_transient_kubio_remote_notifications', '__return_empty_array' );
		}
	}

	/**
	 * Add Installer AJAX hooks
	 *
	 * @since 3.0.0
	 */
	public function add_installer_hooks() {
		add_action( 'wp_ajax_siteground_wizard_install_plugin', array( $this->installer, 'install_from_dashboard' ) );
		add_action( 'wp_ajax_siteground_wizard_activate_plugin', array( $this->installer, 'activate_from_dashboard' ) );
		add_action( 'wp_ajax_siteground_wizard_update_plugin', array( $this->installer, 'update_from_dashboard' ) );
	}

	/**
	 * WP CLI functionality added.
	 *
	 * @since 3.0.0
	 */
	public function add_cli_hooks() {
		// If we’re in `WP_CLI` load the related files.
		if ( class_exists( 'WP_CLI' ) ) {
			add_action( 'init', array( $this->cli, 'register_commands' ) );
		}
	}

}
