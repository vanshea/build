<?php
namespace SiteGround_Optimizer\Install_Service;

use SiteGround_Optimizer\Options\Options;
use SiteGround_Optimizer\Memcache\Memcache;

class Install_7_7_2 extends Install {

	/**
	 * Memcache instance.
	 *
	 * @var Memcache
	 */
	public $memcache;

	/**
	 * The default install version. Overridden by the installation packages.
	 *
	 * @since 7.8.1
	 *
	 * @access protected
	 *
	 * @var string $version The install version.
	 */
	protected static $version = '7.7.2';

	public function __construct() {
		$this->memcache = new Memcache();
	}
	/**
	 * Run the install procedure.
	 *
	 * @since 7.8.1
	 */
	public function install() {

		if (
			Options::is_enabled( 'siteground_optimizer_enable_memcached' )
		) {
			$this->memcache->remove_memcached_dropin();
			$this->memcache->create_memcached_dropin();
		}
	}

}