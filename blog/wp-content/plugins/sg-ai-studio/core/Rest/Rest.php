<?php
/**
 * Rest class for managing REST API endpoints
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\Rest\Auth;
use SG_AI_Studio\Rest\Posts;
use SG_AI_Studio\Rest\Categories;
use SG_AI_Studio\Rest\Tags;
use SG_AI_Studio\Rest\Plugins;
use SG_AI_Studio\Rest\Themes;
use SG_AI_Studio\Rest\Pages;
use SG_AI_Studio\Rest\Comments;
use SG_AI_Studio\Rest\Users;
use SG_AI_Studio\Rest\Media;
use SG_AI_Studio\Rest\Settings;
use SG_AI_Studio\Rest\Settings_Page;
use SG_AI_Studio\Rest\Products;
use SG_AI_Studio\Rest\Orders;
use SG_AI_Studio\Rest\Coupons;
use SG_AI_Studio\Rest\Reports;
use SG_AI_Studio\Rest\WooCommerce_Categories;
use SG_AI_Studio\Rest\Activity_Log;
use SG_AI_Studio\Rest\Core;
use SG_AI_Studio\Rest\Gutenberg;
use SG_AI_Studio\Rest\Post_Types;

/**
 * Handles custom REST API endpoints.
 */
class Rest extends Rest_Controller_Base {

	/**
	 * Auth instance
	 *
	 * @var Auth
	 */
	private $auth;

	/**
	 * Posts API instance
	 *
	 * @var Posts
	 */
	private $posts;

	/**
	 * Categories API instance
	 *
	 * @var Categories
	 */
	private $categories;

	/**
	 * Tags API instance
	 *
	 * @var Tags
	 */
	private $tags;

	/**
	 * Plugins API instance
	 *
	 * @var Plugins
	 */
	private $plugins;

	/**
	 * Themes API instance
	 *
	 * @var Themes
	 */
	private $themes;

	/**
	 * Pages API instance
	 *
	 * @var Pages
	 */
	private $pages;

	/**
	 * Comments API instance
	 *
	 * @var Comments
	 */
	private $comments;

	/**
	 * Users API instance
	 *
	 * @var Users
	 */
	private $users;

	/**
	 * Media API instance
	 *
	 * @var Media
	 */
	private $media;

	/**
	 * Settings API instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Settings Page API instance
	 *
	 * @var Settings_Page
	 */
	private $settings_page;

	/**
	 * Products API instance
	 *
	 * @var Products
	 */
	private $products;

	/**
	 * Orders API instance
	 *
	 * @var Orders
	 */
	private $orders;

	/**
	 * Coupons API instance
	 *
	 * @var Coupons
	 */
	private $coupons;

	/**
	 * Reports API instance
	 *
	 * @var Reports
	 */
	private $reports;

	/**
	 * WooCommerce Categories API instance
	 *
	 * @var WooCommerce_Categories
	 */
	private $woocommerce_categories;

	/**
	 * Activity Log API instance
	 *
	 * @var Activity_Log
	 */
	private $activity_log;

	/**
	 * Core API instance
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Gutenberg API instance
	 *
	 * @var Gutenberg
	 */
	private $gutenberg;

	/**
	 * Post Types API instance
	 *
	 * @var Post_Types
	 */
	private $post_types;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->auth          = new Auth();
		$this->posts         = new Posts();
		$this->categories    = new Categories();
		$this->tags          = new Tags();
		$this->plugins       = new Plugins();
		$this->themes        = new Themes();
		$this->pages         = new Pages();
		$this->comments      = new Comments();
		$this->users         = new Users();
		$this->media         = new Media();
		$this->settings      = new Settings();
		$this->settings_page = new Settings_Page();
		$this->core          = new Core();
		$this->gutenberg     = new Gutenberg();
		$this->post_types    = new Post_Types();

		// Only initialize WooCommerce endpoints if WooCommerce is active.
		if ( function_exists( 'is_plugin_active' ) && \is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->products               = new Products();
			$this->orders                 = new Orders();
			$this->coupons                = new Coupons();
			$this->reports                = new Reports();
			$this->woocommerce_categories = new WooCommerce_Categories();
		}

