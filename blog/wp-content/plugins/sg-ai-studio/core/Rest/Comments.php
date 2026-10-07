<?php
/**
 * Comments API class for managing WordPress comments via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Comment_Query;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for comment operations.
 */
class Comments extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'comments';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new comment.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_comment' ),
					'permission_callback' => array( $this, 'create_comment_permissions_check' ),
					'description'         => 'Creates a new comment with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_comments' ),
					'permission_callback' => array( $this, 'get_comments_permissions_check' ),
					'description'         => 'Retrieves a list of comments based on the provided filters.',
				),
				'schema' => array( $this, 'get_comment_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single comment.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_comment' ),
					'permission_callback' => array( $this, 'get_comment_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the comment.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific comment by ID.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_comment' ),
					'permission_callback' => array( $this, 'update_comment_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the comment.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Updates a specific comment with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_comment' ),
					'permission_callback' => array( $this, 'delete_comment_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the comment.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific comment. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_comment_schema' ),
			)
		);

		// Register endpoint for moderating a comment.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)/moderate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'moderate_comment' ),
				'permission_callback' => array( $this, 'moderate_comment_permissions_check' ),
				'description'         => 'Moderates a comment by changing its status.',
			)
		);

		// Register endpoint for bulk operations on comments.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_create_comments' ),
					'permission_callback' => array( $this, 'create_comment_permissions_check' ),
					'args'                => $this->get_batch_create_comments_args(),
					'description'         => 'Creates multiple comments in a single request.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_comments' ),
					'permission_callback' => array( $this, 'update_comment_permissions_check' ),
					'args'                => $this->get_batch_update_comments_args(),
					'description'         => 'Updates multiple comments in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_comments' ),
					'permission_callback' => array( $this, 'delete_comment_permissions_check' ),
					'args'                => $this->get_batch_delete_comments_args(),
					'description'         => 'Deletes multiple comments in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to create comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_comment_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_comments_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read a specific comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read the item, WP_Error object otherwise.
	 */
	public function get_comment_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to update a comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_comment_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to delete a comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_comment_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to moderate a comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to moderate the item, WP_Error object otherwise.
	 */
	public function moderate_comment_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for creating a comment
	 *
	 * @return array
	 */
	protected function get_create_comment_args() {
		return array(
			'content'           => array(
				'description' => 'The content for the comment.',
				'type'        => 'string',
				'required'    => true,
			),
			'post'              => array(
				'description' => 'The ID of the associated post object.',
				'type'        => 'integer',
				'required'    => true,
			),
			'parent'            => array(
				'description' => 'The ID of the parent comment.',
				'type'        => 'integer',
				'required'    => false,
			),
			'author_name'       => array(
				'description' => 'Display name for the comment author.',
				'type'        => 'string',
				'required'    => false,
			),
			'author_email'      => array(
				'description' => 'Email address for the comment author.',
				'type'        => 'string',
				'format'      => 'email',
				'required'    => false,
			),
			'author_url'        => array(
				'description' => 'URL for the comment author.',
				'type'        => 'string',
				'format'      => 'uri',
				'required'    => false,
			),
			'author_ip'         => array(
				'description' => 'IP address for the comment author.',
				'type'        => 'string',
				'format'      => 'ip',
				'required'    => false,
			),
			'author_user_agent' => array(
				'description' => 'User agent for the comment author.',
				'type'        => 'string',
				'required'    => false,
			),
			'status'            => array(
				'description' => 'State of the comment.',
				'type'        => 'string',
				'enum'        => array( 'approve', 'hold', 'spam', 'trash', 'any' ),
				'default'     => 'hold',
				'required'    => false,
			),
			'type'              => array(
				'description' => 'Type of comment.',
				'type'        => 'string',
				'default'     => 'comment',
				'required'    => false,
			),
			'meta'              => array(
				'description' => 'Meta fields.',
				'type'        => 'object',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a comment
	 *
	 * @return array
	 */
	protected function get_update_comment_args() {
		$args = [];
		// Add comment ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the comment.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving comments
	 *
	 * @return array
	 */
	protected function get_comments_args() {
		return array(
			'page'         => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'required'          => false,
			),
			'per_page'     => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'required'          => false,
			),
			'search'       => array(
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => false,
			),
			'author'       => array(
				'description' => 'Limit result set to comments assigned to specific user IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'author_email' => array(
				'description' => 'Limit result set to that from a specific author email.',
				'type'        => 'string',
				'format'      => 'email',
				'required'    => false,
			),
			'post'         => array(
				'description' => 'Limit result set to comments assigned to specific post IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'parent'       => array(
				'description' => 'Limit result set to comments with specific parent IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'status'       => array(
				'description' => 'Limit result set to comments assigned to specific statuses.',
				'type'        => 'string',
				'enum'        => array( 'approve', 'hold', 'spam', 'trash', 'all', 'any' ),
				'default'     => 'approve',
				'required'    => false,
			),
			'type'         => array(
				'description' => 'Limit result set to comments assigned to specific types.',
				'type'        => 'string',
				'required'    => false,
			),
			'orderby'      => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date_gmt',
				'enum'        => array( 'date', 'date_gmt', 'comment_post_ID', 'comment_author', 'comment_karma' ),
				'required'    => false,
			),
			'order'        => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'asc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'include'      => array(
				'description' => 'Limit result set to specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude'      => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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
	 * Get arguments for batch creating comments
	 *
	 * @return array
	 */
	protected function get_batch_create_comments_args() {
		return array(
			'comments' => array(
				'description' => 'List of comments to create.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_create_comment_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch updating comments
	 *
	 * @return array
	 */
	protected function get_batch_update_comments_args() {
		return array(
			'comments' => array(
				'description' => 'List of comments to update.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_update_comment_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting comments
	 *
	 * @return array
	 */
	protected function get_batch_delete_comments_args() {
		return array(
			'ids'   => array(
				'description' => 'List of comment IDs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get comment schema
	 *
	 * @return array
	 */
	public function get_comment_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'comment',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => 'Unique identifier for the comment.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'post'              => array(
					'description' => 'The ID of the associated post object.',
					'type'        => 'integer',
				),
				'parent'            => array(
					'description' => 'The ID of the parent comment.',
					'type'        => 'integer',
				),
				'author'            => array(
					'description' => 'The ID of the user object, if author was a user.',
					'type'        => 'integer',
				),
				'author_name'       => array(
					'description' => 'Display name for the comment author.',
					'type'        => 'string',
				),
				'author_email'      => array(
					'description' => 'Email address for the comment author.',
					'type'        => 'string',
					'format'      => 'email',
				),
				'author_url'        => array(
					'description' => 'URL for the comment author.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'author_ip'         => array(
					'description' => 'IP address for the comment author.',
					'type'        => 'string',
					'format'      => 'ip',
				),
				'author_user_agent' => array(
					'description' => 'User agent for the comment author.',
					'type'        => 'string',
				),
				'date'              => array(
					'description' => 'The date the comment was published.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_gmt'          => array(
					'description' => 'The date the comment was published, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'content'           => array(
					'description' => 'The content of the comment.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Content for the comment, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML content for the comment, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'link'              => array(
					'description' => 'URL to the comment.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'status'            => array(
					'description' => 'State of the comment.',
					'type'        => 'string',
					'enum'        => array( 'approve', 'hold', 'spam', 'trash', 'any' ),
				),
				'type'              => array(
					'description' => 'Type of comment.',
					'type'        => 'string',
				),
				'meta'              => array(
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
	 * Create a new comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_comment( $request ) {
		// Prepare comment data.
		$comment_data = $this->prepare_comment_for_database( $request );

		// Check if the post exists and allows comments.
		$post = get_post( $comment_data['comment_post_ID'] );
		if ( ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid post ID.', 'sg-ai-studio' ),
				),
				400
			);
		}

		if ( ! comments_open( $post->ID ) && ! current_user_can( 'moderate_comments' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Comments are closed for this post.', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Create the comment.
		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Comment creation failed.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_comment_meta( $comment_id, $meta_key, $meta_value );
			}
		}

		// Get the comment.
		$comment = get_comment( $comment_id );

		// Log the activity.
		$post       = get_post( $comment_data['comment_post_ID'] );
		$post_title = $post ? $post->post_title : "Post ID: {$comment_data['comment_post_ID']}";
		/* translators: %1$s is the post title, %2$d is the comment ID. */
		Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Comment Created on: %1$s (Comment ID: %2$d)', 'sg-ai-studio' ), $post_title, $comment_id ) );

		// Format the response.
		$response = $this->prepare_comment_for_response( $comment );

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
	 * Update an existing comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_comment( $request ) {
		$comment_id = $request['id'];
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid comment ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare comment data.
		$comment_data               = $this->prepare_comment_for_database( $request );
		$comment_data['comment_ID'] = $comment_id;

		// Update the comment.
		$updated = wp_update_comment( $comment_data );

		if ( ! $updated ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Comment update failed.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_comment_meta( $comment_id, $meta_key, $meta_value );
			}
		}

		// Get the updated comment.
		$comment = get_comment( $comment_id );

		// Log the activity.
		$post       = get_post( $comment->comment_post_ID );
		$post_title = $post ? $post->post_title : "Post ID: {$comment->comment_post_ID}";
		/* translators: %1$s is the post title, %2$d is the comment ID. */
		Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Comment Updated on: %1$s (Comment ID: %2$d)', 'sg-ai-studio' ), $post_title, $comment_id ) );

		// Format the response.
		$response = $this->prepare_comment_for_response( $comment );

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
	 * Delete a comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_comment( $request ) {
		$comment_id = $request['id'];
		$force      = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid comment ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get the comment before deleting it.
		$previous = $this->prepare_comment_for_response( $comment );

		// Delete the comment.
		$result = wp_delete_comment( $comment_id, $force );

		if ( $result ) {
			// Log the activity.
			$post       = get_post( $comment->comment_post_ID );
			$post_title = $post ? $post->post_title : "Post ID: {$comment->comment_post_ID}";
			if ( $force ) {
				/* translators: %1$s is the post title, %2$d is the comment ID. */
				Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Comment Permanently Deleted from: %1$s (Comment ID: %2$d)', 'sg-ai-studio' ), $post_title, $comment_id ) );
			} else {
				/* translators: %1$s is the post title, %2$d is the comment ID. */
				Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Comment Moved to Trash from: %1$s (Comment ID: %2$d)', 'sg-ai-studio' ), $post_title, $comment_id ) );
			}
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The comment could not be deleted.', 'sg-ai-studio' ),
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
					'message' => __( 'The comment has been permanently deleted.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The comment has been moved to the trash.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		}
	}

	/**
	 * Moderate a comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function moderate_comment( $request ) {
		$comment_id = $request['id'];
		$status     = $request['status'];
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid comment ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Map status values.
		$status_map = array(
			'approve' => 'approve',
			'hold'    => 'hold',
			'spam'    => 'spam',
			'trash'   => 'trash',
		);

		if ( ! isset( $status_map[ $status ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid comment status.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Update the comment status.
		$result = wp_set_comment_status( $comment_id, $status_map[ $status ] );

		if ( $result ) {
			// Log the activity.
			$post       = get_post( $comment->comment_post_ID );
			$post_title = $post ? $post->post_title : "Post ID: {$comment->comment_post_ID}";
			/* translators: %1$s is the new status, %2$s is the post title, %3$d is the comment ID. */
			Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Comment Status Changed to %1$s on: %2$s (Comment ID: %3$d)', 'sg-ai-studio' ), $status, $post_title, $comment_id ) );
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Comment moderation failed.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Get the updated comment.
		$comment = get_comment( $comment_id );

		// Format the response.
		$response = $this->prepare_comment_for_response( $comment );

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
				/* translators: %s is the new comment status. */
				'message' => sprintf( __( 'Comment status changed to %s.', 'sg-ai-studio' ), $status ),
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Get a list of comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_comments( $request ) {
		$request['per_page'] = (int) $request['per_page'] ?: -1;
		$request['page'] = $request['page'] ?: 1;

		// Prepare query arguments.
		$args = array(
			'number'  => $request['per_page'],
			'offset'  => ( $request['page'] - 1 ) * $request['per_page'],
			'orderby' => $request['orderby'],
			'order'   => $request['order'],
			'status'  => $request['status'],
		);

		// Handle search parameter.
		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
		}

		// Handle author parameter.
		if ( isset( $request['author'] ) && ! empty( $request['author'] ) ) {
			$args['user_id'] = $request['author'];
		}

		// Handle author email parameter.
		if ( isset( $request['author_email'] ) && ! empty( $request['author_email'] ) ) {
			$args['author_email'] = $request['author_email'];
		}

		// Handle post parameter.
		if ( isset( $request['post'] ) && ! empty( $request['post'] ) ) {
			$args['post_id'] = $request['post'];
		}

		// Handle parent parameter.
		if ( isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
			$args['parent'] = $request['parent'];
		}

		// Handle type parameter.
		if ( isset( $request['type'] ) && ! empty( $request['type'] ) ) {
			$args['type'] = $request['type'];
		}

		// Handle include parameter.
		if ( isset( $request['include'] ) && ! empty( $request['include'] ) ) {
			$args['comment__in'] = $request['include'];
		}

		// Handle exclude parameter.
		if ( isset( $request['exclude'] ) && ! empty( $request['exclude'] ) ) {
			$args['comment__not_in'] = $request['exclude'];
		}

		// Get comments.
		$query    = new WP_Comment_Query( $args );
		$comments = $query->comments;

		// Format the response.
		$data = array();
		foreach ( $comments as $comment ) {
			$data[] = $this->prepare_comment_for_response( $comment );
		}

		// Get total count for pagination.
		$total_args          = $args;
		$total_args['count'] = true;
		unset( $total_args['number'], $total_args['offset'] );
		$total_query    = new WP_Comment_Query( $total_args );
		$total_comments = $total_query->get_comments();

		$max_pages = ceil( $total_comments / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'comments'    => $data,
					'total'       => $total_comments,
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
	 * Get a single comment
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_comment( $request ) {
		$comment_id = $request['id'];
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid comment ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_comment_for_response( $comment );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Batch create comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_create_comments( $request ) {
		$comments = $request['comments'];
		$results  = array();
		$errors   = array();

		foreach ( $comments as $key => $comment_data ) {
			// Create a new request for each comment.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add comment data to the request.
			foreach ( $comment_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the comment.
			$response = $this->create_comment( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Log the batch activity.
		$created_count = count( $results );
		$error_count   = count( $errors );
		/* translators: %1$d is the number of comments created, %2$d is the number of errors. */
		Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Batch Comment Creation: %1$d created, %2$d errors', 'sg-ai-studio' ), $created_count, $error_count ) );

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
	 * Batch update comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_comments( $request ) {
		$comments = $request['comments'];
		$results  = array();
		$errors   = array();

		foreach ( $comments as $key => $comment_data ) {
			if ( ! isset( $comment_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Comment ID is required for updating comments.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each comment.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $comment_data['id'] );

			// Add comment data to the request.
			foreach ( $comment_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the comment.
			$response = $this->update_comment( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Log the batch activity.
		$updated_count = count( $results );
		$error_count   = count( $errors );
		/* translators: %1$d is the number of comments updated, %2$d is the number of errors. */
		Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Batch Comment Update: %1$d updated, %2$d errors', 'sg-ai-studio' ), $updated_count, $error_count ) );

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
	 * Batch delete comments
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_comments( $request ) {
		$ids     = $request['ids'];
		$force   = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$results = array();
		$errors  = array();

		foreach ( $ids as $key => $comment_id ) {
			// Create a new request for each comment.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $comment_id );
			$sub_request->set_param( 'id', $comment_id );
			$sub_request->set_param( 'force', $force );

			// Delete the comment.
			$response = $this->delete_comment( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $comment_id ] = $response->get_data();
			} else {
				$results[ $comment_id ] = $response->get_data()['message'];
			}
		}

		$success = empty( $errors );

		// Log the batch activity.
		$deleted_count = count( $results );
		$error_count   = count( $errors );
		/* translators: %1$d is the number of comments deleted, %2$d is the number of errors. */
		Activity_Log_Helper::add_log_entry( 'Comments', sprintf( __( 'Batch Comment Deletion: %1$d deleted, %2$d errors', 'sg-ai-studio' ), $deleted_count, $error_count ) );

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
	 * Prepare a comment for database insertion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_comment.
	 */
	protected function prepare_comment_for_database( $request ) {
		$prepared_comment = array();

		// Comment content.
		if ( isset( $request['content'] ) ) {
			$prepared_comment['comment_content'] = wp_kses( $request['content'], array() );
		}

		// Post ID.
		if ( isset( $request['post'] ) ) {
			$prepared_comment['comment_post_ID'] = absint( $request['post'] );
		}

		// Parent comment.
		if ( isset( $request['parent'] ) ) {
			$prepared_comment['comment_parent'] = absint( $request['parent'] );
		}

		// Author information.
		if ( isset( $request['author_name'] ) ) {
			$prepared_comment['comment_author'] = sanitize_text_field( $request['author_name'] );
		}

		if ( isset( $request['author_email'] ) ) {
			$prepared_comment['comment_author_email'] = sanitize_email( $request['author_email'] );
		}

		if ( isset( $request['author_url'] ) ) {
			$prepared_comment['comment_author_url'] = esc_url_raw( $request['author_url'] );
		}

		if ( isset( $request['author_ip'] ) ) {
			$prepared_comment['comment_author_IP'] = sanitize_text_field( $request['author_ip'] );
		}

		if ( isset( $request['author_user_agent'] ) ) {
			$prepared_comment['comment_agent'] = sanitize_text_field( $request['author_user_agent'] );
		}

		// Comment type.
		if ( isset( $request['type'] ) ) {
			$prepared_comment['comment_type'] = sanitize_text_field( $request['type'] );
		}

		// Comment status.
		if ( isset( $request['status'] ) ) {
			$status_map = array(
				'approve' => 1,
				'hold'    => 0,
				'spam'    => 'spam',
				'trash'   => 'trash',
			);

			if ( isset( $status_map[ $request['status'] ] ) ) {
				$prepared_comment['comment_approved'] = $status_map[ $request['status'] ];
			}
		}

		// Set current user as author if logged in and no author info provided.
		if ( is_user_logged_in() && ! isset( $prepared_comment['comment_author'] ) ) {
			$user                                     = wp_get_current_user();
			$prepared_comment['user_id']              = $user->ID;
			$prepared_comment['comment_author']       = $user->display_name;
			$prepared_comment['comment_author_email'] = $user->user_email;
			$prepared_comment['comment_author_url']   = $user->user_url;
		}

		return $prepared_comment;
	}

	/**
	 * Prepare a comment for the response
	 *
	 * @param  array|\WP_Comment|null $comment Comment object.
	 * @return array Prepared comment data.
	 */
	protected function prepare_comment_for_response( $comment ) {
		// Map status values.
		$status_map = array(
			'1'     => 'approve',
			'0'     => 'hold',
			'spam'  => 'spam',
			'trash' => 'trash',
		);

		$status = isset( $status_map[ $comment->comment_approved ] ) ? $status_map[ $comment->comment_approved ] : 'hold';

		// Prepare response.
		$data = array(
			'id'                => (int) $comment->comment_ID,
			'post'              => (int) $comment->comment_post_ID,
			'parent'            => (int) $comment->comment_parent,
			'author'            => (int) $comment->user_id,
			'author_name'       => $comment->comment_author,
			'author_email'      => $comment->comment_author_email,
			'author_url'        => $comment->comment_author_url,
			'author_ip'         => $comment->comment_author_IP,
			'author_user_agent' => $comment->comment_agent,
			'date'              => mysql_to_rfc3339( $comment->comment_date ),
			'date_gmt'          => mysql_to_rfc3339( $comment->comment_date_gmt ),
			'content'           => array(
				'raw'      => $comment->comment_content,
				'rendered' => apply_filters( 'comment_text', $comment->comment_content, $comment ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			),
			'link'              => get_comment_link( $comment ),
			'status'            => $status,
			'type'              => $comment->comment_type,
		);

		return $data;
	}
}
