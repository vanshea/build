<?php
/**
 * Helper class for AI Studio integration.
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Helper;

use SG_AI_Studio\HelperAuth\SignApiClient;

/**
 * Helper class for utility functions.
 */
class Helper {
	/**
	 * Check if the plugin is running in staging environment
	 *
	 * @return bool True if staging environment, false otherwise
	 * @since 1.1.0
	 */
	public static function is_staging_environment(): bool {
		return defined( 'SG_AI_STUDIO_ENV' ) && \SG_AI_STUDIO_ENV === 'staging';
	}

	/**
	 * Send message to AI Studio API
	 *
	 * @param string $message The user message.
	 * @param string $api_key AI Studio API key.
	 * @param string $thread_id Optional thread ID for continuing conversations.
	 * @param string $agent Optional agent name.
	 * @return array|\WP_Error The API response or WP_Error.
	 */
	public static function send_to_aistudio( $message, $api_key, $thread_id = '', $agent = '' ) {
		if ( self::is_staging_environment() ) {
			$hostname = 'https://api.staging.studio.siteground.ai';
		} else {
			$hostname = 'https://api.studio.siteground.ai';
		}

		$url     = $hostname . '/chat/v1/wp-reply';
		$headers = array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
			'Connection'    => 'keep-alive',
			'Accept'        => '*/*',

		);
		$body = array(
			'question'    => $message,
			'wp_api_base' => get_rest_url(),
			'service'     => 1,
		);

		// Add thread_id to request if provided.
		if ( ! empty( $thread_id ) ) {
			$body['thread_id'] = $thread_id;
		}

		if ( ! empty( $agent ) ) {
			$body['agent'] = $agent;
		}

		$args = array(
			'headers'     => $headers,
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'timeout'     => 90000000,
			'redirection' => 45,
			'sslverify'   => false,
		);

