<?php
namespace SG_AI_Studio\Activator;

use SG_AI_Studio\Activity_Log\Activity_Log;

/**
 * Class managing plugin activation.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @param bool $network_active Whether the plugin is network active.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function activate( $network_active ) {
		Activity_Log::create_log_tables();

		// Create the necesary tables in subsites upon activation for multisite.
		if (
			\is_multisite() &&
			true === $network_active
		) {
			// Get all sites.
			$sites = \get_sites();

			// Loop trough subsites and create the necesary db tables.
			foreach ( $sites as $site ) {
				\switch_to_blog( $site->blog_id );

				// Run the db creation.
				Activity_Log::create_log_tables();
			}

			// Restore to current blog.
			\restore_current_blog();
		}
	}
}