		$this->activity_log = new Activity_Log();
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register auth endpoints.
		$this->auth->register_rest_routes();

		// Register post management endpoints.
		$this->posts->register_rest_routes();

		// Register categories management endpoints.
		$this->categories->register_rest_routes();

		// Register tags management endpoints.
		$this->tags->register_rest_routes();

		// Register plugin management endpoints.
		$this->plugins->register_rest_routes();

		// Register theme management endpoints.
		$this->themes->register_rest_routes();

		// Register page management endpoints.
		$this->pages->register_rest_routes();

		// Register comment management endpoints.
		$this->comments->register_rest_routes();

		// Register user management endpoints.
		$this->users->register_rest_routes();

		// Register media management endpoints.
		$this->media->register_rest_routes();

		// Register settings management endpoints.
		$this->settings->register_rest_routes();

		// Register settings page endpoints.
		$this->settings_page->register_rest_routes();

		// Register core management endpoints.
		$this->core->register_rest_routes();

		// Register Gutenberg AI endpoints.
		$this->gutenberg->register_rest_routes();

		// Register post types endpoints.
		$this->post_types->register_rest_routes();

		// Register WooCommerce endpoints if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			// Register product management endpoints.
			if ( $this->products ) {
				$this->products->register_rest_routes();
			}

			// Register order management endpoints.
			if ( $this->orders ) {
				$this->orders->register_rest_routes();
			}

			// Register coupons management endpoints.
			if ( $this->coupons ) {
				$this->coupons->register_rest_routes();
			}

			// Register reports endpoints.
			if ( $this->reports ) {
				$this->reports->register_rest_routes();
			}

