<?php
namespace SiteGround_Central\Site_Tools_Client;

/**
 * Connect Agent class.
 *
 * Handles the connection to the AI Studio agent via Avalon socket.
 *
 * @since 3.1.0
 */
class Connect_Agent {

	/**
	 * Get the current WordPress app ID from Site Tools.
	 *
	 * @since 3.1.0
	 *
	 * @return int|false The app ID or false on failure.
	 */
	public static function get_app_id() {
		// Call the Site Tools client.
		$result = Site_Tools_Client::call_site_tools_client(
			array(
				'api'      => 'app',
				'cmd'      => 'list',
				'params'   => (object) array(),
				'settings' => array( 'json' => 1 ),
			)
		);

		// Bail if we do not get the result.
		if ( empty( $result['json'] ) ) {
			return false;
		}

		// Get the current site URL and parse it.
		$home_url    = get_home_url();
		$parsed_url  = wp_parse_url( $home_url );
		$domain_name = isset( $parsed_url['host'] ) ? str_replace( 'www.', '', $parsed_url['host'] ) : '';
		$path        = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';

		// Normalize the path - ensure it starts with / and doesn't end with / (unless it's just /).
		if ( empty( $path ) ) {
			$path = '/';
		}

		// Search for the matching WordPress app.
		foreach ( $result['json'] as $app ) {
			// Skip if not a WordPress app.
			if ( empty( $app['app'] ) || 'wordpress' !== $app['app'] ) {
				continue;
			}

			// Skip if domain doesn't match.
			if ( empty( $app['domain_name'] ) || $app['domain_name'] !== $domain_name ) {
				continue;
			}

			// Get the app path, default to '/'.
			$app_path = isset( $app['path'] ) ? $app['path'] : '/';

			// Normalize paths for comparison.
			$normalized_path     = rtrim( $path, '/' );
			$normalized_app_path = rtrim( $app_path, '/' );

			// Handle root path case.
			if ( empty( $normalized_path ) ) {
				$normalized_path = '';
			}
			if ( empty( $normalized_app_path ) ) {
				$normalized_app_path = '';
			}

			// Check if paths match.
			if ( $normalized_path === $normalized_app_path ) {
				return isset( $app['id'] ) ? (int) $app['id'] : false;
			}
		}

		return false;
	}

	/**
	 * Get the domain name from Site Tools site list.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false The domain name or false on failure.
	 */
	public static function get_site_id() {
		// Call the Site Tools client to get the site list.
		$result = Site_Tools_Client::call_site_tools_client(
			array(
				'api'      => 'site',
				'cmd'      => 'list',
				'params'   => (object) array(),
				'settings' => array( 'json' => 1 ),
			)
		);

		// Bail if we do not get the result.
		if ( empty( $result['json']['name'] ) ) {
			return false;
		}

		return $result['json']['name'];
	}

	/**
	 * Get the site information needed for the AI Studio connection.
	 *
	 * @since 3.1.0
	 *
	 * @return array|false The site information array or false on failure.
	 */
	public static function get_site_info() {
		// Get the app ID first.
		$app_id = self::get_app_id();

		if ( false === $app_id ) {
			return false;
		}

		// Get the domain name from Site Tools.
		$site_id = self::get_site_id();

		if ( false === $site_id ) {
			return false;
		}

		// Get the current site URL and parse it.
		$home_url    = get_home_url();
		$parsed_url  = wp_parse_url( $home_url );
		$domain_name = isset( $parsed_url['host'] ) ? str_replace( 'www.', '', $parsed_url['host'] ) : '';

		// Get the server hostname.
		$server_hostname = gethostname();

		if ( false === $server_hostname ) {
			return false;
		}

		return array(
			'id'              => $app_id,
			'app_url'         => $home_url,
			'domain_name'     => $domain_name,
			'server_hostname' => $server_hostname,
			'site_id'         => $site_id,
		);
	}

	/**
	 * Connect to the AI Studio agent.
	 *
	 * @since 3.1.0
	 *
	 * @return array|false The result from the API or false on failure.
	 */
	public static function connect() {
		// Get the site information.
		$site_info = self::get_site_info();

		if ( false === $site_info ) {
			return false;
		}

		// Call the Site Tools client.
		$result = Site_Tools_Client::call_site_tools_client(
			array(
				'api'      => 'request-perform',
				'cmd'      => 'create',
				'settings' => array( 'json' => 1 ),
				'params'   => array(
					'api_name'       => 'ai-tools',
					'request_params' => $site_info,
				),
			)
		);

		return $result;
	}
}
