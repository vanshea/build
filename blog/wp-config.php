<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbxjbbkhxc6qxx' );

/** Database username */
define( 'DB_USER', 'uk1rzawnijua8' );

/** Database password */
define( 'DB_PASSWORD', 'uq8oy66pag50' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '_R6w!(!)2M2n+2(jvw+Y>b2];Q&%`m78qC/o4Q,`OF*(?w%;?T6qfH6jTOQM4`X1' );
define( 'SECURE_AUTH_KEY',   '?$FXt*C[F76Dr{#OkT4D(j0&g^xKmG6Uo$gPNq%YbAsdv.g%tmcC=N,[vss rLK>' );
define( 'LOGGED_IN_KEY',     'mCvJvr[GxH8J0|<@h.{j1mt? Ro6^mKE6dC}AHD/V/=F`T?h#rU1:(oU-E$YiIrP' );
define( 'NONCE_KEY',         'lXjVzA[6|8(SHtLgUWBMNfXr6=)&8[kJ:wbA?Cysda,>pVu_(cS2V%u{@)Gy/;m0' );
define( 'AUTH_SALT',         'N(/8!08IF$0l5j%Y@L18GD(YgPEhk/H&+n0Tc|?4Ka3_E[5)H#;xZkFu[)P,A:4y' );
define( 'SECURE_AUTH_SALT',  '_7nxQ/yLN~Ww:7Euz`XA_6FA?<#.sc8&U@o0K vm7F_$J`7huS:?);Qp,J-_|Q/6' );
define( 'LOGGED_IN_SALT',    ':[j!IU6]ZL%>?tj6 r|L-;L&E%U$OW >C1EcO:gRV^Z{5y8Qp6[l8zG$oZ:Cp,A=' );
define( 'NONCE_SALT',        ']PL1A 5b`) DjqBKq5m]b%w#>v1lndX|VB&/cjMJ]g27dy ^tP?CIgy6j% -3d%7' );
define( 'WP_CACHE_KEY_SALT', 'n?&R6_qmi,$J/: 07@6uKZ0+&Ix;S:jJ?|yaH3g/$~%uapHiB<Z<oHP;P0)&mg,v' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'prc_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
