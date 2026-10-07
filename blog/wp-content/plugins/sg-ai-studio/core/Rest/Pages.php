<?php
/**
 * Pages API class for managing WordPress pages via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Query;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for page operations.
 */
class Pages extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'pages';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new page.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_page' ),
					'permission_callback' => array( $this, 'create_page_permissions_check' ),
					'args'                => $this->get_create_page_args(),
					'description'         => 'Creates a new page with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_pages' ),
					'permission_callback' => array( $this, 'get_pages_permissions_check' ),
					'args'                => $this->get_pages_args(),
					'description'         => 'Retrieves a list of pages based on the provided filters.',
				),
				'schema' => array( $this, 'get_page_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single page.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_page' ),
					'permission_callback' => array( $this, 'get_page_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the page.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific page by ID.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_page' ),
					'permission_callback' => array( $this, 'update_page_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the page.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Updates a specific page with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_page' ),
					'permission_callback' => array( $this, 'delete_page_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the page.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific page. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_page_schema' ),
			)
		);

		// Register endpoint for bulk operations on pages.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_create_pages' ),
					'permission_callback' => array( $this, 'create_page_permissions_check' ),
					'args'                => $this->get_batch_create_pages_args(),
					'description'         => 'Creates multiple pages in a single request.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_pages' ),
					'permission_callback' => array( $this, 'update_page_permissions_check' ),
					'args'                => $this->get_batch_update_pages_args(),
					'description'         => 'Updates multiple pages in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_pages' ),
					'permission_callback' => array( $this, 'delete_page_permissions_check' ),
					'args'                => $this->get_batch_delete_pages_args(),
					'description'         => 'Deletes multiple pages in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to create pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_page_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_pages_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read a specific page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read the item, WP_Error object otherwise.
	 */
	public function get_page_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to update a page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_page_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to delete a page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_page_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for creating a page
	 *
	 * @return array
	 */
	protected function get_create_page_args() {
		return array(
			'title'          => array(
				'description' => 'The title for the page.',
				'type'        => 'string',
				'required'    => true,
			),
			'content'        => array(
				'description' => 'The content for the page.',
				'type'        => 'string',
				'required'    => false,
			),
			'excerpt'        => array(
				'description' => 'The excerpt for the page.',
				'type'        => 'string',
				'required'    => false,
			),
			'author'         => array(
				'description' => 'The ID of the user who created the page.',
				'type'        => 'integer',
				'required'    => false,
			),
			'status'         => array(
				'description' => 'The status of the page (publish, draft, pending, etc.).',
				'type'        => 'string',
				'enum'        => array( 'publish', 'future', 'draft', 'pending', 'private', 'any' ),
				'default'     => 'draft',
				'required'    => false,
			),
			'parent'         => array(
				'description' => 'The ID of the parent page.',
				'type'        => 'integer',
				'required'    => false,
			),
			'menu_order'     => array(
				'description' => 'The order of the page in menus.',
				'type'        => 'integer',
				'required'    => false,
			),
			'featured_media' => array(
				'description' => 'The ID of the featured media for the page.',
				'type'        => 'integer',
				'required'    => false,
			),
			'comment_status' => array(
				'description' => 'Whether to allow comments on the page.',
				'type'        => 'string',
				'enum'        => array( 'open', 'closed' ),
				'required'    => false,
			),
			'ping_status'    => array(
				'description' => 'Whether to allow pings on the page.',
				'type'        => 'string',
				'enum'        => array( 'open', 'closed' ),
				'required'    => false,
			),
			'template'       => array(
				'description' => 'The page template to use.',
				'type'        => 'string',
				'required'    => false,
			),
			'meta'           => array(
				'description' => 'Custom meta fields to add to the page.',
				'type'        => 'object',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a page
	 *
	 * @return array
	 */
	protected function get_update_page_args() {
		$args = $this->get_create_page_args();

		// Make title optional for updates.
		$args['title']['required'] = false;

		// Add page ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the page.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving pages
	 *
	 * @return array
	 */
	protected function get_pages_args() {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'required'          => false,
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'required'          => false,
			),
			'search'   => array(
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => false,
			),
			'author'   => array(
				'description' => 'Limit result set to pages assigned to specific authors.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'status'   => array(
				'description' => 'Limit result set to pages with specific statuses.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
					'enum' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'any' ),
				),
				'required'    => false,
			),
			'parent'   => array(
				'description' => 'Limit result set to pages with specific parent IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'orderby'  => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'title', 'modified', 'author', 'menu_order' ),
				'required'    => false,
			),
			'order'    => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'include'  => array(
				'description' => 'Limit result set to specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude'  => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'description' => 'Ensure result set excludes specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for batch creating pages
	 *
	 * @return array
	 */
	protected function get_batch_create_pages_args() {
		return array(
			'pages' => array(
				'description' => 'List of pages to create.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_create_page_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch updating pages
	 *
	 * @return array
	 */
	protected function get_batch_update_pages_args() {
		return array(
			'pages' => array(
				'description' => 'List of pages to update.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'description' => 'Unique identifier for the page.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting pages
	 *
	 * @return array
	 */
	protected function get_batch_delete_pages_args() {
		return array(
			'ids'   => array(
				'description' => 'List of page IDs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get page schema
	 *
	 * @return array
	 */
	public function get_page_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'page',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => 'Unique identifier for the page.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'date'           => array(
					'description' => 'The date the page was published.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_gmt'       => array(
					'description' => 'The date the page was published, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'modified'       => array(
					'description' => 'The date the page was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'modified_gmt'   => array(
					'description' => 'The date the page was last modified, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'slug'           => array(
					'description' => 'The slug of the page.',
					'type'        => 'string',
				),
				'status'         => array(
					'description' => 'The status of the page.',
					'type'        => 'string',
					'enum'        => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'any' ),
				),
				'type'           => array(
					'description' => 'The type of the post.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'link'           => array(
					'description' => 'The URL to the page.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'title'          => array(
					'description' => 'The title of the page.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Title for the page, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML title for the page, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'content'        => array(
					'description' => 'The content of the page.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Content for the page, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML content for the page, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'excerpt'        => array(
					'description' => 'The excerpt of the page.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Excerpt for the page, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML excerpt for the page, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'author'         => array(
					'description' => 'The ID of the user who created the page.',
					'type'        => 'integer',
				),
				'featured_media' => array(
					'description' => 'The ID of the featured media for the page.',
					'type'        => 'integer',
				),
				'parent'         => array(
					'description' => 'The ID of the parent page.',
					'type'        => 'integer',
				),
				'menu_order'     => array(
					'description' => 'The order of the page in menus.',
					'type'        => 'integer',
				),
				'comment_status' => array(
					'description' => 'Whether comments are allowed on the page.',
					'type'        => 'string',
					'enum'        => array( 'open', 'closed' ),
				),
				'ping_status'    => array(
					'description' => 'Whether pings are allowed on the page.',
					'type'        => 'string',
					'enum'        => array( 'open', 'closed' ),
				),
				'template'       => array(
					'description' => 'The page template to use.',
					'type'        => 'string',
				),
				'meta'           => array(
					'description' => 'Meta fields.',
					'type'        => 'object',
				),
			),
		);
	}

	/**
	 * Get batch schema
	 *
	 * @return array
	 */
	public function get_batch_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'description' => 'Whether the batch operation was successful.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'data'    => array(
					'description' => 'Data returned by the batch operation.',
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Create a new page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_page( $request ) {
		// Prepare page data.
		$page_data              = $this->prepare_page_for_database( $request );
		$page_data['post_type'] = 'page';

		// Create the page.
		$page_id = wp_insert_post( $page_data, true );

		if ( is_wp_error( $page_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $page_id->get_error_message(),
				),
				400
			);
		}

		// Handle featured media if provided.
		if ( isset( $request['featured_media'] ) ) {
			set_post_thumbnail( $page_id, $request['featured_media'] );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $page_id, $meta_key, $meta_value );
			}
		}

		// Handle template if provided.
		if ( isset( $request['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $request['template'] );
		}

		// Get the page.
		$page = get_post( $page_id );

		// Format the response.
		$response = $this->prepare_page_for_response( $page );

		// Log the activity.
		/* translators: %1$s is the page title, %2$d is the page ID. */
		Activity_Log_Helper::add_log_entry( 'Pages', sprintf( __( 'Page Created: %1$s (ID: %2$d)', 'sg-ai-studio' ), $page->post_title, $page_id ) );

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
				'data'    => $response,
			),
			201
		);
	}

	/**
	 * Update an existing page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_page( $request ) {
		$page_id = $request['id'];
		$page    = get_post( $page_id );

		if ( is_wp_error( $page ) || ! $page || 'page' !== $page->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid page ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare page data.
		$page_data              = $this->prepare_page_for_database( $request );
		$page_data['ID']        = $page_id;
		$page_data['post_type'] = 'page';

		// Update the page.
		$updated = wp_update_post( $page_data, true );

		if ( is_wp_error( $updated ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $updated->get_error_message(),
				),
				400
			);
		}

		// Handle featured media if provided.
		if ( isset( $request['featured_media'] ) ) {
			set_post_thumbnail( $page_id, $request['featured_media'] );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $page_id, $meta_key, $meta_value );
			}
		}

		// Handle template if provided.
		if ( isset( $request['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $request['template'] );
		}

		// Get the updated page.
		$page = get_post( $page_id );

		// Format the response.
		$response = $this->prepare_page_for_response( $page );

		// Log the activity.
		/* translators: %1$s is the page title, %2$d is the page ID. */
		Activity_Log_Helper::add_log_entry( 'Pages', sprintf( __( 'Page Updated: %1$s (ID: %2$d)', 'sg-ai-studio' ), $page->post_title, $page_id ) );

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
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Delete a page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_page( $request ) {
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

		$page_id = $request['id'];
		$force   = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$page    = get_post( $page_id );

		if ( is_wp_error( $page ) || ! $page || 'page' !== $page->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid page ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get the page before deleting it.
		$previous = $this->prepare_page_for_response( $page );

		// Delete the page.
		$result = wp_delete_post( $page_id, $force );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The page could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		if ( $force ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The page has been permanently deleted.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The page has been moved to the trash.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		}
	}

	/**
	 * Get a list of pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_pages( $request ) {
		// Prepare query arguments.
		$args = array(
			'post_type'      => 'page',
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
			'orderby'        => $request['orderby'],
			'order'          => $request['order'],
		);

		// Handle search parameter.
		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['s'] = $request['search'];
		}

		// Handle author parameter.
		if ( isset( $request['author'] ) && ! empty( $request['author'] ) ) {
			$args['author__in'] = $request['author'];
		}

		// Handle status parameter.
		if ( isset( $request['status'] ) && ! empty( $request['status'] ) ) {
			$args['post_status'] = $request['status'];
		}

		// Handle parent parameter.
		if ( isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
			$args['post_parent__in'] = $request['parent'];
		}

		// Handle include parameter.
		if ( isset( $request['include'] ) && ! empty( $request['include'] ) ) {
			$args['post__in'] = $request['include'];
		}

		// Handle exclude parameter.
		if ( isset( $request['exclude'] ) && ! empty( $request['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			$args['post__not_in'] = $request['exclude'];
		}

		// Get pages.
		$query = new WP_Query( $args );
		$pages = $query->posts;

		// Format the response.
		$data = array();
		foreach ( $pages as $page ) {
			$data[] = $this->prepare_page_for_response( $page );
		}

		// Prepare pagination headers.
		$total_pages = $query->found_posts;
		$max_pages   = $query->max_num_pages;

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'pages'       => $data,
					'total'       => $total_pages,
					'page'        => $request['page'],
					'per_page'    => $request['per_page'],
					'total_pages' => $max_pages,
				),
			),
			200
		);

		return $response;
	}

	/**
	 * Get a single page
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_page( $request ) {
		$page_id = $request['id'];
		$page    = get_post( $page_id );

		if ( is_wp_error( $page ) || ! $page || 'page' !== $page->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid page ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_page_for_response( $page );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Batch create pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_create_pages( $request ) {
		$pages   = $request['pages'];
		$results = array();
		$errors  = array();

		foreach ( $pages as $key => $page_data ) {
			// Create a new request for each page.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add page data to the request.
			foreach ( $page_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the page.
			$response = $this->create_page( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'created' => $results,
					'errors'  => $errors,
				),
			),
			$success ? 201 : 207
		);
	}

	/**
	 * Batch update pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_pages( $request ) {
		$pages   = $request['pages'];
		$results = array();
		$errors  = array();

		foreach ( $pages as $key => $page_data ) {
			if ( ! isset( $page_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Page ID is required for updating pages.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each page.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $page_data['id'] );

			// Add page data to the request.
			foreach ( $page_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the page.
			$response = $this->update_page( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'updated' => $results,
					'errors'  => $errors,
				),
			),
			$success ? 200 : 207
		);
	}

	/**
	 * Batch delete pages
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_pages( $request ) {
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

		$ids     = $request['ids'];
		$force   = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$results = array();
		$errors  = array();

		foreach ( $ids as $key => $page_id ) {
			// Create a new request for each page.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $page_id );
			$sub_request->set_param( 'id', $page_id );
			$sub_request->set_param( 'force', $force );

			// Delete the page.
			$response = $this->delete_page( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $page_id ] = $response->get_data();
			} else {
				$results[ $page_id ] = $response->get_data()['message'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if( \function_exists('\sg_cachepress_purge_cache') ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'deleted' => $results,
					'errors'  => $errors,
				),
			),
			$success ? 200 : 207
		);
	}

	/**
	 * Prepare a page for database insertion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_page.
	 */
	protected function prepare_page_for_database( $request ) {
		$prepared_page = array();

		// Page title.
		if ( isset( $request['title'] ) ) {
			$prepared_page['post_title'] = sanitize_text_field( $request['title'] );
		}

		// Page content.
		if ( isset( $request['content'] ) ) {
			$prepared_page['post_content'] = wp_kses_post( $request['content'] );
		}

		// Page excerpt.
		if ( isset( $request['excerpt'] ) ) {
			$prepared_page['post_excerpt'] = sanitize_textarea_field( $request['excerpt'] );
		}

		// Page author.
		if ( isset( $request['author'] ) ) {
			$prepared_page['post_author'] = absint( $request['author'] );
		} else {
			$prepared_page['post_author'] = get_current_user_id();
		}

		// Page status.
		if ( isset( $request['status'] ) ) {
			$prepared_page['post_status'] = sanitize_text_field( $request['status'] );
		}

		// Parent page.
		if ( isset( $request['parent'] ) ) {
			$prepared_page['post_parent'] = absint( $request['parent'] );
		}

		// Menu order.
		if ( isset( $request['menu_order'] ) ) {
			$prepared_page['menu_order'] = absint( $request['menu_order'] );
		}

		// Comment status.
		if ( isset( $request['comment_status'] ) ) {
			$prepared_page['comment_status'] = sanitize_text_field( $request['comment_status'] );
		}

		// Ping status.
		if ( isset( $request['ping_status'] ) ) {
			$prepared_page['ping_status'] = sanitize_text_field( $request['ping_status'] );
		}

		return $prepared_page;
	}

	/**
	 * Prepare a page for the response
	 *
	 * @param WP_Post $page Page object.
	 * @return array Prepared page data.
	 */
	protected function prepare_page_for_response( $page ) {
		// Get the featured media ID.
		$featured_media_id = get_post_thumbnail_id( $page->ID );

		// Get page template.
		$template = get_post_meta( $page->ID, '_wp_page_template', true );

		// Prepare response.
		$data = array(
			'id'             => $page->ID,
			'date'           => mysql_to_rfc3339( $page->post_date ),
			'date_gmt'       => mysql_to_rfc3339( $page->post_date_gmt ),
			'modified'       => mysql_to_rfc3339( $page->post_modified ),
			'modified_gmt'   => mysql_to_rfc3339( $page->post_modified_gmt ),
			'slug'           => $page->post_name,
			'status'         => $page->post_status,
			'type'           => $page->post_type,
			'link'           => get_permalink( $page->ID ),
			'title'          => array(
				'raw'      => $page->post_title,
				'rendered' => get_the_title( $page->ID ),
			),
			'content'        => array(
				'raw'      => $page->post_content,
				'rendered' => apply_filters( 'the_content', $page->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			),
			'excerpt'        => array(
				'raw'      => $page->post_excerpt,
				'rendered' => apply_filters( 'the_excerpt', $page->post_excerpt ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			),
			'author'         => (int) $page->post_author,
			'featured_media' => (int) $featured_media_id,
			'parent'         => (int) $page->post_parent,
			'menu_order'     => (int) $page->menu_order,
			'comment_status' => $page->comment_status,
			'ping_status'    => $page->ping_status,
			'template'       => $template ? $template : 'default',
		);

		return $data;
	}
}
