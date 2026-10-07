<?php
namespace SG_AI_Studio\Activity_Log;


use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;

/**
 * Activity log main class
 */
class Activity_Log {

	/**
	 * The singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Activity_Log The singleton instance.
	 */
	private static $instance;

	/**
	 * Our custom log table name
	 *
	 * @var string
	 */
	public $log_table = 'sg_ai_log_events';

	/**
	 * WPDB entity.
	 *
	 * @var object
	 */
	public static $wpdb;

	/**
	 * The constructor.
	 */
	public function __construct() {
		self::$instance = $this;

		global $wpdb;

		$wpdb->sg_ai_log = $wpdb->prefix . $this->log_table;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Activity_Log The singleton instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			static::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Set the CRON job for deleting old logs.
	 *
	 * @since  1.0.0
	 */
	public function set_sg_ai_logs_cron() {
		// Bail if CRON is disabled.
		if ( 1 === Helper::is_cron_disabled() ) {
			return;
		}

		if ( ! wp_next_scheduled( 'sg_ai_studio_clear_logs_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'sg_ai_studio_clear_logs_cron' );
		}
	}

	/**
	 * Delete logs on plugin page if CRON is disabled.
	 *
	 * @since  1.0.0
	 */
	public function delete_logs_on_admin_page() {
		// Delete if we are on plugin page and CRON is disabled.
		if (
			isset( $_GET['page'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'sg-ai-studio' === $_GET['page'] && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			1 === Helper::is_cron_disabled()
		) {
			$this->delete_old_events_logs();
		}
	}



	/**
	 * Create log tables upon new site creation.
	 *
	 * @since  1.0.0
	 *
	 * @param  \WP_Site $new_site New site object.
	 */
	public function create_subsite_log_tables( $new_site ) {
		// Check if the method exists.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( ! \is_plugin_active_for_network( 'sg-ai-studio/sg-ai-studio.php' ) ) {
			return;
		}

		// Switch to the newly created blog.
		switch_to_blog( $new_site->blog_id );

		// Add the new tables.
		self::create_log_tables();

		// Restore to the current blog.
		restore_current_blog();
	}

	/**
	 * Get the activity log lifetime.
	 *
	 * @since 1.0.0
	 *
	 * @return int $log_lifetime How many days the log is preserved, 12 by default.
	 */
	public static function get_activity_log_lifetime() {
		// Set custom log lifetime interval in days. The intval covers the cases for string, array and SQL injections.
		$log_lifetime = intval( \apply_filters( 'sg_ai_studio_set_activity_log_lifetime', \get_option( 'sg_ai_studio_activity_log_lifetime', 12 ) ) );

		// If the custom value is less than 1 day or more than 12, fallback to the default lifetime.
		if ( ( 1 > $log_lifetime ) || ( $log_lifetime > 12 ) ) {
			$log_lifetime = 12;
		}

		return $log_lifetime;
	}
	/**
	 * Checks if the table exists in the database.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $table_name The name of the table.
	 *
	 * @return boolean            True/False.
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;
		self::$wpdb = $wpdb;

		// Bail if table doesn't exist.
		if ( self::$wpdb->get_var( self::$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) { //phpcs:ignore
			return false;
		}

		return true;
	}

	/**
	 * Delete old logs from events table.
	 *
	 * @since 1.0.0
	 *
	 * @return  int|bool False if tables do not exists, number of rows deleted.
	 */
	public function delete_old_events_logs() {
		global $wpdb;

		// Bail if table doesn't exist.
		if ( ! self::table_exists( $wpdb->sg_ai_log ) ) {
			return false;
		}

		// Get the activity log lifetime.
		$log_lifetime = self::get_activity_log_lifetime();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . esc_sql( $wpdb->sg_ai_log ) . '`
					WHERE `ts` < %d
				;',
				time() - $log_lifetime * DAY_IN_SECONDS
			)
		);
		return true;
	}

	/**
	 * Create the log table.
	 *
	 * @since  1.0.0
	 */
	public static function create_log_tables() {
		global $wpdb;
		$events_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}sg_ai_log_events` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `visitor_id` int(11) NOT NULL,
					  `ts` int(11) NOT NULL DEFAULT '0',
					  `activity` varchar(255) NOT NULL,
					  `description` varchar(255) NOT NULL,
					  PRIMARY KEY (`id`),
					  INDEX `log_event_index` (`visitor_id`, `ts`, `activity`, `id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $events_sql );
	}
}
