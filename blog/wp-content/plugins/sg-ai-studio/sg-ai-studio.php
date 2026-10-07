<?php
/**
 * SG AI Studio
 *
 * @package           SG AI Studio
 * @author            SiteGround
 * @link              http://www.siteground.com/
 *
 * @wordpress-plugin
 * Plugin Name:       SG AI Studio
 * Plugin URI:        https://siteground.com
 * Description:       Manage your WordPress site with AI - create content, install plugins, and perform site management tasks effortlessly.
 * Version:           1.1.6
 * Author:            SiteGround
 * Author URI:        https://www.siteground.com
 * Text Domain:       sg-ai-studio
 * Domain Path:       /languages
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Our namespace.
namespace SG_AI_Studio;

use SG_AI_Studio\Loader\Loader;
use SG_AI_Studio\Activator\Activator;


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define version constant.
if ( ! defined( __NAMESPACE__ . '\VERSION' ) ) {
	define( __NAMESPACE__ . '\VERSION', '1.1.6' );
}

// Define slug constant.
if ( ! defined( __NAMESPACE__ . '\PLUGIN_SLUG' ) ) {
	define( __NAMESPACE__ . '\PLUGIN_SLUG', 'sg-ai-studio' );
}

// Define root directory.
if ( ! defined( __NAMESPACE__ . '\DIR' ) ) {
	define( __NAMESPACE__ . '\DIR', __DIR__ );
}

// Define root URL.
if ( ! defined( __NAMESPACE__ . '\URL' ) ) {
	$sg_ai_studio_root_url = \trailingslashit( DIR );

	// Sanitize directory separator on Windows.
	$sg_ai_studio_root_url = str_replace( '\\', '/', $sg_ai_studio_root_url );

	$sg_ai_studio_plugin_dir = str_replace( '\\', '/', WP_PLUGIN_DIR );
	$sg_ai_studio_root_url   = str_replace( $sg_ai_studio_plugin_dir, \plugins_url(), $sg_ai_studio_root_url );

	define( __NAMESPACE__ . '\URL', \untrailingslashit( $sg_ai_studio_root_url ) );

	unset( $sg_ai_studio_root_url, $sg_ai_studio_plugin_dir );
}

require_once( \SG_AI_Studio\DIR . '/vendor/autoload.php' );

register_activation_hook( __FILE__, array( new Activator(), 'activate' ) );

// Initialize the loader.
$sg_ai_studio = new Loader();
