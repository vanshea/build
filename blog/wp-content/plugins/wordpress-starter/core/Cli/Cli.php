<?php
namespace SiteGround_Central\Cli;

/**
 * SG Starter Cli main plugin class
 */
class Cli {
	/**
	 * Init SG Starter .
	 *
	 * @version
	 */
	public function register_commands() {
		// Optimize commands.
		\WP_CLI::add_command( 'sg-starter generate-translations', 'SiteGround_Central\Cli\Cli_Translations' );
	}
}
