<?php
namespace SG_Security\Custom_Login_Url;

use SG_Security\Helper\Helper;
use SiteGround_Helper\Helper_Service;
use SG_Security\Helper\User_Roles_Trait;
use SG_Security\Options_Service\Options_Service;

/**
 * Custom_Login_Url class which disable the WordPress feed.
 */
class Custom_Login_Url {
	use User_Roles_Trait;
	/**
	 * Sg Security token
	 *
	 * @var string
	 */
	private $token = 'sgs-token';

	/**
	 * User Options
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Flag for the ultimate-member plugin forms.
	 *
	 * @var boolean
	 */
	private $um_form_detected_error = false;

	/**
	 * The Constructor
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		// Set the required options.
		$this->options = array(
			'new_slug' => get_option( 'sg_security_login_url', 'login' ),
			'redirect' => get_option( 'sg_security_login_redirect', '404' ),
			'register' => get_option( 'sg_security_login_register', 'register' ),
		);
	}

	/**
	 * Change the site URL to include the custom login URL token,
	 *
	 * @param string $url  The URL to be filtered.
	 * @param string $path The URL path.
	 */
	public function change_site_url( $url, $path = null ) {
		$token  = '';

		$path = is_null( $path ) ? $url : $path;

		// Get the url path.
		$path = Helper::get_url_path( $path ); //phpcs:ignore

		if ( strpos( $path, 'wp-login.php' ) === false ) {
			return $url;
		}

		if ( preg_match( '~[\?&]action=([^&]*)~', $path, $matches ) ) {
			switch ( $matches[1] ) {
				case 'postpass':
					return $url;
				case 'register':
					$token = 'register';
					break;
				case 'rp':
					return $url;
			}
		} else if (
			isset( $_GET[ $this->token ] ) &&
			$_GET[ $this->token ] === $this->options['new_slug']
		) {
			$token = $this->options['new_slug'];
		}

		// Add the token to the URL if not empty.
		if ( empty( $token ) ) {
			return $url;
		}

		// Return the URL.
		return add_query_arg( $this->token, urlencode( $token ), $url );
	}

	/**
	 * Change the links to the login page in the emails sent to the user.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $message The email message.
	 *
	 * @return string          Modified message.
	 */
	public function change_email_links( $message ) {
		return str_replace( 'wp-login.php', trailingslashit( $this->options['new_slug'] ), $message );
	}

	/**
	 * Handle request paths.
	 *
	 * @since  1.1.0
	 */
	public function handle_request() {

		// Get the path.
		$path = Helper::get_url_path( $_SERVER['REQUEST_URI'] ); //phpcs:ignore

		if ( $path === $this->options['new_slug'] ) {
			$this->redirect_with_token( 'login', 'wp-login.php' );
		}

		// Check if we are redirected to the login page, by the LogIn button on the registration page.
		if (
			$this->is_valid( 'login' ) &&
			isset( $_SERVER['HTTP_REFERER'] ) &&
			false !== strpos( $_SERVER['HTTP_REFERER'], 'register' ) &&
			false !== strpos( $path, 'wp-login.php' )
		) {
			$this->redirect_with_token( 'login', 'wp-login.php' );
		}

		// Check if we are redirected to the registration page, by the Register button on the login page.
		if (
			$this->is_valid( 'login' ) &&
			isset( $_SERVER['HTTP_REFERER'] ) &&
			false !== strpos( $_SERVER['HTTP_REFERER'], $this->options['new_slug'] ) &&
			isset( $_GET['action'] ) && 'register' === $_GET['action']
		) {
			$this->handle_registration();
		}

		if ( false !== strpos( $path, 'wp-login' ) || false !== strpos( $path, 'wp-login.php' ) ) {
			$this->handle_login();
		}

		if ( $path === $this->options['register'] ) {
			$this->handle_registration();
		}
	}

	/**
	 * Handle user logout.
	 *
	 * @since  1.1.1
	 *
	 * @param  int $user_id The user ID.
	 */
	public function wp_logout( $user_id ) {
		// Unset the permission cookie on logout.
		if ( $this->is_valid( 'login' ) ) {
			$this->unset_permissions_cookie( 'login' );
		}

		// Redirect to the homepage on logout instead redirecting to 404.
		wp_redirect( home_url() );
		exit;
	}

