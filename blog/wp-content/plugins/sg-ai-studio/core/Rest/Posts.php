<?php
/**
 * Posts API class for managing posts via REST API
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
 * Handles REST API endpoints for post operations.
 */
class Posts extends Rest_Controller_Base {
	use Revisions;
	use Object_Terms;

	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'posts';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new post.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_post' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_post_args(),
					'description'         => 'Creates a new post with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_posts' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_posts_args(),
					'description'         => 'Retrieves a list of posts based on the provided filters.',
				),
				'schema' => array( $this, 'get_post_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single post.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_post' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id'          => array(
							'description' => 'Unique identifier for the post.',
							'type'        => 'integer',
							'required'    => true,
						),
						'embed_terms' => $this->get_embed_terms_arg(),
					),
					'description'         => 'Retrieves a specific post by ID. Pass embed_terms=true to get the assigned terms of every taxonomy as named terms instead of only the category and tag IDs.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_post' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the post.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Updates a specific post with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_post' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the post.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific post. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_post_schema' ),
			)
		);

		// Register endpoint for bulk operations on posts.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_create_posts' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_batch_create_posts_args(),
					'description'         => 'Creates multiple posts in a single request.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_posts' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_batch_update_posts_args(),
					'description'         => 'Updates multiple posts in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_posts' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => $this->get_batch_delete_posts_args(),
					'description'         => 'Deletes multiple posts in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);

		// Register revision endpoints (list, read, restore, prune).
		$this->register_revision_routes( $this->base );

		// Register object term endpoints (read assigned terms, assign terms).
		$this->register_object_terms_routes( $this->base );
	}

	/**
	 * Get arguments for creating a post
	 *
	 * @return array
	 */
	protected function get_create_post_args() {
		return array(
			'title'          => array(
				'description' => 'The title for the post.',
				'type'        => 'string',
				'required'    => true,
			),
			'content'        => array(
				'description' => 'The content for the post.',
				'type'        => 'string',
				'required'    => false,
			),
			'excerpt'        => array(
				'description' => 'The excerpt for the post.',
				'type'        => 'string',
				'required'    => false,
			),
			'author'         => array(
				'description' => 'The ID of the user who created the post.',
				'type'        => 'integer',
				'required'    => false,
			),
			'status'         => array(
				'description' => 'The status of the post (publish, draft, pending, etc.).',
				'type'        => 'string',
				'enum'        => array( 'publish', 'future', 'draft', 'pending', 'private', 'any' ),
				'default'     => 'draft',
				'required'    => false,
			),
			'post_type'      => array(
				'description' => 'The type of the post (post, page, etc.).',
				'type'        => 'string',
				'default'     => 'post',
				'required'    => false,
			),
			'categories'     => array(
				'description' => 'The categories to assign to the post.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'tags'           => array(
				'description' => 'The tags to assign to the post.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'featured_media' => array(
				'description' => 'The ID of the featured media for the post.',
				'type'        => 'integer',
				'required'    => false,
			),
			'comment_status' => array(
				'description' => 'Whether to allow comments on the post.',
				'type'        => 'string',
				'enum'        => array( 'open', 'closed' ),
				'required'    => false,
			),
			'ping_status'    => array(
				'description' => 'Whether to allow pings on the post.',
				'type'        => 'string',
				'enum'        => array( 'open', 'closed' ),
				'required'    => false,
			),
			'meta'           => array(
				'description' => 'Custom meta fields to add to the post.',
				'type'        => 'object',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a post
	 *
	 * @return array
	 */
	protected function get_update_post_args() {
		$args = $this->get_create_post_args();

		// Make title optional for updates.
		$args['title']['required'] = false;

		// Add post ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the post.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving posts
	 *
	 * @return array
	 */
	protected function get_posts_args() {
		return array(
			'embed_terms' => $this->get_embed_terms_arg(),
			'page'        => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'required'          => false,
			),
			'per_page'    => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'required'          => false,
			),
			'search'      => array(
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => false,
			),
			'author'      => array(
				'description' => 'Limit result set to posts assigned to specific authors.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'status'      => array(
				'description' => 'Limit result set to posts with specific statuses.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
					'enum' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'any' ),
				),
				'required'    => false,
			),
			'post_type'   => array(
				'description' => 'Limit result set to posts with specific post types.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'default'     => array( 'post' ),
				'required'    => false,
			),
			'categories'  => array(
				'description' => 'Limit result set to posts with specific categories.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'tags'        => array(
				'description' => 'Limit result set to posts with specific tags.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'orderby'     => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'title', 'modified', 'author', 'comment_count' ),
				'required'    => false,
			),
			'order'       => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'include'     => array(
				'description' => 'Limit result set to specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude'     => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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
	 * Get arguments for batch creating posts
	 *
	 * @return array
	 */
	protected function get_batch_create_posts_args() {
		return array(
			'posts' => array(
				'description' => 'List of posts to create.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_create_post_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch updating posts
	 *
	 * @return array
	 */
	protected function get_batch_update_posts_args() {
		return array(
			'posts' => array(
				'description' => 'List of posts to update.',
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
	 * Get arguments for batch deleting posts
	 *
	 * @return array
	 */
	protected function get_batch_delete_posts_args() {
		return array(
			'ids'   => array(
				'description' => 'List of post IDs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get post schema
	 *
	 * @return array
	 */
	public function get_post_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'post',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => 'Unique identifier for the post.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'date'           => array(
					'description' => 'The date the post was published.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_gmt'       => array(
					'description' => 'The date the post was published, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'modified'       => array(
					'description' => 'The date the post was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'modified_gmt'   => array(
					'description' => 'The date the post was last modified, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'slug'           => array(
					'description' => 'The slug of the post.',
					'type'        => 'string',
				),
				'status'         => array(
					'description' => 'The status of the post.',
					'type'        => 'string',
					'enum'        => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'any' ),
				),
				'type'           => array(
					'description' => 'The type of the post.',
					'type'        => 'string',
				),
				'link'           => array(
					'description' => 'The URL to the post.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'title'          => array(
					'description' => 'The title of the post.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Title for the post, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML title for the post, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'content'        => array(
					'description' => 'The content of the post.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Content for the post, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML content for the post, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'excerpt'        => array(
					'description' => 'The excerpt of the post.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Excerpt for the post, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML excerpt for the post, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'author'         => array(
					'description' => 'The ID of the user who created the post.',
					'type'        => 'integer',
				),
				'featured_media' => array(
					'description' => 'The ID of the featured media for the post.',
					'type'        => 'integer',
				),
				'comment_status' => array(
					'description' => 'Whether comments are allowed on the post.',
					'type'        => 'string',
					'enum'        => array( 'open', 'closed' ),
				),
				'ping_status'    => array(
					'description' => 'Whether pings are allowed on the post.',
					'type'        => 'string',
					'enum'        => array( 'open', 'closed' ),
				),
				'meta'           => array(
					'description' => 'Meta fields.',
					'type'        => 'object',
				),
				'categories'     => array(
					'description' => 'The categories assigned to the post.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'tags'           => array(
					'description' => 'The tags assigned to the post.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'terms'          => array(
					'description' => 'Named terms assigned to the post, keyed by taxonomy slug. Present only when embed_terms=true was requested. A taxonomy with nothing assigned is an empty array.',
					'type'        => 'object',
					'readonly'    => true,
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
	 * Create a new post
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_post( $request ) {
		// Prepare post data.
		$post_data = $this->prepare_post_for_database( $request );

		// Create the post.
		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $post_id->get_error_message(),
				),
				400
			);
		}

		// Handle categories if provided.
		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			wp_set_post_categories( $post_id, $request['categories'] );
		}

		// Handle tags if provided.
		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			wp_set_post_tags( $post_id, $request['tags'] );
		}

		// Handle featured media if provided.
		if ( isset( $request['featured_media'] ) ) {
			set_post_thumbnail( $post_id, $request['featured_media'] );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $post_id, $meta_key, $meta_value );
			}
		}

		// Get the post.
		$post = get_post( $post_id );

		// Log the activity.
		/* translators: %1$s is the post title, %2$d is the post ID. */
		Activity_Log_Helper::add_log_entry( 'Posts', sprintf( __( 'Post Created: %1$s (ID: %2$d)', 'sg-ai-studio' ), $post->post_title, $post_id ) );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		// Return lean response for write operation.
		return new WP_REST_Response(
			array(
				'success'  => true,
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'status'   => $post->post_status,
				'link'     => get_permalink( $post->ID ),
				'modified' => mysql_to_rfc3339( $post->post_modified ),
			),
			201
		);
	}

	/**
	 * Update an existing post
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_post( $request ) {
		$post_id = $request['id'];
		$post    = get_post( $post_id );

		if ( is_wp_error( $post ) || ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid post ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Save a pre-edit revision so the edit has a deterministic restore point.
		// Respects the site's revision config; 0 when disabled/unsupported.
		$revision_id = Helper::save_pre_edit_revision( $post_id );

		// Prepare post data.
		$post_data       = $this->prepare_post_for_database( $request );
		$post_data['ID'] = $post_id;

		// Update the post.
		$updated = wp_update_post( $post_data, true );

		if ( is_wp_error( $updated ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $updated->get_error_message(),
				),
				400
			);
		}

		// Handle categories if provided.
		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			wp_set_post_categories( $post_id, $request['categories'] );
		}

		// Handle tags if provided.
		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			wp_set_post_tags( $post_id, $request['tags'] );
		}

		// Handle featured media if provided.
		if ( isset( $request['featured_media'] ) ) {
			set_post_thumbnail( $post_id, $request['featured_media'] );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $post_id, $meta_key, $meta_value );
			}
		}

		// Get the updated post.
		$post = get_post( $post_id );

		// Log the activity.
		/* translators: %1$s is the post title, %2$d is the post ID. */
		Activity_Log_Helper::add_log_entry( 'Posts', sprintf( __( 'Post Updated: %1$s (ID: %2$d)', 'sg-ai-studio' ), $post->post_title, $post_id ) );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		// Return lean response for write operation.
		return new WP_REST_Response(
			array(
				'success'           => true,
				'id'                => $post->ID,
				'title'             => $post->post_title,
				'status'            => $post->post_status,
				'link'              => get_permalink( $post->ID ),
				'modified'          => mysql_to_rfc3339( $post->post_modified ),
				'revision_id'       => $revision_id ? $revision_id : null,
				'revisions_enabled' => wp_revisions_enabled( $post ),
			),
			200
		);
	}

	/**
	 * Delete a post
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_post( $request ) {
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

		$post_id = $request['id'];
		$force   = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$post    = get_post( $post_id );

		if ( is_wp_error( $post ) || ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid post ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Delete the post.
		$result = wp_delete_post( $post_id, $force );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The post could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		if ( $force ) {
			/* translators: %1$s is the post title, %2$d is the post ID. */
			Activity_Log_Helper::add_log_entry( 'Posts', sprintf( __( 'Post Permanently Deleted: %1$s (ID: %2$d)', 'sg-ai-studio' ), $post->post_title, $post_id ) );
		} else {
			/* translators: %1$s is the post title, %2$d is the post ID. */
			Activity_Log_Helper::add_log_entry( 'Posts', sprintf( __( 'Post Moved to Trash: %1$s (ID: %2$d)', 'sg-ai-studio' ), $post->post_title, $post_id ) );
		}

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		// Return lean response for delete operation.
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $post_id,
				'status'  => $force ? 'deleted' : 'trashed',
			),
			200
		);
	}

	/**
	 * Get a list of posts
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_posts( $request ) {
		// Prepare query arguments.
		$args = array(
			'post_type'      => isset( $request['post_type'] ) ? $request['post_type'] : array( 'post' ),
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

		// Handle categories parameter.
		if ( isset( $request['categories'] ) && ! empty( $request['categories'] ) ) {
			$args['category__in'] = $request['categories'];
		}

		// Handle tags parameter.
		if ( isset( $request['tags'] ) && ! empty( $request['tags'] ) ) {
			$args['tag__in'] = $request['tags'];
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

		// Get posts.
		$query = new WP_Query( $args );
		$posts = $query->posts;

		$embed_terms = (bool) $request['embed_terms'];

		// Prime the object term cache once so embedding terms is a single query, not one per post.
		if ( $embed_terms && ! empty( $posts ) ) {
			update_object_term_cache(
				wp_list_pluck( $posts, 'ID' ),
				array_values( array_unique( wp_list_pluck( $posts, 'post_type' ) ) )
			);
		}

		// Format the response.
		$data = array();
		foreach ( $posts as $post ) {
			$data[] = $this->prepare_post_for_response( $post, 'list', $embed_terms );
		}

		// Prepare pagination headers.
		$total_posts = $query->found_posts;
		$max_pages   = $query->max_num_pages;

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'posts'       => $data,
					'total'       => $total_posts,
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
	 * Get a single post
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_post( $request ) {
		$post_id = $request['id'];
		$post    = get_post( $post_id );

		if ( is_wp_error( $post ) || ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid post ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_post_for_response( $post, 'view', (bool) $request['embed_terms'] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Batch create posts
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_create_posts( $request ) {
		$posts   = $request['posts'];
		$results = array();
		$errors  = array();

		foreach ( $posts as $key => $post_data ) {
			// Create a new request for each post.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add post data to the request.
			foreach ( $post_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the post.
			$response = $this->create_post( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
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
	 * Batch update posts
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_posts( $request ) {
		$posts   = $request['posts'];
		$results = array();
		$errors  = array();

		foreach ( $posts as $key => $post_data ) {
			if ( ! isset( $post_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Post ID is required for updating posts.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each post.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $post_data['id'] );

			// Add post data to the request.
			foreach ( $post_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the post.
			$response = $this->update_post( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
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
	 * Batch delete posts
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_posts( $request ) {
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

		foreach ( $ids as $key => $post_id ) {
			// Create a new request for each post.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $post_id );
			$sub_request->set_param( 'id', $post_id );
			$sub_request->set_param( 'force', $force );

			// Delete the post.
			$response = $this->delete_post( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $post_id ] = $response->get_data();
			} else {
				$results[ $post_id ] = $response->get_data()['status'];
			}
		}

		$success = empty( $errors );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
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
	 * Prepare a post for database insertion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_post.
	 */
	protected function prepare_post_for_database( $request ) {
		$prepared_post = array();

		// Post type.
		if ( isset( $request['post_type'] ) ) {
			$prepared_post['post_type'] = sanitize_text_field( $request['post_type'] );
		} else {
			$prepared_post['post_type'] = 'post';
		}

		// Post title.
		if ( isset( $request['title'] ) ) {
			$prepared_post['post_title'] = sanitize_text_field( $request['title'] );
		}

		// Post content.
		if ( isset( $request['content'] ) ) {
			$prepared_post['post_content'] = wp_kses_post( $request['content'] );
		}

		// Post excerpt.
		if ( isset( $request['excerpt'] ) ) {
			$prepared_post['post_excerpt'] = sanitize_textarea_field( $request['excerpt'] );
		}

		// Post author.
		if ( isset( $request['author'] ) ) {
			$prepared_post['post_author'] = absint( $request['author'] );
		} else {
			$prepared_post['post_author'] = get_current_user_id();
		}

		// Post status.
		if ( isset( $request['status'] ) ) {
			$prepared_post['post_status'] = sanitize_text_field( $request['status'] );
		}

		// Comment status.
		if ( isset( $request['comment_status'] ) ) {
			$prepared_post['comment_status'] = sanitize_text_field( $request['comment_status'] );
		}

		// Ping status.
		if ( isset( $request['ping_status'] ) ) {
			$prepared_post['ping_status'] = sanitize_text_field( $request['ping_status'] );
		}

		return $prepared_post;
	}

	/**
	 * Prepare a post for the response
	 *
	 * @param \WP_Post $post        Post object.
	 * @param string   $context     Request context: 'view' for single reads (full fidelity)
	 *                              or 'list' for collection responses (heavy fields omitted).
	 * @param bool     $embed_terms Whether to include the named terms of every taxonomy
	 *                              on the post, alongside the bare category and tag IDs.
	 * @return array Prepared post data.
	 */
	protected function prepare_post_for_response( $post, $context = 'view', $embed_terms = false ) {
		// Get the post categories.
		$category_ids = wp_get_post_categories( $post->ID );

		// Get the post tags.
		$tag_ids = wp_get_post_tags( $post->ID, array( 'fields' => 'ids' ) );

		// Get the featured media ID.
		$featured_media_id = get_post_thumbnail_id( $post->ID );

		// Prepare response.
		$data = array(
			'id'             => $post->ID,
			'date'           => mysql_to_rfc3339( $post->post_date ),
			'modified'       => mysql_to_rfc3339( $post->post_modified ),
			'slug'           => $post->post_name,
			'status'         => $post->post_status,
			'type'           => $post->post_type,
			'link'           => get_permalink( $post->ID ),
			'title'          => $post->post_title,
			'content'        => $post->post_content,
			'excerpt'        => $post->post_excerpt,
			'author'         => (int) $post->post_author,
			'featured_media' => (int) $featured_media_id,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'categories'     => $category_ids,
			'tags'           => $tag_ids,
		);

		// Named terms for every taxonomy on the post, so callers never have to
		// report a bare term ID. Additive: `categories` and `tags` are untouched.
		if ( $embed_terms ) {
			$data['terms'] = $this->get_object_terms_map( $post->ID, $post->post_type );
		}

		// Omit heavy fields in list context to keep collection responses small.
		// Single reads (context 'view') retain full content.
		if ( 'list' === $context ) {
			unset( $data['content'], $data['excerpt'] );
		}

		return $data;
	}
}
