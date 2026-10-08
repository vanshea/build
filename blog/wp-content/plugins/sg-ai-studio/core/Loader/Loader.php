<?php
/**
 * Loader class for initializing the plugin components
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Loader;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use SG_AI_Studio\Admin\Admin;
use SG_AI_Studio\Frontend\Frontend;
use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\Blocks\BlocksManager;
use SG_AI_Studio\Rest\Rest;
use SG_AI_Studio\CLI\AI_Studio_CLI;
use SG_AI_Studio\Activity_Log\Activity_Log;
use SG_AI_Studio\Gutenberg\Gutenberg;
use SG_AI_Studio\Ai_Provider\Ai_Provider_Hooks;
use SG_AI_Studio\Install_Service\Install_Service;
use SG_AI_Studio\Vendor\SiteGround_i18n\i18n_Service;

/**
 * Loader functions and main initialization class.
 */
class Loader {
	/**
	 * Admin class instance
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Frontend class instance
	 *
	 * @var Frontend
	 */
	public $frontend;

	/**
	 * BlocksManager class instance
	 *
	 * @var BlocksManager
	 */
	public $blocks;

	/**
	 * Rest class instance
	 *
	 * @var Rest
	 */
	public $rest;

	/**
	 * Activity_Log class instance
	 *
	 * @var Activity_Log
	 */
	public $activity_log;

	/**
	 * Gutenberg class instance
	 *
	 * @var Gutenberg
	 */
	public $gutenberg;

	/**
	 * Helper class instance
	 *
	 * @var Helper
	 */
	public $helper;

	/**
	 * AI Provider hooks instance
	 *
	 * @var Ai_Provider_Hooks|null
	 */
	public $ai_provider;

	/**
	 * Install Service instance
	 *
	 * @var Install_Service
	 */
	public $install_service;

	/**
	 * i18n Service instance
	 *
	 * @var i18n_Service
	 */
	public $i18n_service;

	/**
	 * Constructor - initialize plugin components
	 */
	public function __construct() {
		$this->admin           = new Admin();
		$this->frontend        = new Frontend();
		$this->blocks          = new BlocksManager();
		$this->rest            = new Rest();
		$this->activity_log    = new Activity_Log();
		$this->gutenberg       = new Gutenberg();
		$this->helper          = new Helper();
		$this->install_service = new Install_Service();
		$this->i18n_service    = new i18n_Service( 'sg-ai-studio' );

		$this->add_admin_hooks();
		$this->add_frontend_hooks();
		$this->add_activity_log_hooks();
		$this->add_blocks_hooks();
		$this->add_rest_hooks();
		$this->add_gutenberg_hooks();
		$this->add_cli_hooks();
		$this->add_helper_hooks();
		$this->add_ai_provider_hooks();
		$this->add_install_service_hooks();
		$this->add_i18n_hooks();
	}

