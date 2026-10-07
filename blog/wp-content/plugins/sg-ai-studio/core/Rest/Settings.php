<?php
/**
 * Settings API class for managing WordPress settings via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WordPress settings operations.
 */
class Settings extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'settings';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for getting all settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permissions_check' ),
					'description'         => 'Retrieves all WordPress settings.',
				),
				'schema' => array( $this, 'get_settings_schema' ),
			)
		);

		// Register endpoint for updating a single setting.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<setting_name>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_setting' ),
					'permission_callback' => array( $this, 'update_setting_permissions_check' ),
					'args'                => $this->get_update_setting_args(),
					'description'         => 'Updates a specific WordPress setting.',
				),
				'schema' => array( $this, 'get_setting_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to read settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_settings_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to update settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update items, WP_Error object otherwise.
	 */
	public function update_setting_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for updating a setting
	 *
	 * @return array
	 */
	protected function get_update_setting_args() {
		return array(
			'setting_name' => array(
				'description' => 'The name of the setting to update.',
				'type'        => 'string',
				'required'    => true,
			),
			'value'        => array(
				'description' => 'The new value for the setting.',
				'type'        => array( 'string', 'integer', 'boolean', 'array', 'object' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Get settings schema
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'settings',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'description' => 'Whether the operation was successful.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'data'    => array(
					'description' => 'WordPress settings data.',
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get single setting schema
	 *
	 * @return array
	 */
	public function get_setting_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'description' => 'Whether the operation was successful.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'data'    => array(
					'description' => 'Setting data.',
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get all WordPress settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_settings( $request ) {
		// Common WordPress settings.
		$settings_list = array(
			'blogname',
			'blogdescription',
			'siteurl',
			'home',
			'admin_email',
			'users_can_register',
			'default_role',
			'timezone_string',
			'date_format',
			'time_format',
			'start_of_week',
			'use_smilies',
			'default_category',
			'default_post_format',
			'posts_per_page',
			'posts_per_rss',
			'rss_use_excerpt',
			'show_avatars',
			'avatar_default',
			'avatar_rating',
			'default_comment_status',
			'default_ping_status',
			'comment_moderation',
			'require_name_email',
			'comment_whitelist',
			'comment_max_links',
			'moderation_notify',
			'comments_notify',
			'uploads_use_yearmonth_folders',
			'thumbnail_size_w',
			'thumbnail_size_h',
			'thumbnail_crop',
			'medium_size_w',
			'medium_size_h',
			'large_size_w',
			'large_size_h',
			'image_default_link_type',
			'image_default_align',
			'image_default_size',
			'embed_autourls',
			'embed_size_w',
			'embed_size_h',
			'page_on_front',
			'page_for_posts',
			'show_on_front',
			'default_link_category',
			'links_updated_date_format',
			'links_recently_updated_prepend',
			'links_recently_updated_append',
			'links_recently_updated_time',
			'link_manager_enabled',
			// SG Cachepress settings.
			'siteground_optimizer_enable_cache',
			'siteground_optimizer_autoflush_cache',
			'siteground_optimizer_file_caching',
			'siteground_optimizer_preheat_cache',
			'siteground_optimizer_logged_in_cache',
			'siteground_optimizer_user_agent_header',
			'siteground_optimizer_purge_rest_cache',
			'siteground_optimizer_enable_memcached',
			'siteground_optimizer_file_caching_interval_cleanup',
			'siteground_optimizer_excluded_urls',
			'siteground_optimizer_post_types_exclude',
			'siteground_optimizer_ssl_enabled',
			'siteground_optimizer_fix_insecure_content',
			'siteground_optimizer_enable_gzip_compression',
			'siteground_optimizer_enable_browser_caching',
			'siteground_optimizer_heartbeat_control',
			'siteground_optimizer_heartbeat_post_interval',
			'siteground_optimizer_heartbeat_dashboard_interval',
			'siteground_optimizer_heartbeat_frontend_interval',
			'siteground_optimizer_database_optimization',
			'siteground_optimizer_optimize_css',
			'siteground_optimizer_combine_css',
			'siteground_optimizer_preload_combined_css',
			'siteground_optimizer_optimize_javascript',
			'siteground_optimizer_combine_javascript',
			'siteground_optimizer_optimize_javascript_async',
			'siteground_optimizer_optimize_html',
			'siteground_optimizer_minify_html_exclude',
			'siteground_optimizer_optimize_web_fonts',
			'siteground_optimizer_fonts_preload_urls',
			'siteground_optimizer_remove_query_strings',
			'siteground_optimizer_disable_emojis',
			'siteground_optimizer_dns_prefetch',
			'siteground_optimizer_dns_prefetch_urls',
			'siteground_optimizer_lazyload_images',
			'siteground_optimizer_lazyload_mobile',
			'siteground_optimizer_lazyload_gravatars',
			'siteground_optimizer_lazyload_thumbnails',
			'siteground_optimizer_lazyload_responsive',
			'siteground_optimizer_lazyload_textwidgets',
			'siteground_optimizer_lazyload_iframes',
			'siteground_optimizer_lazyload_videos',
			'siteground_optimizer_lazyload_woocommerce',
			'siteground_optimizer_lazyload_shortcodes',
			'siteground_optimizer_compression_level',
			'siteground_optimizer_resize_images',
			'siteground_optimizer_backup_media',
			'siteground_optimizer_overwrite_custom',
			'siteground_optimizer_compress_existing',
			'siteground_optimizer_webp_support',
			'siteground_optimizer_quality_type',
			'siteground_optimizer_supercacher_permissions',
			'siteground_optimizer_frontend_permissions',
			'siteground_optimizer_images_permissions',
			'siteground_optimizer_environment_permissions',
			'siteground_optimizer_analytics_permissions',
			// SG Security options.
			'sg_security_lock_system_folders',
			'sg_security_disable_file_edit',
			'sg_security_wp_remove_version',
			'sg_security_disable_xml_rpc',
			'sg_security_disable_feed',
			'sg_security_xss_protection',
			'sg_security_delete_readme',
			'sg_security_sg2fa',
			'sg_security_disable_usernames',
			'sg_security_login_type',
			'sg_security_login_url',
			'sg_security_login_register',
			'sg_security_login_redirect',
			'sg_security_login_attempts',
			'sg_login_access',
			'sg_security_disable_activity_log',
			'sgs_activity_log_lifetime',
		);

		$settings = array();

		foreach ( $settings_list as $setting ) {
			$value                = get_option( $setting );
			$settings[ $setting ] = $value;
		}

		// Add some additional useful information.
		$settings['gmt_offset']          = get_option( 'gmt_offset' );
		$settings['WPLANG']              = get_option( 'WPLANG' );
		$settings['blog_public']         = get_option( 'blog_public' );
		$settings['permalink_structure'] = get_option( 'permalink_structure' );
		$settings['category_base']       = get_option( 'category_base' );
		$settings['tag_base']            = get_option( 'tag_base' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $settings,
			),
			200
		);
	}

	/**
	 * Update a single WordPress setting
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_setting( $request ) {
		// Check if powermode is enabled.
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}

		$setting_name = $request['setting_name'];
		$value        = $request['value'];

		// Validate setting name.
		if ( empty( $setting_name ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Setting name cannot be empty.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// List of allowed settings for security.
		$allowed_settings = array(
			'blogname',
			'blogdescription',
			'admin_email',
			'users_can_register',
			'default_role',
			'timezone_string',
			'gmt_offset',
			'date_format',
			'time_format',
			'start_of_week',
			'use_smilies',
			'default_category',
			'default_post_format',
			'posts_per_page',
			'posts_per_rss',
			'rss_use_excerpt',
			'show_avatars',
			'avatar_default',
			'avatar_rating',
			'default_comment_status',
			'default_ping_status',
			'comment_moderation',
			'require_name_email',
			'comment_whitelist',
			'comment_max_links',
			'moderation_notify',
			'comments_notify',
			'uploads_use_yearmonth_folders',
			'thumbnail_size_w',
			'thumbnail_size_h',
			'thumbnail_crop',
			'medium_size_w',
			'medium_size_h',
			'large_size_w',
			'large_size_h',
			'image_default_link_type',
			'image_default_align',
			'image_default_size',
			'embed_autourls',
			'embed_size_w',
			'embed_size_h',
			'page_on_front',
			'page_for_posts',
			'show_on_front',
			'default_link_category',
			'links_updated_date_format',
			'links_recently_updated_prepend',
			'links_recently_updated_append',
			'links_recently_updated_time',
			'link_manager_enabled',
			'WPLANG',
			'blog_public',
			'permalink_structure',
			'category_base',
			'tag_base',
			// SG Cachepress settings.
			'siteground_optimizer_enable_cache',
			'siteground_optimizer_autoflush_cache',
			'siteground_optimizer_file_caching',
			'siteground_optimizer_preheat_cache',
			'siteground_optimizer_logged_in_cache',
			'siteground_optimizer_user_agent_header',
			'siteground_optimizer_purge_rest_cache',
			'siteground_optimizer_enable_memcached',
			'siteground_optimizer_file_caching_interval_cleanup',
			'siteground_optimizer_excluded_urls',
			'siteground_optimizer_post_types_exclude',
			'siteground_optimizer_ssl_enabled',
			'siteground_optimizer_fix_insecure_content',
			'siteground_optimizer_enable_gzip_compression',
			'siteground_optimizer_enable_browser_caching',
			'siteground_optimizer_heartbeat_control',
			'siteground_optimizer_heartbeat_post_interval',
			'siteground_optimizer_heartbeat_dashboard_interval',
			'siteground_optimizer_heartbeat_frontend_interval',
			'siteground_optimizer_database_optimization',
			'siteground_optimizer_optimize_css',
			'siteground_optimizer_combine_css',
			'siteground_optimizer_preload_combined_css',
			'siteground_optimizer_optimize_javascript',
			'siteground_optimizer_combine_javascript',
			'siteground_optimizer_optimize_javascript_async',
			'siteground_optimizer_optimize_html',
			'siteground_optimizer_minify_html_exclude',
			'siteground_optimizer_optimize_web_fonts',
			'siteground_optimizer_fonts_preload_urls',
			'siteground_optimizer_remove_query_strings',
			'siteground_optimizer_disable_emojis',
			'siteground_optimizer_dns_prefetch',
			'siteground_optimizer_dns_prefetch_urls',
			'siteground_optimizer_lazyload_images',
			'siteground_optimizer_lazyload_mobile',
			'siteground_optimizer_lazyload_gravatars',
			'siteground_optimizer_lazyload_thumbnails',
			'siteground_optimizer_lazyload_responsive',
			'siteground_optimizer_lazyload_textwidgets',
			'siteground_optimizer_lazyload_iframes',
			'siteground_optimizer_lazyload_videos',
			'siteground_optimizer_lazyload_woocommerce',
			'siteground_optimizer_lazyload_shortcodes',
			'siteground_optimizer_compression_level',
			'siteground_optimizer_resize_images',
			'siteground_optimizer_backup_media',
			'siteground_optimizer_overwrite_custom',
			'siteground_optimizer_compress_existing',
			'siteground_optimizer_webp_support',
			'siteground_optimizer_quality_type',
			'siteground_optimizer_supercacher_permissions',
			'siteground_optimizer_frontend_permissions',
			'siteground_optimizer_images_permissions',
			'siteground_optimizer_environment_permissions',
			'siteground_optimizer_analytics_permissions',
			// SG Security options.
			'sg_security_lock_system_folders',
			'sg_security_disable_file_edit',
			'sg_security_wp_remove_version',
			'sg_security_disable_xml_rpc',
			'sg_security_disable_feed',
			'sg_security_xss_protection',
			'sg_security_delete_readme',
			'sg_security_sg2fa',
			'sg_security_disable_usernames',
			'sg_security_login_type',
			'sg_security_login_url',
			'sg_security_login_register',
			'sg_security_login_redirect',
			'sg_security_login_attempts',
			'sg_login_access',
			'sg_security_disable_activity_log',
			'sgs_activity_log_lifetime',

		);

		// Check if the setting is allowed to be updated.
		if ( ! in_array( $setting_name, $allowed_settings, true ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s is the setting name. */
						__( 'Setting "%s" is not allowed to be updated via this endpoint.', 'sg-ai-studio' ),
						$setting_name
					),
				),
				403
			);
		}

		// Get the old value.
		$old_value = get_option( $setting_name );

		// Update the setting.
		$updated = update_option( $setting_name, $value );

		if ( $updated || get_option( $setting_name ) === $value ) {
			// If it's a permalink structure change, flush rewrite rules.
			if ( 'permalink_structure' === $setting_name || 'category_base' === $setting_name || 'tag_base' === $setting_name ) {
				flush_rewrite_rules();
			}

			$new_value = get_option( $setting_name );

			// Log the activity.
			/* translators: %1$s is the setting name, %2$s is the old value, %3$s is the new value. */
			$log_description = sprintf( __( 'Setting Updated: %1$s (from "%2$s" to "%3$s")', 'sg-ai-studio' ), $setting_name, is_scalar( $old_value ) ? $old_value : serialize( $old_value ), is_scalar( $new_value ) ? $new_value : serialize( $new_value ) );
			Activity_Log_Helper::add_log_entry( 'Settings', $log_description );

			// Clear all caches.
			if( \function_exists('\sg_cachepress_purge_cache') ) {
				\sg_cachepress_purge_cache();
				\wp_cache_flush();
			} else {
				\wp_cache_flush();
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => sprintf(
						/* translators: %s is the setting name. */
						__( 'Setting "%s" has been updated successfully.', 'sg-ai-studio' ),
						$setting_name
					),
					'data'    => array(
						'setting_name' => $setting_name,
						'old_value'    => $old_value,
						'new_value'    => $new_value,
					),
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s is the setting name. */
						__( 'Failed to update setting "%s".', 'sg-ai-studio' ),
						$setting_name
					),
				),
				500
			);
		}
	}
}
