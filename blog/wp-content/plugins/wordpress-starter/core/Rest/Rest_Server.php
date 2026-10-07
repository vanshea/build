<?php
/**
 * Initialize this version of the REST API.
 */

namespace SiteGround_Central\Rest;

use SiteGround_Central\Rest\Controllers\V1\Rest_Plugins;
use SiteGround_Central\Rest\Controllers\V1\Rest_Wizards;
use SiteGround_Central\Rest\Controllers\V1\Rest_Steps;
use SiteGround_Central\Rest\Controllers\V1\Rest_Install;
use SiteGround_Central\Rest\Controllers\V1\Rest_Dashboard;
use SiteGround_Central\Rest\Controllers\V1\Rest_Themes;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Rest_Server {
	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Register REST API routes.
	 *
	 * @since 3.0.0
	 */
	public function register_rest_routes() {
		$wizards   = new Rest_Wizards();
		$steps     = new Rest_Steps();
		$install   = new Rest_Install();
		$dashboard = new Rest_Dashboard();
		$plugins   = new Rest_Plugins();
		$themes   = new Rest_Themes();

		$wizards->register_routes();
		$steps->register_routes();
		$install->register_routes();
		$dashboard->register_routes();
		$plugins->register_routes();
		$themes->register_routes();
	}
}
