<?php
namespace SiteGround_Central;

use SiteGround_Central\Wizard\Wizard;
use SiteGround_Central\Admin\Admin;

include_once( ABSPATH . 'wp-admin/includes/plugin.php');
?>
<!doctype html>
<html>
<head>
    <!-- Defining responsive ambient. -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'WordPress Starter', 'siteground-wizard' ); ?></title>
    <style>.notice { display:none!important; } </style>
</head>
<body>
<?php
// Enqueue the style.
wp_enqueue_style(
	'siteground-central-style',
	\SiteGround_Central\URL . '/assets/css/wizard.min.css',
	array(),
	\SiteGround_Central\VERSION,
	'all'
);

// Enqueue the script.
wp_enqueue_script(
	'siteground-central-script',
	\SiteGround_Central\URL . '/assets/js/wizard.min.js',
	array( 'jquery' ), // Dependencies.
	\SiteGround_Central\VERSION,
	false
);

wp_print_scripts();
wp_print_styles();

echo '<style>.notice { display:none!important; } </style>';

$data = array(
	'rest_base'   => untrailingslashit( get_rest_url( null, '/' ) ),
	'home_url'    => home_url(),
    'admin_url'   => admin_url(),
	'localeSlug'  => join( '-', explode( '_', \get_user_locale() ) ),
	'locale'      => Admin::get_i18n_data_json(),
	'assetsPath'  => \SiteGround_Central\URL . '/assets/',
	'wp_nonce'    => wp_create_nonce( 'wp_rest' ),
	'wizard_type' => Wizard::get_wizard_name(),
);

echo '<script>window.addEventListener("load", function(){ WPStarter.init({domElementId: "sg-starter-container", config:' . json_encode( $data ) . '})});</script>';


?>
<div id="sg-starter-container"></div>
</body>
