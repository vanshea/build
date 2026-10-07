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
use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\Blocks\BlocksManager;
use SG_AI_Studio\Rest\Rest;
use SG_AI_Studio\CLI\AI_Studio_CLI;
use SG_AI_Studio\Activity_Log\Activity_Log;
use SG_AI_Studio\Gutenberg\Gutenberg;

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
	 * Constructor - initialize plugin components
	 */
	public function __construct() {
		$this->admin        = new Admin();
		$this->blocks       = new BlocksManager();
		$this->rest         = new Rest();
		$this->activity_log = new Activity_Log();
		$this->gutenberg    = new Gutenberg();
		$this->helper       = new Helper();

		$this->add_admin_hooks();
		$this->add_activity_log_hooks();
		$this->add_blocks_hooks();
		$this->add_rest_hooks();
		$this->add_gutenberg_hooks();
		$this->add_cli_hooks();
		$this->add_helper_hooks();
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
		add_action( 'admin_print_footer_scripts', array( $this->admin, 'add_floating_chat' ) );

		add_action( 'rest_api_init', function()  { remove_filter('rest_pre_serve_request', 'rest_send_cors_headers'); }, 15 );

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
	}

	/**
	 * Register Gutenberg editor hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_gutenberg_hooks() {
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
		}

		add_action( 'sg_ai_studio_key_refresh_cron', array( $this->helper, 'cron_refresh_keys' ) );


	}

}