	/**
	 * Adds a token and redirect to the URL.
	 *
	 * @since  1.1.0
	 *
	 * @param string $type     The type of request to add an access token for.
	 * @param string $path     The path to redirect to.
	 */
	private function redirect_with_token( $type, $path ) {
		// Set the cookie so that access via unknown integrations works more smoothly.
		$this->set_permissions_cookie( $type );

		// Preserve existing query vars and add access token query arg.
		$query_vars                 = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query_vars[ $this->token ] = $this->options['new_slug'];

		$url = add_query_arg( $query_vars, site_url( $path ) );

		// Get the current URL.
		$current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// Prevent redirect loop by checking if the current URL matches the redirect URL.
		if ( true === $this->compare_urls( $url, $current_url ) ) {
			return;
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * Handle login.
	 *
	 * @since  1.1.0
	 */
	private function handle_login() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'rp' === $action ) {
			return;
		}

		if ( 'resetpass' === $action ) {
			return;
		}

		if ( 'postpass' === $action ) {
			return;
		}

		if ( 'register' === $action ) {
			if ( 'wp-signup.php' !== $this->options['register'] ) {
				$this->block( 'register' );
			}

			return;
		}

		if (
			has_filter( 'login_form_jetpack_json_api_authorization' ) &&
			'jetpack_json_api_authorization' === $action
		) {
			return;
		}

		if ( 'jetpack-sso' === $action && has_filter( 'login_form_jetpack-sso' ) ) {
			// Jetpack's SSO redirects from WordPress.com to wp-login.php on the site. Only allow this process to
			// continue if they successfully log in, which should happen by login_init in Jetpack which happens just
			// before this action fires.
			add_action( 'login_form_jetpack-sso', array( $this, 'block' ) );

			return;
		}

		$this->block( 'login' );
	}

	/**
	 * Block a request to the page.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $type The block request type.
	 */
	private function block( $type = 'login' ) {
		if ( is_user_logged_in() || $this->is_valid( $type ) ) {
			return;
		}

		// Die if there is no 404 page.
		if ( empty( $this->options['redirect'] ) ) {
			wp_die(
				esc_html__( 'This feature has been disabled.', 'sg-security' ),
				esc_html__( 'Restricted access', 'sg-security' ),
				array(
					'sgs_error' => true,
					'response'  => 403,
				)
			);
		}

		// Redirect to 404 page.
		wp_redirect( Helper_Service::get_home_url() . $this->options['redirect'], 302 );
		exit;
	}

