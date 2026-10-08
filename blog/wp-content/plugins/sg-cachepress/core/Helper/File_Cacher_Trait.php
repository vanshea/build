<?php
namespace SiteGround_Optimizer\Helper;

/**
 * Trait used for factory pattern in the plugin.
 */
trait File_Cacher_Trait {

	/**
	 * Build a cache partition key for a logged-in WordPress session.
	 *
	 * The complete cookie is used so an untrusted username field cannot select
	 * another user's cache partition before WordPress authentication is loaded.
	 *
	 * @since 7.8.3
	 *
	 * @param mixed  $logged_in_cookie The raw logged-in cookie value.
	 * @param string $cache_secret_key The file cache secret.
	 *
	 * @return string|false The session cache key, or false for an invalid cookie.
	 */
	public function get_logged_in_cache_key( $logged_in_cookie, $cache_secret_key ) {
		if (
			! is_string( $logged_in_cookie ) ||
			'' === $logged_in_cookie ||
			! is_string( $cache_secret_key ) ||
			'' === $cache_secret_key
		) {
			return false;
		}

		$cookie_parts = explode( '|', $logged_in_cookie );

		if (
			4 !== count( $cookie_parts ) ||
			'' === $cookie_parts[0] ||
			'' === $cookie_parts[1] ||
			'' === $cookie_parts[2] ||
			'' === $cookie_parts[3] ||
			1 !== preg_match( '/^[0-9]+$/', $cookie_parts[1] ) ||
			(int) $cookie_parts[1] < time()
		) {
			return false;
		}

		return hash_hmac( 'sha256', $logged_in_cookie, $cache_secret_key );
	}

