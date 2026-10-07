<?php
/**
 * Tags API class for managing tags via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Term;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for tag operations.
 */
class Tags extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'tags';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new tag and listing tags.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_tag' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_tag_args(),
					'description'         => 'Creates a new tag with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_tags' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_tags_args(),
					'description'         => 'Retrieves a list of tags based on the provided filters.',
				),
				'schema' => array( $this, 'get_tag_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single tag.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_tag' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the tag.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific tag by ID.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_tag' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_update_tag_args(),
					'description'         => 'Updates a specific tag with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_tag' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the tag.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific tag.',
				),
				'schema' => array( $this, 'get_tag_schema' ),
			)
		);
	}

	/**
	 * Get arguments for creating a tag
	 *
	 * @return array
	 */
	protected function get_create_tag_args() {
		return array(
			'name'        => array(
				'description' => 'The name for the tag.',
				'type'        => 'string',
				'required'    => true,
			),
			'description' => array(
				'description' => 'The description for the tag.',
				'type'        => 'string',
				'required'    => false,
			),
			'slug'        => array(
				'description' => 'The slug for the tag.',
				'type'        => 'string',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a tag
	 *
	 * @return array
	 */
	protected function get_update_tag_args() {
		$args = [];
		// Add tag ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the tag.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving tags
	 *
	 * @return array
	 */
	protected function get_tags_args() {
		return array(
			'page'       => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'required'          => false,
			),
			'per_page'   => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'required'          => false,
			),
			'search'     => array(
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => false,
			),
			'orderby'    => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'name',
				'enum'        => array( 'name', 'count', 'term_id' ),
				'required'    => false,
			),
			'order'      => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'asc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'hide_empty' => array(
				'description' => 'Whether to hide tags not assigned to any posts.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'include'    => array(
				'description' => 'Limit result set to specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude'    => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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
	 * Get tag schema
	 *
	 * @return array
	 */
	public function get_tag_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'tag',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => 'Unique identifier for the tag.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'count'       => array(
					'description' => 'Number of posts with the tag.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'description' => array(
					'description' => 'The description of the tag.',
					'type'        => 'string',
				),
				'link'        => array(
					'description' => 'The URL to the tag archive.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'name'        => array(
					'description' => 'The name of the tag.',
					'type'        => 'string',
				),
				'slug'        => array(
					'description' => 'The slug of the tag.',
					'type'        => 'string',
				),
			),
		);
	}

	/**
	 * Create a new tag
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_tag( $request ) {
		// Prepare tag data.
		$tag_data = $this->prepare_tag_for_database( $request );

		// Create the tag.
		$result = wp_insert_term( $tag_data['name'], 'post_tag', $tag_data );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$term_id = $result['term_id'];

		// Get the tag.
		$tag = get_term( $term_id, 'post_tag' );

		// Format the response.
		$response = $this->prepare_tag_for_response( $tag );

		// Log the activity.
		Activity_Log_Helper::add_log_entry( 'Tags', "Tag Created: {$tag->name} (ID: {$term_id})" );

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
	 * Update an existing tag
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_tag( $request ) {
		$term_id = $request['id'];
		$tag     = get_term( $term_id, 'post_tag' );

		if ( is_wp_error( $tag ) || ! $tag ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid tag ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare tag data.
		$tag_data = $this->prepare_tag_for_database( $request );

		// Update the tag.
		$result = wp_update_term( $term_id, 'post_tag', $tag_data );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		// Get the updated tag.
		$tag = get_term( $term_id, 'post_tag' );

		// Format the response.
		$response = $this->prepare_tag_for_response( $tag );

		// Log the activity.
		Activity_Log_Helper::add_log_entry( 'Tags', "Tag Updated: {$tag->name} (ID: {$term_id})" );

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
	 * Delete a tag
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_tag( $request ) {
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

		$term_id = $request['id'];
		$tag     = get_term( $term_id, 'post_tag' );

		if ( is_wp_error( $tag ) || ! $tag ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid tag ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get the tag before deleting it.
		$previous = $this->prepare_tag_for_response( $tag );

		// Delete the tag.
		$result = wp_delete_term( $term_id, 'post_tag' );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The tag could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		Activity_Log_Helper::add_log_entry( 'Tags', "Tag Deleted: {$previous['name']} (ID: {$term_id})" );

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
				'message' => __( 'The tag has been permanently deleted.', 'sg-ai-studio' ),
				'data'    => $previous,
			),
			200
		);
	}

	/**
	 * Get a list of tags
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tags( $request ) {
		// Validate per_page parameter.
		if ( $request['per_page'] < 1 ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid per_page parameter. Must be at least 1.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Prepare query arguments.
		$args = array(
			'taxonomy'   => 'post_tag',
			'number'     => $request['per_page'],
			'offset'     => ( $request['page'] - 1 ) * $request['per_page'],
			'orderby'    => $request['orderby'],
			'order'      => $request['order'],
			'hide_empty' => $request['hide_empty'],
		);

		// Handle search parameter.
		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
		}

		// Handle include parameter.
		if ( isset( $request['include'] ) && ! empty( $request['include'] ) ) {
			$args['include'] = $request['include'];
		}

		// Handle exclude parameter.
		if ( isset( $request['exclude'] ) && ! empty( $request['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$args['exclude'] = $request['exclude'];
		}

		// Get tags.
		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $terms->get_error_message(),
				),
				400
			);
		}

		// Format the response.
		$data = array();
		foreach ( $terms as $term ) {
			$data[] = $this->prepare_tag_for_response( $term );
		}

		// Get total count for pagination.
		$total_args         = $args;
		$total_args['number'] = 0;
		$total_args['offset'] = 0;
		$total_tags         = get_terms( $total_args );
		$total              = is_array( $total_tags ) ? count( $total_tags ) : 0;
		$total_pages        = ceil( $total / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'tags'        => $data,
					'total'       => $total,
					'page'        => $request['page'],
					'per_page'    => $request['per_page'],
					'total_pages' => $total_pages,
				),
			),
			200
		);

		return $response;
	}

	/**
	 * Get a single tag
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tag( $request ) {
		$term_id = $request['id'];
		$tag     = get_term( $term_id, 'post_tag' );

		if ( is_wp_error( $tag ) || ! $tag ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid tag ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_tag_for_response( $tag );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Prepare a tag for database insertion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_tag.
	 */
	protected function prepare_tag_for_database( $request ) {
		$prepared_tag = array();

		// Tag name.
		if ( isset( $request['name'] ) ) {
			$prepared_tag['name'] = sanitize_text_field( $request['name'] );
		}

		// Tag description.
		if ( isset( $request['description'] ) ) {
			$prepared_tag['description'] = sanitize_textarea_field( $request['description'] );
		}

		// Tag slug.
		if ( isset( $request['slug'] ) ) {
			$prepared_tag['slug'] = sanitize_title( $request['slug'] );
		}

		return $prepared_tag;
	}

	/**
	 * Prepare a tag for the response
	 *
	 * @param WP_Term $tag Tag object.
	 * @return array Prepared tag data.
	 */
	protected function prepare_tag_for_response( $tag ) {
		// Prepare response.
		$data = array(
			'id'          => $tag->term_id,
			'count'       => $tag->count,
			'description' => $tag->description,
			'link'        => get_term_link( $tag->term_id, 'post_tag' ),
			'name'        => $tag->name,
			'slug'        => $tag->slug,
		);

		return $data;
	}
}