	/**
	 * Checks if the user has permissions to view a page.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $type The permission type.
	 *
	 * @return boolean      True/False.
	 */
	private function is_valid( $type ) {
		$cookie = $this->token . '-' . $type . '-' . COOKIEHASH;

		// Check if the validation cookie is set.
		if (
			isset( $_COOKIE[ $cookie ] ) &&
			$_COOKIE[ $cookie ] === $this->options['new_slug'] //phpcs:ignore
		) {
			return true;
		}

		// Check if the token value is set.
		if (
			isset( $_REQUEST[ $this->token ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_REQUEST[ $this->token ] === $this->options['new_slug'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			// Add the permissions cookie.
			$this->set_permissions_cookie( $type );

			return true;
		}

		return false;
	}

	/**
	 * Set a cookie which will be used to check if the user has permissions to view a page.
	 *
	 * @since 1.1.0
	 *
	 * @param string $type The permissions type.
	 */
	private function set_permissions_cookie( $type ) {
		$url_parts = wp_parse_url( Helper_Service::get_site_url() );
		$home_path = trailingslashit( $url_parts['path'] );

		setcookie(
			$this->token . '-' . $type . '-' . COOKIEHASH,
			$this->options['new_slug'],
			time() + 3600,
			$home_path,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);
	}

	/**
	 * Handle registration request.
	 *
	 * @since  1.1.0
	 */
	private function handle_registration() {
		// Check if registration is allowed.
		if ( 1 !== intval( get_option( 'users_can_register', 0 ) ) ) {
			return;
		}

		if ( empty( get_option( 'users_can_register' ) ) ) {
			return;
		}

		$this->set_permissions_cookie( 'login' );

		if ( is_multisite() ) {
			$this->redirect_with_token( 'register', 'wp-signup.php' );
		}

		$this->redirect_with_token( 'register', 'wp-login.php?action=register' );
	}

	/**
	 * Handle change in user registration option.
	 *
	 * @since  1.1.0
	 *
	 * @param  (mixed) $old_value The old option value.
	 * @param  (mixed) $new_value The new option value.
	 *
	 * @return mixed              The new value.
	 */
	public function handle_user_registration_change( $old_value, $new_value ) {
		if ( ! empty( get_option( 'sg_security_login_register', false ) ) ) {
			return $new_value;
		}

		if ( 1 === intval( $new_value ) ) {
			update_option( 'sg_security_show_signup_notice', 1 );
		}

		return $new_value;
	}

	/**
	 * Displays an admin notice that registration is enabled and registration custom URL should be selected.
	 *
	 * @since  1.1.0
	 */
	public function show_notices() {
		// Bail if we should not show the notice.
		if ( empty( get_option( 'sg_security_show_signup_notice', false ) ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error" style="position: relative"><p>%1$s</p><button type="button" class="notice-dismiss dismiss-sg-security-notice" data-link="%2$s"><span class="screen-reader-text">Dismiss this notice.</span></button></div>',
			__( 'You have enabled registration for your site, please <a href="' . admin_url( 'admin.php?page=login-settings' ) . '">select a custom registration URL</a>.', 'sg-security' ), // phpcs:ignore
			wp_nonce_url( admin_url( 'admin-ajax.php?action=dismiss_sg_security_notice&notice=show_signup_notice' ), 'sg-security-signup-notice' ) // phpcs:ignore
		);
	}

	/**
	 * Dismiss notice handle.
	 *
	 * @since  1.1.0
	 */
	public function dismiss_backup_codes_notice() {
		$current_user = wp_get_current_user();

		delete_user_meta( $current_user->data->ID, 'sgs_additional_codes_added' ); //phpcs:ignore
	}

	/**
	 * Hide notices.
	 *
	 * @since  1.1.0
	 */
	public function hide_notice() {
		if ( empty( $_GET['notice'] ) || ! check_ajax_referer( 'sg-security-signup-notice', 'nonce', false ) ) {
			return;
		}

		if ( 'show_signup_notice' !== sanitize_text_field( $_GET['notice'] ) ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		update_option( 'sg_security_show_signup_notice', 0 );

		wp_send_json_success();
	}

	/**
	 * Adds the login token to the confirmation URL.
	 *
	 * @since  1.1.1
	 *
	 * @param  string $content    The email content.
	 * @param  array  $email_data Data relating to the account action email.
	 *
	 * @return string             Modified content.
	 */
	public function change_email_confirmation_url( $content, $email_data ) {
		// Bail if the request is not personal data removal.
		if (
			'remove_personal_data' !== $email_data['request']->action_name &&
			'export_personal_data' !== $email_data['request']->action_name
		) {
			return $content;
		}

		// Add the login token to the GDPR confirmation URL.
		$confirm_url = add_query_arg(
			$this->token,
			$this->options['new_slug'],
			$email_data['confirm_url']
		);

		return str_replace(
			'###CONFIRM_URL###',
			esc_url_raw( $confirm_url ),
			$content
		);
	}

	/**
	 * Modify the WPDiscuz comment post login URL.
	 *
	 * @since  1.2.0
	 *
	 * @param  string $login HTML code returned by the wpdiscuz_login_link filter.
	 *
	 * @return string $login modified HTML code with the SGS token added to the login link.
	 */
	public function custom_login_for_wpdiscuz( $login ) {
		// Get the login URL from the HTML.
		preg_match( '/<a\s+(?:[^>]*?\s+)?href=(["])(.*?)\1/', $login, $match );

		// Add the token to it.
		$new_url = add_query_arg( $this->token, $this->options['new_slug'], $match[2] );

		// Replace the URL in the HTML.
		$login = str_replace( $match[2], $new_url, $login );

		// Return the updated HTML.
		return $login;
	}

	/**
	 * Block administrators from logging-in through third party login forms when Custom Login URL is enabled.
	 *
	 * @since 1.3.3
	 *
	 * @param  \WP_User |\WP_Error $user      \WP_User object of the user that is trying to login or \WP_Error object if a previous callback failed * authentication.
	 * @return \WP_Error|\WP_User  If successful, the original \WP_User object, otherwise a \WP_Error object.
	 */
	public function maybe_block_custom_login( $user ) {
		// Check if the referrer slug is set.
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return $user;
		}

		// Check if $user is a WP_Error object.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Check if the ultimate member plugin form has errors.
		if ( true === $this->um_form_detected_error ) {
			return $user;
		}

		$error = new \WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: You are trying to login with an administrative account. Please, use the Custom Login URL instead.', 'sg-security' ) );

		// Set the user roles that are not allowed to login through custom forms and intersect them with the roles of the current user trying to log in.
		$user_admin_roles = array_intersect( $user->roles, $this->get_admin_user_roles() );

		// Check if the user has admin roles, if not - continue with the login.
		if ( empty( $user_admin_roles ) ) {
			return $user;
		}

		// Get referrer parts by parsing its URL.
		$referer = str_replace(
			array( home_url(), '/' ),
			array( '', '' ),
			$_SERVER['HTTP_REFERER']
		);

		// Parse the URL into query and path array items.
		$referer_parts = wp_parse_url( $referer );

		// Bail if query is not set.
		if ( empty( $referer_parts['query'] ) ) {
			return $error;
		}

		// Retrieve the query from the URL.
		parse_str( $referer_parts['query'], $referer_query );

		// Get the sgs-token if it's set.
		$sgs_token = ! empty( $referer_query['sgs-token'] ) ? esc_attr( $referer_query['sgs-token'] ) : '';

		if (
			$referer === $this->options['new_slug'] ||
			$this->options['new_slug'] === $sgs_token
		) {
			return $user;
		}

		return $error;
	}

	/**
	 * Adds our 'maybe_block_custom_login' error message, in the Ultimate Member plugin's errors filter.
	 *
	 * @param $err_codes Custom error codes array on the ultimate members plugin forms.
	 *
	 * @return $err_codes The updated error codes array.
	 */
	public function add_um_form_error_code( $err_codes ) {
		// Adds our error message code.
		$err_codes[] = 'authentication_failed';

		return $err_codes;
	}

	/**
	 * Sets the flag, if the ultimate member plugin find error on their forms.
	 *
	 * @param  $error The error message.
	 *
	 * @param  $key The error code.
	 *
	 * @return $error The error message.
	 */
	public function set_um_form_flag( $error, $key ) {
		// Check if the UM form has detected and error.
		if ( $key ) {
			$this->um_form_detected_error = true;
		}

		return $error;
	}

	/**
	 * Adds the 'sgs-token' query string after language change.
	 */
	public function add_sgs_token_to_language_switcher() {
		// Check if the language of the login/register pages is getting changed.
		if (
			$this->is_valid( 'login' ) &&
			isset( $_SERVER['HTTP_REFERER'] ) &&
			(
				false !== strpos( $_SERVER['HTTP_REFERER'], $this->options['new_slug'] ) ||
				false !== strpos( $_SERVER['HTTP_REFERER'], $this->options['register'] )
			) &&
			isset( $_GET['wp_lang'] )
		) {

			// Get the current URL.
			$current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			$parsed_url = wp_parse_url( $current_url );

			// Extract query parameters from the URL.
			$query_params = array();
			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $query_params );
			}

			// Determine which SGS token to add. Preset it to 'register'.
			$query_params['sgs-token'] = $this->options['register'];

			if ( false !== strpos( $_SERVER['HTTP_REFERER'], $this->options['new_slug'] ) ) {
				$query_params['sgs-token'] = $this->options['new_slug'];
			}

			// Sanitize all query parameters.
			foreach ( $query_params as $key => $value ) {
				$query_params[ $key ] = sanitize_text_field( $value );
			}

			// Build the URL with all the parameters.
			$redirect_url = add_query_arg( $query_params, site_url( 'wp-login.php' ) );

			// Prevent redirect loop by checking if the current URL matches the redirect URL.
			if ( true === $this->compare_urls( $current_url, $redirect_url ) ) {
				return;
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Compare two URLs if they are basically the same.
	 *
	 * @param $current_url First URL to compare.
	 *
	 * @param $redirect_url Second URL to compare.
	 *
	 * @return boolean True if the URLs are the same. False if they are different.
	 */
	public function compare_urls( $current_url, $redirect_url ) {
		// Parse the URLs
		$current_url = parse_url( $current_url );
		$redirect_url = parse_url( $redirect_url );

		// Check if both URLs have the same domain.
		if ( $current_url['host'] !== $redirect_url['host'] ) {
			return false;
		}

		// Ensure both URLs include "wp-login.php" in the path.
		if ( false === strpos( $current_url['path'], 'wp-login.php' ) || false === strpos( $redirect_url['path'], 'wp-login.php' ) ) {
			return false;
		}

		// Parse query strings into arrays for comparison.
		parse_str( $current_url['query'], $current_url_params );
		parse_str( $redirect_url['query'], $redirect_url_params );

		// Compare the total number of query parameters.
		if ( count( $current_url_params ) !== count( $redirect_url_params ) ) {
			return false;
		}

		// If a key is missing or a value is different, URLs are not equal.
		foreach ( $current_url_params as $query_key => $query_value ) {
			if (
					! array_key_exists( $query_key, $redirect_url_params ) ||
					$redirect_url_params[ $query_key ] !== $query_value
				) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Unset the permission cookie, by recreating it with expiration time in the past.
	 *
	 * @param string $type The permissions type.
	 *
	 * @param string $cookie_name The name of the cookie to unset.
	 */
	private function unset_permissions_cookie( $type ) {
		$url_parts = wp_parse_url( Helper_Service::get_site_url() );
		$home_path = trailingslashit( $url_parts['path'] );

		$cookie_name = $this->token . '-' . $type . '-' . COOKIEHASH;

		// Set the cookie with an expired time, so the browser can delete the cookie.
		setcookie(
			$cookie_name,
			$this->options['new_slug'],
			time() - 3600,
			$home_path,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		// Unset the cookie from the $_COOKIE superglobal.
		unset( $_COOKIE[ $cookie_name ] );
	}
}
