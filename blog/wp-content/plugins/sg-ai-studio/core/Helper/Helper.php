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
	 * Derive a minimal, structured page context for the current request.
	 *
	 * Re-derived on every call (never cached) so it always reflects the current
	 * page. Only cheap, readily-available server-side fields are populated;
	 * anything requiring a heavier lookup is omitted. Returns null when the
	 * current page cannot be determined, in which case callers should omit
	 * page_context entirely from the injected chat config. This function never
	 * throws, so it is safe to call during chat init.
	 *
	 * @since 1.2.7
	 *
	 * @return array|null The page context array, or null if it cannot be determined.
	 */
	public static function get_page_context() {
		if ( is_admin() ) {
			return self::get_admin_page_context();
		}

		// Page builders (Elementor, Beaver, Divi, Bricks, etc.) render their
		// "Edit with <builder>" experience on a front-end URL inside an <iframe>,
		// so WordPress' is_admin() returns false even though the user is really on
		// an editor screen. Detect that here and report a 'wp-admin' surface with
		// the correct editor, instead of mislabelling it as 'frontend'.
		$builder = self::get_page_builder_editor();
		if ( '' !== $builder ) {
			return self::get_builder_page_context( $builder );
		}

		return self::get_frontend_page_context();
	}

	/**
	 * Derive the page context for a wp-admin request.
	 *
	 * @since 1.2.7
	 *
	 * @return array|null The admin page context, or null if it cannot be determined.
	 */
	private static function get_admin_page_context() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return null;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return null;
		}

		$context = array(
			'surface' => 'wp-admin',
		);

		// Admin-relative request URL, e.g. wp-admin/post.php?post=42&action=edit.
		$url = self::get_admin_relative_url();
		if ( '' !== $url ) {
			$context['url'] = $url;
		}

		if ( 'post' === $screen->base ) {
			// Editing (or creating) a single post/page.
			$context['screen'] = 'edit-post';

			$post = self::get_edited_post();
			if ( $post instanceof \WP_Post ) {
				$context['post_id']   = $post->ID;
				$context['post_type'] = $post->post_type;

				$title = get_the_title( $post );
				if ( '' !== $title ) {
					$context['title'] = $title;
				}
			}

			$context['editor'] = self::get_admin_editor( $screen );
		} else {
			// Any other admin screen (settings, tools, list tables, etc.).
			$context['screen'] = 'settings';

			$area = self::get_admin_area_label();
			if ( '' !== $area ) {
				$context['area'] = $area;
			}
		}

		return $context;
	}

	/**
	 * Derive the page context for a front-end request.
	 *
	 * @since 1.2.7
	 *
	 * @return array|null The front-end page context, or null if it cannot be determined.
	 */
	private static function get_frontend_page_context() {
		$context = array(
			'surface' => 'frontend',
		);

		$queried = get_queried_object();

		if ( $queried instanceof \WP_Post ) {
			$context['post_id']   = $queried->ID;
			$context['post_type'] = $queried->post_type;

			$title = get_the_title( $queried );
			if ( '' !== $title ) {
				$context['title'] = $title;
			}

			$permalink = get_permalink( $queried );
			if ( $permalink ) {
				$context['url'] = wp_make_link_relative( $permalink );
			}
		}

		// Fall back to the WordPress-parsed request path (from the rewrite engine)
		// when no canonical permalink was derived, e.g. on archives or search.
		if ( empty( $context['url'] ) ) {
			$wp_request = isset( $GLOBALS['wp']->request ) && is_string( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : '';
			if ( '' !== $wp_request ) {
				$context['url'] = '/' . ltrim( $wp_request, '/' );
			} elseif ( ( function_exists( 'is_front_page' ) && is_front_page() ) || ( function_exists( 'is_home' ) && is_home() ) ) {
				$context['url'] = '/';
			}
		}

		// Without at least a URL or a queried post there is nothing to report.
		if ( count( $context ) <= 1 ) {
			return null;
		}

		return $context;
	}

	/**
	 * Resolve the post being edited on the current admin screen, if any.
	 *
	 * @since 1.2.7
	 *
	 * @return \WP_Post|null The edited post, or null when none can be resolved.
	 */
	private static function get_edited_post() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post'] ) ) {
			// Read-only context lookup; no state change, so no nonce is required.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post = get_post( absint( wp_unslash( $_GET['post'] ) ) );
			if ( $post instanceof \WP_Post ) {
				return $post;
			}
		}

		if ( ! empty( $GLOBALS['post'] ) && $GLOBALS['post'] instanceof \WP_Post ) {
			return $GLOBALS['post'];
		}

		return null;
	}

	/**
	 * Detect which editor is active on the current admin edit screen.
	 *
	 * @since 1.2.7
	 *
	 * @param \WP_Screen $screen The current admin screen.
	 * @return string One of 'gutenberg', 'elementor' or 'classic'.
	 */
	private static function get_admin_editor( $screen ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['action'] ) && 'elementor' === $_GET['action'] ) {
			return 'elementor';
		}

		if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return 'gutenberg';
		}

		return 'classic';
	}

	/**
	 * Detect a front-end page builder that is currently in edit/preview mode.
	 *
	 * These builders load their editor (or its live-preview iframe) on a
	 * front-end URL, so is_admin() is false. Each is identified primarily by the
	 * distinctive query argument the builder itself adds to that request (the
	 * most reliable signal at enqueue time), with the builder's own API used as a
	 * secondary, guarded confirmation so a renamed function can never fatal.
	 *
	 * @since 1.2.8
	 *
	 * @return string The builder slug (e.g. 'elementor'), or '' when none is active.
	 */
	private static function get_page_builder_editor() {
		// Elementor — the editor shell is a wp-admin URL (?action=elementor, already
		// handled above); its live preview loads from the front end (?elementor-preview=<id>).
		if (
			self::has_query_param( 'elementor-preview' )
			|| 'elementor' === self::get_query_param( 'action' )
			|| ( class_exists( '\Elementor\Plugin' )
				&& isset( \Elementor\Plugin::$instance->preview )
				&& \Elementor\Plugin::$instance->preview->is_preview_mode() )
		) {
			return 'elementor';
		}

		// Beaver Builder — front-end editor (?fl_builder).
		if (
			self::has_query_param( 'fl_builder' )
			|| ( class_exists( '\FLBuilderModel' ) && \FLBuilderModel::is_builder_active() )
		) {
			return 'beaver-builder';
		}

		// Divi Visual Builder (?et_fb=1).
		if (
			'' !== self::get_query_param( 'et_fb' )
			|| ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() )
		) {
			return 'divi';
		}

		// Bricks Builder (?bricks=run).
		if (
			'run' === self::get_query_param( 'bricks' )
			|| ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() )
		) {
			return 'bricks';
		}

		// Oxygen Builder (?ct_builder=true, its iframe adds ?oxygen_iframe).
		if (
			'' !== self::get_query_param( 'ct_builder' )
			|| self::has_query_param( 'oxygen_iframe' )
			|| defined( 'SHOW_CT_BUILDER' )
		) {
			return 'oxygen';
		}

		// WPBakery Page Builder / Visual Composer front-end editor (?vc_action=vc_inline).
		if (
			'vc_inline' === self::get_query_param( 'vc_action' )
			|| self::has_query_param( 'vc_editable' )
			|| ( function_exists( 'vc_is_inline' ) && vc_is_inline() )
		) {
			return 'wpbakery';
		}

		// Thrive Architect / Thrive Theme Builder (?tve=true).
		if (
			'true' === self::get_query_param( 'tve' )
			|| ( function_exists( 'tve_in_architect' ) && tve_in_architect() )
		) {
			return 'thrive-architect';
		}

		// Avada Live / Fusion Builder (?fb-edit=1).
		if (
			'' !== self::get_query_param( 'fb-edit' )
			|| ( function_exists( 'fusion_is_builder_frame' ) && fusion_is_builder_frame() )
		) {
			return 'avada';
		}

		// Cornerstone / Pro (ThemeCo) front-end editor.
		if (
			self::has_query_param( 'cornerstone' )
			|| self::has_query_param( 'cs-preview' )
			|| ( function_exists( 'cornerstone_is_permalink_endpoint' ) && cornerstone_is_permalink_endpoint() )
		) {
			return 'cornerstone';
		}

		return '';
	}

	/**
	 * Build a wp-admin edit-post context for a front-end page builder session.
	 *
	 * Mirrors the shape produced for the native editor by get_admin_page_context()
	 * so downstream consumers see a consistent "editing a post in wp-admin"
	 * context regardless of which builder is in use.
	 *
	 * @since 1.2.8
	 *
	 * @param string $editor The builder slug from get_page_builder_editor().
	 * @return array The page context array.
	 */
	private static function get_builder_page_context( $editor ) {
		$context = array(
			'surface' => 'wp-admin',
			'screen'  => 'edit-post',
			'editor'  => $editor,
		);

		$post = self::get_builder_edited_post();
		if ( $post instanceof \WP_Post ) {
			$context['post_id']   = $post->ID;
			$context['post_type'] = $post->post_type;

			$title = get_the_title( $post );
			if ( '' !== $title ) {
				$context['title'] = $title;
			}

			// The canonical wp-admin edit URL for the post, matching the surface.
			$context['url'] = 'wp-admin/post.php?post=' . $post->ID . '&action=edit';
		}

		return $context;
	}

	/**
	 * Resolve the post being edited in a front-end page builder session.
	 *
	 * Builders load on the post's own front-end URL, so the main query normally
	 * already resolves to it; a post ID passed in the query string is used as a
	 * fallback (e.g. Elementor's ?elementor-preview=<id>).
	 *
	 * @since 1.2.8
	 *
	 * @return \WP_Post|null The edited post, or null when none can be resolved.
	 */
	private static function get_builder_edited_post() {
		$queried = get_queried_object();
		if ( $queried instanceof \WP_Post ) {
			return $queried;
		}

		foreach ( array( 'elementor-preview', 'fl_builder_id', 'post', 'p', 'page_id', 'post_id' ) as $key ) {
			$value = self::get_query_param( $key );
			if ( '' !== $value ) {
				$post = get_post( absint( $value ) );
				if ( $post instanceof \WP_Post ) {
					return $post;
				}
			}
		}

		return null;
	}

	/**
	 * Whether a read-only query argument is present on the current request.
	 *
	 * @since 1.2.8
	 *
	 * @param string $key The query argument name.
	 * @return bool True when the argument is set.
	 */
	private static function has_query_param( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] );
	}

	/**
	 * Read and sanitize a read-only query argument from the current request.
	 *
	 * Used only for cheap, read-only page-context detection, so no nonce is
	 * required; the value is always sanitized before use.
	 *
	 * @since 1.2.8
	 *
	 * @param string $key The query argument name.
	 * @return string The sanitized value, or '' when the argument is absent.
	 */
	private static function get_query_param( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}

		// Read-only context lookup; no state change, so no nonce is required.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}

	/**
	 * Build the admin-relative request URL (from wp-admin/ onward).
	 *
	 * Uses the WordPress-parsed script name ($pagenow) rather than the raw
	 * request URI, and re-attaches only the context-relevant query args, each
	 * sanitized individually, so nonces and other transient params never leak
	 * into the chat config.
	 *
	 * @since 1.2.7
	 *
	 * @return string The relative URL, or an empty string when unavailable.
	 */
	private static function get_admin_relative_url() {
		$pagenow = isset( $GLOBALS['pagenow'] ) && is_string( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';

		if ( '' === $pagenow ) {
			return '';
		}

		$url = 'wp-admin/' . $pagenow;

		$allowed_args = array( 'page', 'post', 'post_type', 'action', 'taxonomy', 'tab' );
		$query        = array();

		foreach ( $allowed_args as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET[ $key ] ) ) {
				// Read-only context lookup; no state change, so no nonce is required.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
			}
		}

		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		return ltrim( esc_url_raw( '/' . $url ), '/' );
	}

	/**
	 * Resolve a human-readable label for the current admin area/screen.
	 *
	 * @since 1.2.7
	 *
	 * @return string The area label (e.g. "Speed Optimizer"), or an empty string.
	 */
	private static function get_admin_area_label() {
		if ( function_exists( 'get_admin_page_title' ) ) {
			$title = get_admin_page_title();
			if ( is_string( $title ) && '' !== trim( $title ) ) {
				return wp_strip_all_tags( $title );
			}
		}

		if ( ! empty( $GLOBALS['title'] ) && is_string( $GLOBALS['title'] ) ) {
			return wp_strip_all_tags( $GLOBALS['title'] );
		}

		return '';
	}

	/**
	 * Send message to AI Studio API
	 *
	 * @param string $message The user message.
	 * @param string $api_key AI Studio API key.
	 * @param string $thread_id Optional thread ID for continuing conversations.
	 * @param string $agent Optional agent name.
	 * @param array  $model_config Optional model configuration (temperature, max_tokens, etc.).
	 * @param string $chat_source Optional source identifier for the chat request.
	 * @return array|\WP_Error The API response or WP_Error.
	 */
	public static function send_to_aistudio( $message, $api_key, $thread_id = '', $agent = '', $model_config = array(), $chat_source = '' ) {
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

		// Add model configuration for WP 7.0 compatibility.
		// NOTE: This requires backend API support. If the backend doesn't
		// support these parameters, they will be safely ignored.
		if ( ! empty( $model_config ) ) {
			$body['config'] = $model_config;
		}

		// Add chat_source to track request origin.
		if ( ! empty( $chat_source ) ) {
			$body['chat_source'] = $chat_source;
		}

		$args = array(
			'headers'     => $headers,
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'timeout'     => 90000000,
			'redirection' => 45,
			'sslverify'   => true,
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
	 * @param string $chat_source Optional source identifier for the chat request.
	 * @return array The response data.
	 */
	public static function process_chat_request( $message, $api_key, $thread_id = '', $agent = '', $post_id = 0, $chat_source = '' ) {
		$response = self::send_to_aistudio( $message, $api_key, $thread_id, $agent, array(), $chat_source );

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
	 * Schedule the temp files cleanup cron job to run daily
	 */
	public static function schedule_temp_cleanup_cron() {
		if ( ! wp_next_scheduled( 'sg_ai_studio_cleanup_temp_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'sg_ai_studio_cleanup_temp_cron' );
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
	 * WordPress cron job hook for cleaning up temporary files
	 */
	public static function cron_cleanup_temp_files() {
		self::cleanup_temp_files( 24 );
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
	 * Validate that a URL is safe to fetch server-side (SSRF protection).
	 *
	 * Rejects non-http(s) schemes and any URL whose host resolves to a loopback,
	 * private, link-local, reserved, or cloud-metadata (169.254.169.254) address.
	 * Should be paired with wp_safe_remote_get() for defense in depth.
	 *
	 * @param string $url The URL to validate.
	 * @return bool True if the URL is safe to fetch, false otherwise.
	 */
	public static function is_safe_remote_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return false;
		}

		$parts = wp_parse_url( $url );

		// Require an explicit http(s) scheme and a host.
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		$scheme = strtolower( $parts['scheme'] );
		if ( 'http' !== $scheme && 'https' !== $scheme ) {
			return false;
		}

		// Strip IPv6 brackets if present.
		$host = trim( $parts['host'], '[]' );

		// Collect the set of IP addresses the host resolves to.
		$ips = array();

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			// Host is already a literal IP address.
			$ips[] = $host;
		} else {
			// Resolve both IPv4 (A) and IPv6 (AAAA) records. Silenced because a
			// resolution failure is expected and handled via the empty() check below.
			$records = @dns_get_record( $host, DNS_A | DNS_AAAA ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_array( $records ) ) {
				foreach ( $records as $record ) {
					if ( ! empty( $record['ip'] ) ) {
						$ips[] = $record['ip'];
					}
					if ( ! empty( $record['ipv6'] ) ) {
						$ips[] = $record['ipv6'];
					}
				}
			}

			// Fallback to a simple IPv4 lookup if no DNS records were returned.
			if ( empty( $ips ) ) {
				$resolved = gethostbynamel( $host );
				if ( is_array( $resolved ) ) {
					$ips = $resolved;
				}
			}
		}

		// If the host could not be resolved to any IP, treat it as unsafe.
		if ( empty( $ips ) ) {
			return false;
		}

		foreach ( $ips as $ip ) {
			// Reject private and reserved ranges. FILTER_FLAG_NO_RES_RANGE covers
			// loopback (127/8, ::1), link-local (169.254/16 incl. cloud metadata,
			// fe80::/10) and other reserved ranges; FILTER_FLAG_NO_PRIV_RANGE covers
			// 10/8, 172.16/12, 192.168/16 and fc00::/7.
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return false;
			}
		}

		return true;
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
		// Validate the URL to prevent SSRF before making any outbound request.
		if ( ! self::is_safe_remote_url( $image_url ) ) {
			return false;
		}

		// Download the image.
		$response = wp_safe_remote_get( $image_url, array( 'timeout' => 30 ) );

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

		// Add custom meta to identify AI-generated images from Gutenberg.
		update_post_meta( $attachment_id, '_sg_ai_studio_generated', true );

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
			'sg_ai_studio_provider_connected',
			'sg_ai_studio_connected_url',
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
			'sg_ai_studio_cleanup_temp_cron',
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

		// Remove temporary files directory.
		$upload_dir = wp_upload_dir();
		$tmp_dir    = $upload_dir['basedir'] . '/sg-ai-studio-tmp';

		if ( file_exists( $tmp_dir ) ) {
			$files = glob( $tmp_dir . '/*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
			// Try to remove the directory itself.
			if ( ! @rmdir( $tmp_dir ) ) {
				$errors[] = 'Failed to remove temporary files directory';
			}
		}

		return array(
			'success' => empty( $errors ),
			'errors'  => $errors,
		);
	}
	/**
	 * Send request to AI Studio generation API (v1)
	 *
	 * Handles text generation, image generation, and image editing requests
	 * with different endpoints and request formats.
	 *
	 * @param array  $question_parts Array of question parts with 'type' and content (for text) or prompt string (for image).
	 * @param string $api_key AI Studio API key.
	 * @param array  $model_config Optional model configuration.
	 * @param bool   $is_image_generation Whether this is an image generation request.
	 * @param string $chat_source Optional source identifier for the chat request.
	 * @param bool   $is_image_edit Whether this is an image editing/refinement request.
	 * @return array|\WP_Error The API response or WP_Error.
	 */
	public static function send_to_text_generation_api( $question_parts, $api_key, $model_config = array(), $is_image_generation = false, $chat_source = '', $is_image_edit = false ) {
		if ( self::is_staging_environment() ) {
			$hostname = 'https://api.staging.studio.siteground.ai';
		} else {
			$hostname = 'https://api.studio.siteground.ai';
		}

		// Set endpoint and build body based on generation type.
		if ( $is_image_edit ) {
			$url  = $hostname . '/api/v1/image/edit';
			$body = array(
				'question' => $question_parts,
				'language' => 'en',
			);

			// Add model if provided in model config.
			if ( ! empty( $model_config['model'] ) ) {
				$body['model'] = $model_config['model'];
			}

			// Add chat_source to track request origin.
			if ( ! empty( $chat_source ) ) {
				$body['chat_source'] = $chat_source;
			}
		} elseif ( $is_image_generation ) {
			$url  = $hostname . '/api/v1/image/generate';
			$body = array(
				'prompt' => is_array( $question_parts ) ? $question_parts['prompt'] : $question_parts,
			);

			// Add aspect ratio if provided in model config.
			if ( ! empty( $model_config['aspect_ratio'] ) ) {
				$body['aspect_ratio'] = $model_config['aspect_ratio'];
			}

			// Add chat_source to track request origin.
			if ( ! empty( $chat_source ) ) {
				$body['chat_source'] = $chat_source;
			}
		} else {
			$url  = $hostname . '/api/v1/text/generate';
			$body = array(
				'question' => $question_parts,
				'language' => '',
				'service'  => true,
			);

			// Add model configuration if provided.
			if ( ! empty( $model_config ) ) {
				$body['config'] = $model_config;
			}

			// Add chat_source to track request origin.
			if ( ! empty( $chat_source ) ) {
				$body['chat_source'] = $chat_source;
			}
		}

		$headers = array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
			'Connection'    => 'keep-alive',
			'Accept'        => '*/*',
		);

		$args = array(
			'headers'     => $headers,
			'body'        => wp_json_encode( $body ),
			'method'      => 'POST',
			'timeout'     => 90000000,
			'redirection' => 45,
			'sslverify'   => true,
		);

		return wp_remote_post( $url, $args );
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
			$auth_token = false;
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
		return $value === true || $value === 'true' || $value === 1 || $value === 'True';
	}

	/**
	 * Save a pre-edit revision for a post so an edit has a deterministic restore point.
	 *
	 * Uses WordPress' normal revision path, which respects the site's
	 * `WP_POST_REVISIONS` config and the post type's revision support. This does
	 * NOT force revisions: when revisions are disabled or unsupported (e.g. most
	 * WooCommerce products), no revision is created and 0 is returned. Callers
	 * should report the site status separately via wp_revisions_enabled().
	 *
	 * Call this BEFORE the update is persisted so the snapshot captures the
	 * pre-edit state.
	 *
	 * @param int $post_id The post ID to snapshot.
	 * @return int The pre-edit revision ID, or 0 when none exists/could be created.
	 */
	public static function save_pre_edit_revision( $post_id ) {
		// Respects the site's WP_POST_REVISIONS config and post-type support.
		$revision_id = wp_save_post_revision( $post_id );

		if ( ! is_wp_error( $revision_id ) && (int) $revision_id > 0 ) {
			return (int) $revision_id;
		}

		// Content unchanged since the last snapshot -> that revision is the
		// restore point. Returns 0 when revisions are disabled or unsupported.
		$revisions = wp_get_post_revisions(
			$post_id,
			array(
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		return ! empty( $revisions ) ? (int) reset( $revisions ) : 0;
	}

	/**
	 * Clean up old temporary files from the sg-ai-studio-tmp directory.
	 *
	 * Removes files older than 24 hours to prevent disk space accumulation.
	 *
	 * @param int $max_age_hours Maximum age of files to keep in hours (default: 24).
	 * @return array Cleanup results with count of deleted files.
	 */
	public static function cleanup_temp_files( $max_age_hours = 24 ) {
		$upload_dir = wp_upload_dir();
		$tmp_dir    = $upload_dir['basedir'] . '/sg-ai-studio-tmp';

		if ( ! file_exists( $tmp_dir ) ) {
			return array(
				'success' => true,
				'deleted' => 0,
				'message' => 'Temp directory does not exist.',
			);
		}

		$files           = glob( $tmp_dir . '/*' );
		$deleted_count   = 0;
		$max_age_seconds = $max_age_hours * HOUR_IN_SECONDS;
		$current_time    = time();

		foreach ( $files as $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}

			$file_age = $current_time - filemtime( $file );

			if ( $file_age > $max_age_seconds ) {
				if ( wp_delete_file( $file ) ) {
					++$deleted_count;
				}
			}
		}

		return array(
			'success' => true,
			'deleted' => $deleted_count,
			'message' => sprintf( 'Deleted %d temporary file(s).', $deleted_count ),
		);
	}

	/**
	 * Detect if site is hosted on SiteGround.
	 *
	 * Checks for SiteGround-specific file system markers.
	 *
	 * @since 1.0.0
	 *
	 * @return int 1 if SiteGround hosted, 0 otherwise.
	 */
	public static function is_siteground() {
		// Bail if open_basedir restrictions are set, and we are not able to check certain directories.
		if ( ! empty( ini_get( 'open_basedir' ) ) ) {
			return 0;
		}

		return (int) ( @file_exists( '/etc/yum.repos.d/baseos.repo' ) && @file_exists( '/Z' ) );
	}

	/**
	 * Purge all caches (SiteGround Cache + WordPress object cache)
	 *
	 * @return void
	 */
	public static function purge_caches() {
		if ( function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}
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
	 * @param string $chat_source Optional source identifier for the chat request.
	 * @return array The response data.
	 */
	public static function process_gutenberg_block_request( $message, $api_key, $thread_id = '', $agent = '', $post_id = 0, $chat_source = '' ) {
		$response = self::send_to_aistudio( $message, $api_key, $thread_id, $agent, array(), $chat_source );

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