		return wp_remote_post( $url, $args );
	}

	/**
	 * Process chat request and handle responses
	 *
	 * @param string $message The user message.
	 * @param string $api_key AI Studio API key.
	 * @param string $thread_id Optional thread ID for continuing conversations.
	 * @param string $agent Optional agent name.
	 * @param int    $post_id Optional post ID to attach images to.
	 * @return array The response data.
	 */
	public static function process_chat_request( $message, $api_key, $thread_id = '', $agent = '', $post_id = 0 ) {
		$response = self::send_to_aistudio( $message, $api_key, $thread_id, $agent );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => esc_html( $response->get_error_message() ),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Unexpected response code: %d', $response_code ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$message       = '';
		$thread_id     = '';
		$image_ids     = array();
		$image_url_map = array(); // Map original URLs to WordPress URLs.

		// Parse response body - handle both NDJSON (newline-delimited) and streaming formats.
		$raw_body = $response['body'];

		// Try parsing as newline-delimited JSON first.
		$lines = explode( "\n", $raw_body );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			$decoded_line = json_decode( $line, true );

			// If the line is not valid JSON, skip it.
			if ( null === $decoded_line ) {
				continue;
			}

			if ( ! empty( $decoded_line['type'] ) && 'text_delta' === $decoded_line['type'] ) {
				$message .= $decoded_line['content'];
			}

			// Handle image generation tool results.
			if ( ! empty( $decoded_line['type'] ) && 'tool_result' === $decoded_line['type'] ) {
				if ( ! empty( $decoded_line['content']['name'] ) && 'image_generation' === $decoded_line['content']['name'] ) {
					$response_data    = $decoded_line['content']['response'] ?? null;
					$image_data       = $response_data['output'] ?? null;
					$output_resources = $response_data['output_resources'] ?? array();

					if ( $image_data && 'success' === $image_data['status'] && ! empty( $output_resources ) ) {
						foreach ( $output_resources as $resource ) {
							if ( ! empty( $resource['url'] ) ) {
								$attachment_id = self::upload_image_from_url( $resource['url'], $image_data['prompt'] ?? '', $post_id );
								if ( $attachment_id ) {
									$image_ids[] = $attachment_id;
									// Map original URL to WordPress attachment URL.
									$wp_image_url                      = wp_get_attachment_url( $attachment_id );
									$image_url_map[ $resource['url'] ] = $wp_image_url;
								}
							}
						}
					}
				}
			}

			// Extract thread_id if present.
			if ( ! empty( $decoded_line['chat_id'] ) ) {
				$thread_id = $decoded_line['chat_id'];
			}
		}

		// If we have images, replace original URLs with WordPress URLs and clean up the message.
		if ( ! empty( $image_url_map ) ) {
			// Replace image URLs in the message.
			foreach ( $image_url_map as $original_url => $wp_url ) {
				$message = str_replace( $original_url, $wp_url, $message );
			}

			// Remove all unwanted HTML tags and structure in one pass.
			$message = preg_replace(
				array(
					'/<img[^>]*>/i',                           // Remove img tags.
					'/<h1[^>]*>.*?<\/h1>/is',                  // Remove h1 tags.
					'/<p[^>]*>.*?<\/p>/is',                    // Remove p tags.
					'/<head[^>]*>.*?<\/head>/is',              // Remove head section.
					'/<body[^>]*>(.*?)<\/body>/is',            // Extract body content.
					'/<!DOCTYPE[^>]*>|<\/?html[^>]*>/is',      // Remove DOCTYPE and html tags.
					'/\s+/',                                   // Clean up whitespace.
				),
				array( '', '', '', '', '$1', '', ' ' ),
				$message
			);

			$message = trim( $message );

			// If message is now empty or just whitespace/empty tags, clear it completely.
			if ( preg_match( '/^\s*(<[^>]+>\s*<\/[^>]+>\s*)*$/s', $message ) ) {
				$message = '';
			}
		}

		if ( isset( $body['error'] ) ) {
			return array(
				'success' => false,
				'message' => sanitize_text_field( $body['error']['message'] ),
			);
		}

		// Allow a more permissive set of HTML tags and formatting to support rich text.
		// This includes headings, lists, blockquotes, and other formatting elements.
		$allowed_html = array(
			'p'          => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'blockquote' => array(),
			'pre'        => array(),
			'code'       => array(),
			'strong'     => array(),
			'em'         => array(),
			'b'          => array(),
			'i'          => array(),
			'a'          => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
			'br'         => array(),
			'hr'         => array(),
			'div'        => array(
				'class' => array(),
			),
			'span'       => array(
				'class' => array(),
			),
		);

		$result = array(
			'success'   => true,
			'reply'     => $message, // Return raw HTML without sanitization.
			'thread_id' => $thread_id,
		);

		// Add image IDs and URLs if any were generated.
		if ( ! empty( $image_ids ) ) {
			$result['image_ids'] = $image_ids;
			// Also include image URLs for easier rendering.
			$result['images'] = array();
			foreach ( $image_ids as $image_id ) {
				$result['images'][] = array(
					'id'  => $image_id,
					'url' => wp_get_attachment_url( $image_id ),
				);
			}
		}

		return $result;
	}

	/**
	 * Initialize client authentication with dynamic-keys API
	 *
	 * @param string $client_id The client ID.
	 * @param string $link_id The link ID.
	 * @return array|\WP_Error The API response or error.
	 */
	public static function init_client_auth( $client_id, $link_id ) {
		// Make REST API call to dynamic-keys service.
		if ( self::is_staging_environment() ) {
			$hostname = 'https://api.staging.studio.siteground.ai';
		} else {
			$hostname = 'https://api.studio.siteground.ai';
		}
		$api_url      = $hostname . '/dynamic-keys/v1/init-client';
		$api_response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => json_encode(
					array(
						'client'  => $client_id,
						'link_id' => $link_id,
					)
				),

				'timeout' => 30,
			)
		);

		// Check if API call failed.
		if ( is_wp_error( $api_response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to connect to dynamic-keys API.', 'sg-ai-studio' ),
				'error'   => $api_response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $api_response );
		$response_body = wp_remote_retrieve_body( $api_response );

		// Check if API returned an error status.
		if ( $response_code < 200 || $response_code >= 300 ) {
			return array(
				'success'      => false,
				'message'      => __( 'Dynamic-keys API returned an error.', 'sg-ai-studio' ),
				'api_response' => $response_body,
				'status_code'  => $response_code,
			);
		}

		$data    = json_decode( $response_body, true )['data'];
		$client  = $data['client'];
		$service = $data['service'];

		update_option( 'sg_ai_studio_client_key', $client );
		update_option( 'sg_ai_studio_service_key', $service );

		// Schedule the key refresh cron job after successful authentication.
		self::schedule_key_refresh_cron();

		return array(
			'success'      => true,
			'message'      => __( 'Authentication initialized successfully.', 'sg-ai-studio' ),
			'api_response' => $response_body,
		);
	}

	/**
	 * Generate authentication token for dynamic-keys service
	 *
	 * @param string $client_id The client ID.
	 * @return string|false The authentication token or false on failure.
	 */
	public static function generate_dynamic_keys_token( $client_id = 0 ) {
		if ( 0 === $client_id ) {
			$client_id = get_option( 'sg_ai_studio_client_id' );
		}
		try {
			// Include the HelperAuth class.
			require_once plugin_dir_path( __FILE__ ) . 'HelperAuth.php';
			// Initialize SignApiClient with dynamic-keys service configuration.
			$auth_client = new SignApiClient(
				$client_id,
				'dynamic-keys',
				'dynamic-keys',
				10.0,
				false,
				'api.staging.studio.siteground.ai',
				null,
				3600,
				'ES384'
			);

			// Generate the authentication token.
			return $auth_client->get_auth_token();

		} catch ( \Exception $e ) {

			return false;
		}
	}

	/**
	 * Refresh client authentication keys with dynamic-keys API
	 *
	 * @param string $client The client ID.
	 * @return array|\WP_Error The API response or error.
	 */
	public static function refresh_client_keys( $client ) {
		// Generate authentication token for dynamic-keys service.
		$auth_token = self::generate_dynamic_keys_token( $client );

		if ( false === $auth_token ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to generate authentication token for dynamic-keys API.', 'sg-ai-studio' ),
			);
		}

		// Make REST API call to dynamic-keys service.
		if ( self::is_staging_environment() ) {
			$api_url = 'https://api.staging.studio.siteground.ai/dynamic-keys/v1/key-refresh';
		} else {
			$api_url = 'https://api.studio.siteground.ai/dynamic-keys/v1/key-refresh';
		}
		$api_response = wp_remote_post(
			$api_url,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'body'    => json_encode(
					array(
						'client' => $client,
					)
				),
				'timeout' => 30,
			)
		);

		// Check if API call failed.
		if ( is_wp_error( $api_response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to connect to dynamic-keys API.', 'sg-ai-studio' ),
				'error'   => $api_response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $api_response );
		$response_body = wp_remote_retrieve_body( $api_response );
		// Check if API returned an error status.
		if ( $response_code < 200 || $response_code >= 300 ) {
			return array(
				'success'      => false,
				'message'      => __( 'Dynamic-keys API returned an error.', 'sg-ai-studio' ),
				'api_response' => $response_body,
				'status_code'  => $response_code,
			);
		}

		$data    = json_decode( $response_body, true )['data'];
		$client  = $data['client'];
		$service = $data['service'];

		update_option( 'sg_ai_studio_client_key', $client );
		update_option( 'sg_ai_studio_service_key', $service );

		return array(
			'success'      => true,
			'message'      => __( 'Authentication keys refreshed successfully.', 'sg-ai-studio' ),
			'api_response' => $response_body,
		);
	}

	/**
	 * Schedule the key refresh cron job for every 29 days
	 */
	public static function schedule_key_refresh_cron() {
		if ( ! wp_next_scheduled( 'sg_ai_studio_key_refresh_cron' ) ) {
			wp_schedule_event( time() + ( 29 * DAY_IN_SECONDS ), 'sg_ai_studio_29_days', 'sg_ai_studio_key_refresh_cron' );
		}
	}

	/**
	 * WordPress cron job hook for refreshing authentication keys
	 */
	public static function cron_refresh_keys() {
		$client_key = get_option( 'sg_ai_studio_client_id', '' );

		if ( empty( $client_key ) ) {

			return;
		}

		$result = self::refresh_client_keys( $client_key );

		if ( ! $result['success'] ) {

		} else {

		}
	}

	/**
	 * Check if wp cron is disabled and send error message.
	 *
	 * @since  1.0.0
	 */
	public static function is_cron_disabled() {
		if ( defined( 'SG_UNIX_CRON' ) && true === SG_UNIX_CRON ) {
			return 0;
		}

		if ( defined( 'DISABLE_WP_CRON' ) && true === DISABLE_WP_CRON ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Upload image from URL to WordPress media library
	 *
	 * @param string $image_url The URL of the image to upload.
	 * @param string $description Optional description for the image.
	 * @param int    $post_id Optional post ID to attach the image to.
	 * @return int|false The attachment ID on success, false on failure.
	 */
	public static function upload_image_from_url( $image_url, $description = '', $post_id = 0 ) {
		// Download the image.
		$response = wp_remote_get( $image_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			return false;
		}

		// Get the content type from the response headers.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		// Whitelist of allowed image MIME types.
		$allowed_mime_types = array(
			'image/jpeg' => 'jpg',
			'image/jpg'  => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		);

		// Validate content type against whitelist.
		if ( empty( $content_type ) || ! isset( $allowed_mime_types[ $content_type ] ) ) {
			return false;
		}

		$extension = $allowed_mime_types[ $content_type ];

		// Verify file content matches the declared MIME type using WordPress file type checking.
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( $finfo ) {
			$detected_mime = finfo_buffer( $finfo, $image_data );
			finfo_close( $finfo );

			// Check if detected MIME type matches the declared content-type.
			if ( $detected_mime !== $content_type ) {
				return false;
			}
		}

		// Additional check: Verify it's actually an image using getimagesizefromstring.
		$image_info = getimagesizefromstring( $image_data );
		if ( false === $image_info || ! is_array( $image_info ) ) {
			return false;
		}

		// Verify the detected image MIME type matches our allowed types.
		if ( ! in_array( $image_info['mime'], array_keys( $allowed_mime_types ), true ) ) {
			return false;
		}

		// Generate a unique filename.
		$filename = 'ai-generated-' . uniqid() . '.' . $extension;

		// Upload to WordPress uploads directory.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$upload = wp_upload_bits( $filename, null, $image_data );

		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		// Prepare attachment data.
		$file_path = $upload['file'];
		$file_type = wp_check_filetype( $filename, null );

		$attachment = array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => sanitize_text_field( $description ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the attachment with parent post ID.
		$attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );

		if ( is_wp_error( $attachment_id ) || 0 === $attachment_id ) {
			return false;
		}

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		return $attachment_id;
	}

	/**
	 * Check JWT authorization for REST API endpoints
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has valid JWT authorization, WP_Error object otherwise.
	 */
	public static function check_jwt_authorization( $request ) {
		$authorization_header = $request->get_header( 'Authorization' );
		require_once plugin_dir_path( __FILE__ ) . 'HelperAuth.php';

		if ( empty( $authorization_header ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Authorization header missing.', 'sg-ai-studio' ),
				array( 'status' => 401 )
			);
		}

		$token_parts = explode( ' ', $authorization_header, 2 );
		if ( count( $token_parts ) !== 2 || strtolower( $token_parts[0] ) !== 'bearer' ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Invalid authorization header format.', 'sg-ai-studio' ),
				array( 'status' => 401 )
			);
		}

		$token = $token_parts[1];

		try {
			$sign_client = new SignApiClient(
				'wp-plugin-client',
				'ai-tools',
				'dynamic-keys'
			);

			// Just decode the token to validate it.
			$sign_client->decode( $token );
		} catch ( \SG_AI_Studio\HelperAuth\SignApiAuthException $e ) {
			return new \WP_Error(
				'rest_forbidden',
				/* translators: %s is the error message from token validation. */
				sprintf( __( 'Token validation failed: %s', 'sg-ai-studio' ), $e->getMessage() ),
				array( 'status' => 401 )
			);
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'rest_server_error',
				/* translators: %s is the authentication error message. */
				sprintf( __( 'Authentication error: %s', 'sg-ai-studio' ), $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Clean up all plugin data from the database
	 *
	 * @return array Array with success status and optional errors.
	 */
	public static function cleanup_plugin_data() {
		global $wpdb;

		$errors = array();

		// Delete all plugin options.
		$options = array(
			'sg_ai_studio_client_id',
			'sg_ai_studio_link_id',
			'sg_ai_studio_client_key',
			'sg_ai_studio_service_key',
			'sg_ai_studio_api_key',
			'sg_ai_studio_powermode',
			'sg_ai_studio_activity_log_lifetime',
			'sg_ai_studio_settings',
			'sg_ai_studio_connected',
		);

		foreach( $options as $option ) {
				delete_option($option);
		}

		// Delete all transients related to the plugin.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$transients_deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_sg_ai_studio_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_sg_ai_studio_' ) . '%'
			)
		);

		if ( false === $transients_deleted ) {
			$errors[] = 'Failed to delete transients';
		}

		// Clear scheduled cron jobs.
		$cron_hooks = array(
			'sg_ai_studio_clear_logs_cron',
			'sg_ai_studio_key_refresh_cron',
		);

		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				if ( ! wp_unschedule_event( $timestamp, $hook ) ) {
					$errors[] = sprintf( 'Failed to unschedule cron job: %s', $hook );
				}
			}
		}

		// Drop plugin database tables.
		$tables = array(
			$wpdb->prefix . 'sg_ai_log_events',
		);

		foreach ( $tables as $table ) {
			// Check if table exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

			if ( $table_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
				if ( false === $result ) {
					$errors[] = sprintf( 'Failed to drop table: %s', $table );
				}
			}
		}

		return array(
			'success' => empty( $errors ),
			'errors'  => $errors,
		);
	}
	/**
	 * Generates a token for AI Studio Service.
	 *
	 * @return string $token
	 */
	public static function generate_ai_studio_token() {
		$auth_token = '';
		// Get client_id from WordPress options or request parameters.
		$client_id = get_option( 'sg_ai_studio_client_id' );
		if ( empty( $client_id ) ) {
			error_log( 'No client_id set.' );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return $auth_token;
		}

		require_once plugin_dir_path( __FILE__ ) . 'HelperAuth.php';
		// Initialize SignApiClient with WordPress service configuration.
		$auth_client = new SignApiClient(
			$client_id,
			'ai-tools',
			'dynamic-keys',
			10.0,
			false,
			'api.staging.studio.siteground.ai',
			null,
			3600,
			'ES384'
		);

		// Generate the authentication token.
		try {
			$auth_token = $auth_client->get_auth_token();
		} catch ( \Exception $e ) {
			error_log( 'Failed to generate authentication token: ' . $e->getMessage() );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$auth_token = false;
		}

		if ( false === $auth_token ) {
			error_log( 'Authentication token is invalid or could not be generated.' );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $auth_token;
	}

	/**
	 * Validate force parameter value.
	 * Only accepts true, 'true', or 1 as true values.
	 *
	 * @param mixed $value The value to validate.
	 * @return bool True if value is true, 'true', or 1, false otherwise.
	 */
	public static function validate_force_param( $value ) {
		return $value === true || $value === 'true' || $value === 1;
	}

	/**
	 * Process chat request for Gutenberg blocks (preserves block markup)
	 * This is an extension of process_chat_request specifically for Gutenberg block generation
	 * that skips aggressive HTML cleanup to preserve block structure.
	 *
	 * @param string $message The user message.
	 * @param string $api_key AI Studio API key.
	 * @param string $thread_id Optional thread ID for continuing conversations.
	 * @param string $agent Optional agent name.
	 * @param int    $post_id Optional post ID to attach images to.
	 * @return array The response data.
	 */
	public static function process_gutenberg_block_request( $message, $api_key, $thread_id = '', $agent = '', $post_id = 0 ) {
		$response = self::send_to_aistudio( $message, $api_key, $thread_id, $agent );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => esc_html( $response->get_error_message() ),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Unexpected response code: %d', $response_code ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$message       = '';
		$thread_id     = '';
		$image_ids     = array();
		$image_url_map = array(); // Map original URLs to WordPress URLs.

		// Parse response body - handle both NDJSON (newline-delimited) and streaming formats.
		$raw_body = $response['body'];

		// Try parsing as newline-delimited JSON first.
		$lines = explode( "\n", $raw_body );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			$decoded_line = json_decode( $line, true );

			// If the line is not valid JSON, skip it.
			if ( null === $decoded_line ) {
				continue;
			}

			if ( ! empty( $decoded_line['type'] ) && 'text_delta' === $decoded_line['type'] ) {
				$message .= $decoded_line['content'];
			}

			// Handle image generation tool results.
			if ( ! empty( $decoded_line['type'] ) && 'tool_result' === $decoded_line['type'] ) {
				if ( ! empty( $decoded_line['content']['name'] ) && 'image_generation' === $decoded_line['content']['name'] ) {
					$response_data = $decoded_line['content']['response'] ?? null;
					$image_data = $response_data['output'] ?? null;
					$output_resources = $response_data['output_resources'] ?? array();

					if ( $image_data && 'success' === $image_data['status'] && ! empty( $output_resources ) ) {
						foreach ( $output_resources as $resource ) {
							if ( ! empty( $resource['url'] ) ) {
								$attachment_id = self::upload_image_from_url( $resource['url'], $image_data['prompt'] ?? '', $post_id );
								if ( $attachment_id ) {
									$image_ids[] = $attachment_id;
									// Map original URL to WordPress attachment URL.
									$wp_image_url                       = wp_get_attachment_url( $attachment_id );
									$image_url_map[ $resource['url'] ] = $wp_image_url;
								}
							}
						}
					}
				}
			}

			// Extract thread_id if present.
			if ( ! empty( $decoded_line['chat_id'] ) ) {
				$thread_id = $decoded_line['chat_id'];
			}
		}

		// For Gutenberg blocks: only replace image URLs, do NOT apply aggressive HTML cleanup.
		if ( ! empty( $image_url_map ) ) {
			// Replace image URLs in the message.
			foreach ( $image_url_map as $original_url => $wp_url ) {
				$message = str_replace( $original_url, $wp_url, $message );
			}
		}

		if ( isset( $body['error'] ) ) {
			return array(
				'success' => false,
				'message' => sanitize_text_field( $body['error']['message'] ),
			);
		}

		$result = array(
			'success'   => true,
			'reply'     => $message, // Return raw HTML without sanitization or cleanup.
			'thread_id' => $thread_id,
		);

		// Add image IDs and URLs if any were generated.
		if ( ! empty( $image_ids ) ) {
			$result['image_ids'] = $image_ids;
			// Also include image URLs for easier rendering.
			$result['images'] = array();
			foreach ( $image_ids as $image_id ) {
				$result['images'][] = array(
					'id'  => $image_id,
					'url' => wp_get_attachment_url( $image_id ),
				);
			}
		}

		return $result;
	}

}