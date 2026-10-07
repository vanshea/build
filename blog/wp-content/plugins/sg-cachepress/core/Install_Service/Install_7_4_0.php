<?php
namespace SiteGround_Optimizer\Install_Service;

use SiteGround_Helper\Helper_Service;
use SiteGround_Optimizer\Memcache\Memcache;
use SiteGround_Optimizer\Options\Options;

class Install_7_4_0 extends Install {

	/**
	 * The default install version. Overridden by the installation packages.
	 *
	 * @since 7.4.0
	 *
	 * @access protected
	 *
	 * @var string $version The install version.
	 */
	protected static $version = '7.4.0';

	/**
	 * Run the install procedure.
	 *
	 * @since 7.4.0
	 */
	public function install() {
		// Non SG users and non multisites only.
		if (
			Helper_Service::is_siteground() ||
			is_multisite()
		) {
			// Update install service option.
			update_option( 'sgo_install_7_4_0', 1 );
			return;
		}

		update_option( 'siteground_optimizer_enable_cache', 0 );

		// Disable auto-flush if file-based cache is not enabled.
		if ( ! Options::is_enabled( 'siteground_optimizer_file_caching' ) ) {
			update_option( 'siteground_optimizer_autoflush_cache', 0 );
		}

		// Setting the notification email option for Performance report emails.
		add_option( 'siteground_optimizer_performace_receipient', array( get_bloginfo( 'admin_email' ) ) );

		// Update install service option.
		update_option( 'sgo_install_7_4_0', 1 );
	}
}