	/**
	 * Checks if the current request is cacheable
	 *
	 * @since 7.0.0
	 *
	 * @return bool Returns true if the request is cacheable, false if not.
	 */
	public function is_cacheable() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] !== 'GET' ) { //phpcs:ignore
			return;
		}

		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}

		// Never collapse an authenticated request into a shared cache key.
		if ( $this->has_authorization_header() ) {
			return false;
		}

		// Check if this is an ajax request.
		if ( $this->doing_ajax() ) {
			return false;
		}

		// Check if this is an cron request.
		if ( $this->doing_cron() ) {
			return false;
		}

		if ( $this->is_content_type_not_supported() ) {
			return false;
		}

		if ( $this->logged_in_cache ) {
			$this->bypass_cookies = array_diff( $this->bypass_cookies, array( 'wordpress_logged_in_' ) );
		}

		if ( $this->has_bypass_cookies() ) {
			return false;
		}

		if ( $this->has_skip_cache_query_params() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check for query args that shoundn't be cached.
	 *
	 * @since  7.0.0
	 *
	 * @return boolean True/False.
	 */
	public function has_skip_cache_query_params() {
		// Iterate through the query array and check for skip cache params.
		foreach ( $_GET as $param => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $param, $this->bypass_query_params, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the request contains bypass cookies.
	 *
	 * @since  7.0.0
	 *
	 * @return boolean True/False
	 */
	public function has_bypass_cookies() {
		if ( empty( $_COOKIE ) ) {
			return false;
		}

		// Check if any of the users' cookies are one of the bypass ones.
		foreach ( $this->bypass_cookies as $bypass_cookie ) {
			foreach ( array_keys( $_COOKIE ) as $cookie ) {
				// Bail if a bypass cookie was found.
				if ( substr( $cookie, 0, strlen( $bypass_cookie ) ) === $bypass_cookie ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the content is supported.
	 *
	 * @since  7.0.0
	 *
	 * @return boolean Tru/False
	 */
	public function is_content_type_not_supported() {
		if (
			empty( $_SERVER['HTTP_ACCEPT'] ) ||
			false === strpos( $_SERVER['HTTP_ACCEPT'], 'text/html' ) // phpcs:ignore
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get the current url.
	 *
	 * @since  7.0.0
	 *
	 * @return string The current url.
	 */
	public static function get_current_url() {
		$protocol = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http'; // phpcs:ignore

		// Build the current url.
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //phpcs:ignore
	}

	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
	 *
	 * @since  5.9.0
	 *
	 * @return boolean
	 */
	public static function is_mobile() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$is_mobile = false;
		} elseif ( @strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false // phpcs:ignore
			|| @strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) { // phpcs:ignore
				$is_mobile = true;
		} else {
			$is_mobile = false;
		}

		return $is_mobile;
	}


	/**
	 * Gets the GET parameters from the request, filters out the whitelisted ones ( that won't be taken into account ), takes their values and hashes the string with the serialized data
	 *
	 * @since 7.0.0
	 *
	 * @return string The hashed serialized query string, empty if none
	 */
	public function get_filename() {
		// Version the file name so entries created by older cache policies are not served.
		$base_filename = self::is_mobile() ? 'index-mobile-v2' : 'index-v2';

		// Retrieve the GET parameters.
		$get_params = $_GET; //phpcs:ignore

		// Iterate through the query array and unset the unneeded params.
		foreach ( $get_params as $param => $value ) {
			if ( ! in_array( $param, $this->ignored_query_params, true ) ) {
				continue;
			}

			unset( $get_params[ $param ] );
		}

		// Check if any query params are left.
		if ( empty( $get_params ) ) {
			return $base_filename . '.html';
		}

		// Stringify the array and return the value.
		return $base_filename . '-' . md5( implode( '', $get_params ) ) . '.html';
	}

	/**
	 * Custom implementations of doing_ajax function
	 *
	 * @since  7.0.0
	 *
	 * @return bool True/false
	 */
	public function doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Custom implementations of doing_cron function
	 *
	 * @since  7.0.0
	 *
	 * @return bool True/false
	 */
	public function doing_cron() {
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	/**
	 * Check if the request contains an authorization identity.
	 *
	 * @since 7.8.3
	 *
	 * @return boolean True if an authorization identity exists, false otherwise.
	 */
	public function has_authorization_header() {
		$authorization_keys = array(
			'HTTP_AUTHORIZATION',
			'REDIRECT_HTTP_AUTHORIZATION',
			'PHP_AUTH_USER',
			'PHP_AUTH_PW',
			'PHP_AUTH_DIGEST',
			'REMOTE_USER',
			'AUTH_TYPE',
		);

		foreach ( $authorization_keys as $authorization_key ) {
			if ( isset( $_SERVER[ $authorization_key ] ) && '' !== $_SERVER[ $authorization_key ] ) { // phpcs:ignore
				return true;
			}
		}

		foreach ( $_SERVER as $server_key => $server_value ) { // phpcs:ignore
			if (
				is_string( $server_key ) &&
				preg_match( '/^(?:REDIRECT_)+HTTP_AUTHORIZATION$/', $server_key ) &&
				'' !== $server_value
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the response headers waiting to be sent.
	 *
	 * @since 7.8.3
	 *
	 * @return array|false Response headers, or false when they cannot be read.
	 */
	public function get_response_headers() {
		$headers = false;

		if ( function_exists( 'headers_list' ) ) {
			$headers = \headers_list();
			if ( ! empty( $headers ) ) {
				return $headers;
			}
		}

		if ( function_exists( 'apache_response_headers' ) ) {
			$apache_headers = \apache_response_headers();
			if ( is_array( $apache_headers ) && ! empty( $apache_headers ) ) {
				return $apache_headers;
			}
		}

		return is_array( $headers ) ? $headers : false;
	}

	/**
	 * Normalize numeric and associative response header representations.
	 *
	 * @since 7.8.3
	 *
	 * @param array $headers Response headers.
	 *
	 * @return array Headers keyed by lowercase name, retaining duplicate values.
	 */
	public function normalize_response_headers( $headers ) {
		$normalized_headers = array();

		foreach ( $headers as $header => $values ) {
			if ( is_int( $header ) ) {
				if ( ! is_string( $values ) || false === strpos( $values, ':' ) ) {
					continue;
				}

				list( $header, $values ) = explode( ':', $values, 2 );
			}

			if ( ! is_string( $header ) || '' === trim( $header ) ) {
				continue;
			}

			$header = strtolower( trim( $header ) );
			$values = is_array( $values ) ? $values : array( $values );

			foreach ( $values as $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}

				$normalized_headers[ $header ][] = trim( (string) $value );
			}
		}

		return $normalized_headers;
	}

	/**
	 * Check header values for exact comma-separated directive names.
	 *
	 * @since 7.8.3
	 *
	 * @param array $values     Header values.
	 * @param array $directives Directive names to find.
	 *
	 * @return boolean True if a directive is present, false otherwise.
	 */
	public function has_header_directive( $values, $directives ) {
		foreach ( $values as $value ) {
			foreach ( explode( ',', $value ) as $directive ) {
				$directive_parts = explode( '=', $directive, 2 );
				$directive_name  = strtolower( trim( $directive_parts[0] ) );

				if ( in_array( $directive_name, $directives, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if Cache-Control values match a canonical WordPress logged-in response.
	 *
	 * @since 7.8.3
	 *
	 * @param array $values Cache-Control header values.
	 * @param bool  $require_marker Whether the internal cache marker must be present.
	 *
	 * @return boolean True for a canonical WordPress no-cache value, false otherwise.
	 */
	public function is_wordpress_nocache_headers( $values, $require_marker = false ) {
		if ( ! is_array( $values ) ) {
			return false;
		}

		$directives = array();

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				return false;
			}

			foreach ( explode( ',', strtolower( $value ) ) as $directive ) {
				$directive_parts = array_map( 'trim', explode( '=', trim( $directive ), 2 ) );
				$directives[]    = implode( '=', $directive_parts );
			}
		}

		sort( $directives );

		$required_marker = $require_marker ? array( 'sgo-private-cache' ) : array();

		$wordpress_nocache_headers = array(
			array_merge( array( 'max-age=0', 'must-revalidate', 'no-cache' ), $required_marker ),
			array_merge( array( 'max-age=0', 'must-revalidate', 'no-cache', 'no-store', 'private' ), $required_marker ),
		);

		return in_array( $directives, $wordpress_nocache_headers, true );
	}

	/**
	 * Check whether a Vary header names a dimension missing from the cache key.
	 *
	 * @since 7.8.3
	 *
	 * @param array $values Vary header values.
	 *
	 * @return boolean True if the response varies on an unsupported dimension.
	 */
	public function has_unsupported_vary_header( $values ) {
		$allowed_vary_headers = apply_filters(
			'sgo_file_cache_allowed_vary_headers', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.
			array( 'accept-encoding', 'user-agent' )
		);

		foreach ( $values as $value ) {
			foreach ( explode( ',', $value ) as $vary_header ) {
				$vary_header = strtolower( trim( $vary_header ) );

				if ( '' !== $vary_header && ! in_array( $vary_header, $allowed_vary_headers, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check for nocache headers.
	 *
	 * @since  7.0.0
	 *
	 * @param boolean $allow_marked_wordpress_nocache Whether to allow a marked WordPress logged-in no-cache value.
	 *
	 * @return boolean True if nocache headers exist, false otherwise.
	 */
	public function has_nocache_headers( $allow_marked_wordpress_nocache = false ) {
		$response_headers = $this->get_response_headers();

		// Do not write when the response policy cannot be inspected.
		if ( false === $response_headers ) {
			return true;
		}

		$response_headers = $this->normalize_response_headers( $response_headers );

		if ( array_key_exists( 'set-cookie', $response_headers ) ) {
			return true;
		}

		if (
			isset( $response_headers['cache-control'] ) &&
			! ( $allow_marked_wordpress_nocache && $this->is_wordpress_nocache_headers( $response_headers['cache-control'], true ) ) &&
			$this->has_header_directive( $response_headers['cache-control'], array( 'private', 'no-cache', 'no-store' ) )
		) {
			return true;
		}

		if (
			isset( $response_headers['pragma'] ) &&
			$this->has_header_directive( $response_headers['pragma'], array( 'no-cache' ) )
		) {
			return true;
		}

		if (
			isset( $response_headers['vary'] ) &&
			$this->has_unsupported_vary_header( $response_headers['vary'] )
		) {
			return true;
		}

		// Define the ignore cache headers.
		$ignore_headers = apply_filters(
			'sgo_file_cache_ignore_headers', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.
			array(
				'cache-control' => 'no-cache',
			)
		);

		if ( is_array( $ignore_headers ) ) {
			foreach ( $ignore_headers as $header => $matches ) {
				$header = strtolower( $header );

				if ( ! isset( $response_headers[ $header ] ) ) {
					continue;
				}

				foreach ( (array) $matches as $match ) {
					if ( ! is_scalar( $match ) ) {
						continue;
					}

					// The built-in Cache-Control parser handles no-cache as a directive.
					if ( 'cache-control' === $header && 'no-cache' === strtolower( trim( (string) $match ) ) ) {
						continue;
					}

					foreach ( $response_headers[ $header ] as $value ) {
						if ( false !== stripos( $value, (string) $match ) ) {
							return true;
						}
					}
				}
			}
		}

		// We can cache the page.
		return false;
	}

}