	/**
	 * Register admin-related hooks
	 *
	 * @return void
	 */
	public function add_admin_hooks() {
		// Add admin menus.
		add_action( 'network_admin_menu', array( $this->admin, 'add_plugin_pages' ) );
		add_action( 'admin_menu', array( $this->admin, 'add_plugin_pages' ) );
		// Register the stylesheets for the admin area.
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ), 111 );
		// Register the JavaScript for the admin area.
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		// Add styles to WordPress admin head.
		add_action( 'admin_print_styles', array( $this->admin, 'admin_print_styles' ) );
		// Register settings.
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		// Add floating chat widget to admin footer.
		add_action( 'admin_print_footer_scripts', array( $this->admin, 'add_floating_chat' ), 9 );
		// Reorder submenu pages.
		add_filter( 'menu_order', array( $this->admin, 'reorder_submenu_pages' ) );
	}

	/**
	 * Register frontend-related hooks
	 *
	 * @return void
	 */
	public function add_frontend_hooks() {
		// Register the JavaScript for the frontend area.
		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_scripts' ) );
		// Add floating chat widget to frontend footer.
		add_action( 'wp_footer', array( $this->frontend, 'add_floating_chat' ), 9 );
	}

	public function add_activity_log_hooks() {
		// Fires only for Multisite. Add log, visitors table if network active.
		add_action( 'wp_insert_site', array( $this->activity_log, 'create_subsite_log_tables' ) );

		if ( (bool) get_option('sg_ai_studio_connected', false ) ) {
			// Set the cron job for deleting the old logs.
			add_action( 'init', array( $this->activity_log, 'set_sg_ai_logs_cron' ) );
		}

		// Delete old logs if cron is disabled.
		add_action( 'admin_init', array( $this->activity_log, 'delete_logs_on_admin_page' ) );
		// Run the cron daily to check for expired logs and delete them.
		add_action( 'sg_ai_studio_clear_logs_cron', array( $this->activity_log, 'delete_old_events_logs' ) );

	}

	/**
	 * Register Gutenberg blocks hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_blocks_hooks() {
		// Register blocks.
		add_action( 'init', array( $this->blocks, 'register_blocks' ), 20 );
	}

	/**
	 * Register REST API hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rest_hooks() {
		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this->rest, 'register_rest_routes' ) );
		add_filter( 'rest_post_dispatch', array( $this->rest, 'apply_fields_filter' ), 9, 3 );
	}

	/**
	 * Register Gutenberg editor hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_gutenberg_hooks() {
		// Skip Gutenberg integration if disabled.
		if ( ! get_option( 'sg_ai_studio_gutenberg_actions', false ) ) {
			return;
		}

		// Enqueue Gutenberg editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this->gutenberg, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register WP-CLI hooks
	 *
	 * @return void
	 */
	public function add_cli_hooks() {
		// Register CLI commands only if WP-CLI is present
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'sg ai-studio', AI_Studio_CLI::class );
		}
	}

	/**
	 * Register key refresh cron hooks
	 *
	 * @return void
	 */
	public function add_helper_hooks() {
		// Add custom cron interval if it doesn't exist
		add_filter('cron_schedules', function($schedules) {
			$schedules['sg_ai_studio_29_days'] = array(
				'interval' => 29 * DAY_IN_SECONDS,
				'display'  => __('Every 29 Days', 'sg-ai-studio')
			);
			return $schedules;
		});

		if ( (bool) get_option('sg_ai_studio_connected', false ) ) {
			add_action( 'init', array( $this->helper, 'schedule_key_refresh_cron' ) );
			add_action( 'init', array( $this->helper, 'schedule_temp_cleanup_cron' ) );
		}

		add_action( 'sg_ai_studio_key_refresh_cron', array( $this->helper, 'cron_refresh_keys' ) );
		add_action( 'sg_ai_studio_cleanup_temp_cron', array( $this->helper, 'cron_cleanup_temp_files' ) );


	}

	/**
	 * Register AI Provider hooks
	 *
	 * Initializes the SG AI Studio provider for the WordPress PHP AI Client SDK
	 * and registers the connector on the WP 7.0+ Settings > Connectors page.
	 *
	 * @return void
	 */
	public function add_ai_provider_hooks() {
		if ( ! class_exists( 'WordPress\AiClient\AiClient' ) ) {
			return;
		}

		$this->ai_provider = new Ai_Provider_Hooks();

		// Register the provider early so it's available in the registry.
		add_action( 'init', array( $this->ai_provider, 'register_provider' ), -1 );

		// Reorder after all providers have been registered. Using admin_init ensures
		// this runs after WordPress core registers the default providers.
		add_action( 'admin_init', array( $this->ai_provider, 'prepend_provider_in_registry' ), 999 );

		// Show synthetic API key when provider is connected.
		add_filter( 'pre_option_connectors_ai_ai_studio_siteground_api_key', array( $this->ai_provider, 'filter_connector_option' ) );

		// Allow empty API key for AI Studio connector (for disconnect).
		add_filter( 'rest_pre_update_setting', array( $this->ai_provider, 'allow_empty_connector_api_key' ), 10, 3 );

		// Admin notices for provider state changes.
		add_action( 'admin_notices', array( $this->ai_provider, 'show_provider_state_notice' ) );

		// Detect when connector option is deleted/updated.
		add_action( 'deleted_option', array( $this->ai_provider, 'handle_connector_option_deleted' ) );
		add_action( 'updated_option', array( $this->ai_provider, 'handle_connector_option_updated' ), 10, 3 );

		// Enqueue CSS to hide API key field on Connectors page.
		add_action( 'admin_enqueue_scripts', array( $this->ai_provider, 'enqueue_connectors_css' ) );
	}

	/**
	 * Add the install service hooks.
	 *
	 * @since 1.1.8
	 */
	public function add_install_service_hooks() {
		// Add the install action.
		add_action( 'upgrader_process_complete', array( $this->install_service, 'install' ) );

		// Force the installation process if it is not completed.
		if ( false === get_option( 'sg_ai_studio_version', false ) ) {
			add_action( 'init', array( $this->install_service, 'install' ) );
		}
	}

	/**
	 * Add localization hooks.
	 *
	 * @since 1.1.8
	 * @return void
	 */
	public function add_i18n_hooks() {
		// Load the plugin textdomain.
		add_action( 'after_setup_theme', array( $this->i18n_service, 'load_textdomain' ), 9999 );
		// Generate JSON translations.
		add_action( 'upgrader_process_complete', array( $this->i18n_service, 'update_json_translations' ), 10, 2 );
	}
}
