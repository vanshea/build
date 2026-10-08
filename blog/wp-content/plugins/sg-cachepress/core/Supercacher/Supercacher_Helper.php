<?php
namespace SiteGround_Optimizer\Supercacher;

use SiteGround_Optimizer\Helper\Helper;
use SiteGround_Helper\Helper_Service;

/**
 * SG CachePress class that help to split the logic in Supercacher.
 */
class Supercacher_Helper {

	/**
	 * Check if we need to change the cache header of rest requests.
	 *
	 * @since 6.0.6
	 *
	 * @param WP_HTTP_Respons $result Result to send to the client.
	 */
	public function set_rest_cache_headers( $result ) {
		// Get the optimization status.
		$is_cache_enabled = (int) get_option( 'siteground_optimizer_enable_cache', 0 );

		// Prepare the url.
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$url         = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$url        .= $server_name . $request_uri;

		// Set the cache header to false so it's skipped from caching.
		if (
			0 === $is_cache_enabled ||
			self::is_url_excluded( trailingslashit( $url ) ) ||
			self::is_query_param_excluded( $url )
		) {
			$result->header( 'X-Cache-Enabled', 'False' );
		}

		return $result;
	}
	/**
	 * Set headers cookie.
	 *
	 * @since 5.0.0
	 */
	public function set_cache_headers( $headers ) {
		if ( defined( 'WP_CLI' ) || php_sapi_name() === 'cli' ) {
			return;
		}

		if( ! Helper_Service::is_siteground() )  {
			return $headers;
		}

		$is_cache_enabled   = (int) get_option( 'siteground_optimizer_enable_cache', 0 );
		$file_cache_enabled = (int) get_option( 'siteground_optimizer_file_caching', 0 );
		$vary_user_agent    = (int) get_option( 'siteground_optimizer_user_agent_header', 0 );

		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$url         = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$url        .= $server_name . $request_uri;

		// Bail if the cache is not enabled or if the url is excluded from cache.
		if (
			( 0 === $is_cache_enabled && 0 === $file_cache_enabled ) ||
			self::is_url_excluded( $url ) ||
			self::is_post_type_excluded( $url ) ||
			self::is_query_param_excluded( $url )
		) {
			$headers['X-Cache-Enabled'] = 'False';
			return $headers;
		}

		// Add user agent header.
		if ( 1 === $vary_user_agent ) {
			$headers['Vary'] = 'User-Agent';
		}

		// Set cache header.
		$headers['X-Cache-Enabled'] = 'True';

		return $headers;
	}

	/**
	 * Check if the current url has been excluded.
	 *
	 * @since  5.0.0
	 *
	 * @param string $url The url to test.
	 *
	 * @return boolean True if it was excluded, false otherwise.
	 */
	public static function is_url_excluded( $url ) {
		// Get excluded urls.
		$parts = apply_filters( 'sgo_exclude_urls_from_cache', \get_option( 'siteground_optimizer_excluded_urls', array() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.

		// Bail if there are no excluded urls.
		if ( empty( $parts ) ) {
			return false;
		}

		// Ensure the function exists before calling it.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// If the WPML plugins is active, remove the language post-fix from the home URL.
		if ( \is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			$normalized_home_url = self::remove_wpml_language_postfix( $parts );
		}

		// Prepare the url parts for being used as regex.
		$prepared_parts = array_map(
			function( $item ) {
				return str_replace( '\*', '.*', preg_quote( $item, '/' ) );
			}, $parts
		);

		// Build the regular expression.
		$regex = sprintf(
			'/%s(%s)$/i',
			preg_quote( $normalized_home_url ?? home_url(), '/' ), // Add the home url in the beginning of the regex.
			implode( '|', $prepared_parts ) // Then add each part.
		);

		// Check if the current url matches any of the excluded urls.
		preg_match( $regex, $url, $matches );

		// The url is excluded if matched the regular expression.
		return ! empty( $matches ) ? true : false;
	}

	/**
	 * Check if the curent url's post type has been excluded.
	 *
	 * @since  5.7.0
	 *
	 * @param  string $url The url to check.
	 *
	 * @return boolean True if the url matches an excluded type, false otherwise.
	 */
	public static function is_post_type_excluded( $url ) {
		// Get excluded post_types.
		$post_types = \get_option( 'siteground_optimizer_post_types_exclude', array() );

		// Bail if there are no excluded post types.
		if ( empty( $post_types ) ) {
			return false;
		}

		// We don't want to cache page builder edit page.
		if ( Helper::check_for_builders() ) {
			return false;
		}

		// Get the post/page ID.
		$post_id = url_to_postid( $url );

		// Bail if the page is not found.
		if ( 0 === $post_id ) {
			return false;
		}

		// Bail if we are on the home page.
		if ( get_option( 'page_on_front' ) === $post_id ) {
			return false;
		}

		// Get the post type.
		$post_type = get_post_type( $post_id );

		// Check if the post type is in the exclude list.
		if ( in_array( $post_type, $post_types ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the URL contains excluded query parameters.
	 *
	 * @param $url The URL to check.
	 *
	 * @return boolean True if the URL contains excluded parameters, false otherwise.
	 */
	public static function is_query_param_excluded( $url ) {
		// Get the excluded parameters, if there are such.
		$excluded_params = apply_filters( 'sgo_bypass_query_params', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.

		if ( empty( $excluded_params ) ) {
			return false;
		}

		// Get the query parameters.
		$parsed_params = wp_parse_url( $url, PHP_URL_QUERY );

		if ( empty( $parsed_params ) ) {
			return false;
		}

		// Convert the parameters into array.
		$query_params = array();
		parse_str( $parsed_params, $query_params );

		// Check if excluded parameters, exist in the query string.
		foreach ( $excluded_params as $param ) {
			if ( array_key_exists( $param, $query_params ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Removes the language post-fix from the home_url() that the WPML plugins adds.
	 *
	 * @param string $parts The URLs excluded from caching.
	 *
	 * @return null|string $normalized_home_url The home URL without the language post-fix.
	 */
	public static function remove_wpml_language_postfix( $parts ) {
		$home_url_path = wp_parse_url( home_url(), PHP_URL_PATH );

		// Checks if there is a language post-fix, eg. /fr.
		$last_dir = basename( $home_url_path ?? '' );

		if ( ! empty( $last_dir ) ) {
			foreach ( $parts as $excluded_url ) {
				if ( 1 === strpos( $excluded_url, $last_dir ) ) {
					$normalized_home_url = rtrim( str_replace( '/' . $last_dir, '', home_url() ), '/' );

					return $normalized_home_url;
				}
			}
		}

		return null;
	}
}
