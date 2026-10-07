<?php
namespace SiteGround_Central\Installer;

use SiteGround_Central\ThirdParty\ThirdParty;
use SiteGround_Central\Steps\PluginStep;
use SiteGround_Central\Steps\ThemeStep;

/**
 * Installer functions and main initialization class.
 */
class Installer {
	/**
	 * Execute the installation command based on type and item.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $type The plugin or theme we will install.
	 * @param  array  $item Details for the item we want to install.
	 *
	 * @return boolean      True/False if the installation was successful.
	 */
	public static function execute_installation_command( $type, $item ) {
		// Get the current errors if any.
		$errors = get_option( 'siteground_wizard_installation_errors', array() );
		// Execute the installation command.
		exec(
			sprintf(
				'wp %s install %s --activate --skip-packages',
				escapeshellarg( rtrim( $type, 's' ) ),
				! empty( $item['download_url'] ) ? escapeshellarg( $item['download_url'] ) : escapeshellarg( $item['slug'] )
			),
			$output,
			$status
		);

		// Check for errors.
		if ( ! empty( $status ) ) {
			$errors[] = sprintf( 'Cannot install %1$s: %2$s', $item['type'], $item['slug'] );
			// Add the error.
			update_option( 'siteground_wizard_installation_errors', $errors );

			return false;
		}

		// Maybe flag the plugin/theme if it was added by the starter.
		self::maybe_flag_wizard_install( $type, $item['slug'] );

		// Flush all caches in order to have the classes initialized.
		wp_cache_flush();

		if ( ! function_exists( '\wp_clean_plugins_cache' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		\wp_clean_plugins_cache();

		return true;
	}

	/**
	 * Add a flag that a specific item was added via the Starter plugin.
	 *
	 * @since 3.0.2
	 *
	 * @param  string $type The item type - plugin or theme.
	 * @param  string $item The plugin/theme slug.
	 */
	public static function maybe_flag_wizard_install( $type, $item ) {
		// Plugins and themes we need to mark.
		$flags = array(
			'themes' => array(
				'astra' => 'siteground_wizard_installed_astra_theme',
			),
			'plugins' => array(
				'iubenda-cookie-law-solution' => 'siteground_wizard_installed_iubenda',
				'kubio'                       => 'siteground_wizard_installed_kubio',
			),
		);

		// Set the flag if the item is in the list.
		if ( array_key_exists( $item, $flags[ $type ] ) ) {
			update_option( $flags[ $type ][ $item ], 1 );
		}
	}

	/**
	 * Complete the Wizard installation.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public static function complete() {
		// Get the errors.
		$errors = get_option( 'siteground_wizard_installation_errors', array() );

		// Update the status.
		$callback = ! \is_multisite() ? 'update_option' : 'update_site_option';

		// Update wizard installation status option.
		call_user_func(
			$callback,
			'siteground_wizard_installation_status',
			array(
				'status' => 'completed',
				'errors' => $errors,
			)
		);

		// Update wizard redirect option.
		call_user_func(
			$callback,
			'siteground_wizard_activation_redirect',
			'no'
		);

		// Delete themes and plugins data files.
		unlink( ThemeStep::get_themes_data_file() );
		unlink( PluginStep::get_plugins_data_file() );

		// Reset the errors and progress.
		delete_option( 'siteground_wizard_installation_errors' );
		delete_option( 'siteground_wizard_progress' );
		delete_option( 'siteground_wizard_ai_flow' );
		delete_option( 'siteground_wizard_next_step' );
		delete_option( ThemeStep::get_themes_data_option() );
		delete_option( PluginStep::get_plugins_data_option() );

		ThirdParty::configure_other_plugins();
	}

	/**
	 * Install plugin from the custom dashboard.
	 *
	 * @since  1.0.0
	 */
	public static function install_from_dashboard( $activate = true ) {
		if ( ! wp_verify_nonce( $_GET['nonce'], $_GET['plugin'] ) ) {
			die( __( 'Security check', 'siteground-wizard' ) );
		}

		// Execute the installation command.
		exec(
			sprintf(
				'wp plugin install %s %s',
				escapeshellarg( $_GET['plugin'] ),
				true === $activate ? '--activate' : ''
			),
			$output,
			$status
		);

		wp_clean_plugins_cache();

		// Check for errors.
		if ( ! empty( $status ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Install plugin from the custom dashboard.
	 *
	 * @since  1.0.0
	 */
	public static function activate_from_dashboard() {
		if ( ! wp_verify_nonce( $_GET['nonce'], $_GET['plugin'] ) ) {
			die( __( 'Security check', 'siteground-wizard' ) );
		}

		// Execute the installation command.
		exec(
			sprintf(
				'wp plugin activate %s',
				escapeshellarg( $_GET['plugin'] )
			),
			$output,
			$status
		);

		wp_clean_plugins_cache();

		// Check for errors.
		if ( ! empty( $status ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Update plugin from the custom dashboard.
	 *
	 * @since  1.0.0
	 */
	public static function update_from_dashboard() {
		if ( ! wp_verify_nonce( $_GET['nonce'], $_GET['plugin'] ) ) {
			die( __( 'Security check', 'siteground-wizard' ) );
		}

		// Execute the update command.
		exec(
			sprintf(
				'wp plugin update %s',
				escapeshellarg( $_GET['plugin'] )
			),
			$output,
			$status
		);

		wp_clean_plugins_cache();

		// Check for errors.
		if ( ! empty( $status ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}
}