			// Register WooCommerce categories management endpoints.
			if ( $this->woocommerce_categories ) {
				$this->woocommerce_categories->register_rest_routes();
			}
		}

		// Register activity log endpoints.
		$this->activity_log->register_rest_routes();

		// Register REST API route for spam comments detection.
		register_rest_route(
			$this->namespace,
			'/spam-comments',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_spam_comments' ),
				'permission_callback' => function() {
					return current_user_can( 'moderate_comments' );
				},
			)
		);

		// Register REST API routes for block functionality.
		register_rest_route(
			$this->namespace,
			'/generate-content',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_content' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			$this->namespace,
			'/ping',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ping_response' ),
				'permission_callback' => array( $this, 'disconnect_permissions_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/disconnect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'disconnect' ),
				'permission_callback' => array( $this, 'disconnect_permissions_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/update-domain',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this->auth, 'update_domain' ),
				'permission_callback' => array( $this, 'disconnect_permissions_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/usage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_usage' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			$this->namespace,
			'/acl',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_acl' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

	}

	/**
	 * Generate content callback for REST API
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The REST response.
	 */
	public function generate_content( $request ) {
		// Verify nonce.
		if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sg_ai_studio_gutenberg_nonce' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Invalid security token', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Get prompt.
		$prompt = sanitize_textarea_field( $request->get_param( 'prompt' ) );

		if ( empty( $prompt ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Prompt cannot be empty', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Add system prompt to instruct AI to format response as HTML.
		$prompt = \sprintf(
			"Create Gutenberg block(s) for: %s\n\nCRITICAL Rules - Follow EXACTLY:\n\n1. Return ONLY valid Gutenberg block markup\n2. Every block: opening comment (<!-- wp:blockname -->), HTML, closing comment (<!-- /wp:blockname -->)\n3. NEVER self-closing blocks (<!-- wp:blockname /-->)\n4. Match WordPress core block structure EXACTLY\n5. HTML classes must match the JSON attributes EXACTLY\n\n## Image Blocks (if needed):\n- Use image_generation tool to create images\n- Format:\n  <!-- wp:image {\"id\":1,\"sizeSlug\":\"large\"} -->\n  <figure class=\"wp-block-image size-large\"><img src=\"url\" alt=\"description\" class=\"wp-image-1\"/></figure>\n  <!-- /wp:image -->\n\n## Cover Blocks (if needed):\n- Use image_generation tool for background\n- Format EXACTLY like this:\n  <!-- wp:cover {\"url\":\"image-url\",\"id\":1,\"dimRatio\":50,\"align\":\"full\"} -->\n  <div class=\"wp-block-cover alignfull\"><img class=\"wp-block-cover__image-background wp-image-1\" alt=\"\" src=\"image-url\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim\"></span><div class=\"wp-block-cover__inner-container\">\n  <!-- Inner blocks here -->\n  </div></div>\n  <!-- /wp:cover -->\n- Key rules for cover:\n  * Use <img> tag, NOT background-image style\n  * dimRatio in JSON must match has-background-dim-XX class (or omit class if 50)\n  * Don't add color classes unless in JSON attributes\n  * Keep structure simple and clean\n\n## Standard Block Examples:\n\n<!-- wp:paragraph -->\n<p>Text here.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":2} -->\n<h2 class=\"wp-block-heading\">Heading</h2>\n<!-- /wp:heading -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><li>Item</li></ul>\n<!-- /wp:list -->\n\n<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column --><div class=\"wp-block-column\"></div><!-- /wp:column --></div>\n<!-- /wp:columns -->\n\n<!-- wp:group -->\n<div class=\"wp-block-group\"></div>\n<!-- /wp:group -->\n\n<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button --><div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\">Text</a></div><!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n## Important:\n- Copy standard WordPress block HTML structure EXACTLY\n- Don't invent classes not in WordPress core\n- JSON attributes must match HTML classes perfectly\n- Keep it simple and standard\n- No extra styling unless requested\n\nOutput ONLY the block markup, nothing else.",
			$prompt
		);

		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to generate authentication token.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Get post_id if provided.
		$post_id = $request->get_param( 'post_id' );
		if ( empty( $post_id ) ) {
			$post_id = 0;
		}

		try {
			// Process request using Helper class.
			$result = Helper::process_gutenberg_block_request( $prompt, $auth_token, '', '', $post_id );

			if ( ! $result['success'] ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $result['message'],
					),
					500
				);
			}

			$result['reply'] = str_replace( array( '```html', '```' ), array( '', '' ), $result['reply'] );
			$response_data = array(
				'success' => true,
				'data'    => $result['reply'],
			);

			// Add image data if any were generated.
			if ( ! empty( $result['image_ids'] ) ) {
				$response_data['image_ids'] = $result['image_ids'];
			}
			if ( ! empty( $result['images'] ) ) {
				$response_data['images'] = $result['images'];
			}

			return new \WP_REST_Response( $response_data, 200 );
		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html( $e->getMessage() ),
				),
				500
			);
		}
	}


	/**
	 * Get spam comments from WordPress database
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The spam comments response.
	 */
	public function get_spam_comments( $request ) {
		// Query for spam comments directly from WordPress database.
		$args = array(
			'status'  => 'spam',  // Get only comments marked as spam.
			'orderby' => 'comment_date',
			'order'   => 'DESC',
		);

		$spam_comments = get_comments( $args );

		// Extract comment IDs.
		$spam_comment_ids = array();
		foreach ( $spam_comments as $comment ) {
			$spam_comment_ids[] = intval( $comment->comment_ID );
		}

		// Return the response in the requested format.
		return new \WP_REST_Response(
			array(
				'ids'   => $spam_comment_ids,
				'count' => count( $spam_comment_ids ),
			),
			200
		);
	}

	/**
	 * Ping response function
	 *
	 * @return \WP_REST_Response
	 */
	public function ping_response() {
		// Check if get_plugins() function exists.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$active_theme        = wp_get_theme();
		$all_plugins         = get_plugins();
		$active_plugins      = get_option( 'active_plugins' );
		$active_plugins_data = array();
		$plugin_count        = 0;

		$site_issue_counts = \json_decode( get_transient( 'health-check-site-status-result' ), true );

		if ( isset( $site_issues_counts['recommended'] ) && isset( $site_issue_counts['critical'] ) && $site_issue_counts['recommended'] + $site_issue_counts['critical'] <= 0 ) {
			$site_health = 'Great';
		} elseif ( isset( $site_issue_counts['critical'] ) && 1 === (int) $site_issue_counts['critical'] ) {
			$site_health = 'Your site has a critical issue';
		} elseif ( isset( $site_issue_counts['critical'] ) && (int) $site_issue_counts['critical'] > 1 ) {
			$site_health = 'Your site has several critical issues';
		} else {
			$site_health = 'Good';
		}

		foreach ( $active_plugins as $plugin ) {
			foreach ( $all_plugins as $key1 => $value1 ) {
				if ( $plugin === $key1 ) {
					$active_plugins_data[ $plugin_count ]['name']    = $value1['Name'];
					$active_plugins_data[ $plugin_count ]['version'] = $value1['Version'];
					$plugin_count++;

				}
			}
		}

		$data = array(
			'site_name'        => get_bloginfo( 'name' ),
			'site_description' => get_bloginfo( 'description' ),
			'site_url'         => get_option( 'siteurl' ),
			'home_url'         => get_option( 'home' ),
			'version'          => get_bloginfo( 'version' ),
			'site_health'      => $site_health,
			'active_plugins'   => $active_plugins_data,
			'active_theme'     => array(
				'name'    => $active_theme->get( 'Name' ),
				'version' => $active_theme->get( 'Version' ),
			),
		);

		update_option('sg_ai_studio_connected', true);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}

	/**
	 * Check if a user has permission to disconnect
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access to disconnect, WP_Error object otherwise.
	 */
	public function disconnect_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Disconnect the site and clean up all plugin data
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response Response object on success.
	 */
	public function disconnect( $request ) {
		$result = Helper::cleanup_plugin_data();

		// Prepare response.
		if ( $result['success'] ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Successfully disconnected and cleaned up all SG AI Studio data.', 'sg-ai-studio' ),
				),
				200
			);
		} else {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Disconnect completed with some errors.', 'sg-ai-studio' ),
					'errors'  => $result['errors'],
				),
				207
			);
		}
	}

	/**
	 * Get usage data from AI Studio API
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response Response object on success.
	 */
	public function get_usage( $request ) {
		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to generate authentication token.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Determine API URL based on environment.
		if ( Helper::is_staging_environment() ) {
			$api_url = 'https://api.staging.studio.siteground.ai/api/v1/reporting/wp-usage';
		} else {
			$api_url = 'https://api.studio.siteground.ai/api/v1/reporting/wp-usage';
		}

		// Call AI Studio Backend API.
		$api_response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'timeout' => 30,
			)
		);

		// Check if API call failed.
		if ( is_wp_error( $api_response ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to fetch usage data.', 'sg-ai-studio' ),
					'error'   => $api_response->get_error_message(),
				),
				500
			);
		}

		$response_body = wp_remote_retrieve_body( $api_response );
		$status_code   = wp_remote_retrieve_response_code( $api_response );

		return new \WP_REST_Response(
			json_decode( $response_body, true ),
			$status_code
		);
	}

	/**
	 * Get ACL data from AI Studio API
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response Response object on success.
	 */
	public function get_acl( $request ) {
		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to generate authentication token.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Determine API URL based on environment.
		if ( Helper::is_staging_environment() ) {
			$api_url = 'https://api.staging.studio.siteground.ai/api/v1/acl/wp-acl';
		} else {
			$api_url = 'https://api.studio.siteground.ai/api/v1/acl/wp-acl';
		}

		// Call AI Studio Backend API.
		$api_response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $auth_token,
				),
				'timeout' => 30,
			)
		);

		// Check if API call failed.
		if ( is_wp_error( $api_response ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to fetch ACL data.', 'sg-ai-studio' ),
					'error'   => $api_response->get_error_message(),
				),
				500
			);
		}

		$response_body = wp_remote_retrieve_body( $api_response );
		$status_code   = wp_remote_retrieve_response_code( $api_response );

		return new \WP_REST_Response(
			json_decode( $response_body, true ),
			$status_code
		);
	}

}
