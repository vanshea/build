<?php
namespace SiteGround_Optimizer\Cli;

use SiteGround_Optimizer\Config\Config;

/**
 * WP-CLI: wp sg optimizer-config.
 *
 * Run the `wp sg optimizer-config` command to generate config file.
 *
 * @since 7.6.3
 * @package Cli
 * @subpackage Cli/Config
 */

/**
 * Define the {@link Cli_Config} class.
 *
 * @since 7.6.3
 */
class Cli_Config {

	/**
	 * Create Config file.
	 */
	public function __invoke() {
		$config = new Config();

		if ( ! version_compare( get_option( 'siteground_optimizer_current_version', false ), \SiteGround_Optimizer\VERSION, '==' ) ) {
			// Update the option in the db.
			update_option( 'siteground_optimizer_current_version', \SiteGround_Optimizer\VERSION );
		}

		$config->update_config();
	}

}
