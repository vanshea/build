<?php
/**
 * Categories API class for managing categories via REST API
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
 * Handles REST API endpoints for category operations.
 */
class Categories extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'categories';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new category and listing categories.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_category' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_category_args(),
					'description'         => 'Creates a new category with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_categories_args(),
					'description'         => 'Retrieves a list of categories based on the provided filters.',
				),
				'schema' => array( $this, 'get_category_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single category.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_category' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the category.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific category by ID.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_category' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_update_category_args(),
					'description'         => 'Updates a specific category with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_category' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the category.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific category.',
				),
				'schema' => array( $this, 'get_category_schema' ),
			)
		);
	}

	/**
	 * Get arguments for creating a category
	 *
	 * @return array
	 */
	protected function get_create_category_args() {
		return array(
			'name'        => array(
				'description' => 'The name for the category.',
				'type'        => 'string',
				'required'    => true,
			),
			'description' => array(
				'description' => 'The description for the category.',
				'type'        => 'string',
				'required'    => false,
			),
			'slug'        => array(
				'description' => 'The slug for the category.',
				'type'        => 'string',
				'required'    => false,
			),
			'parent'      => array(
				'description' => 'The parent category ID.',
				'type'        => 'integer',
				'required'    => false,
				'default'     => 0,
			),
		);
	}

	/**
	 * Get arguments for updating a category
	 *
	 * @return array
	 */
	protected function get_update_category_args() {
		$args = [];
		// Add category ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the category.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving categories
	 *
	 * @return array
	 */
	protected function get_categories_args() {
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
			'parent'     => array(
				'description' => 'Limit result set to categories assigned to a specific parent.',
				'type'        => 'integer',
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
				'description' => 'Whether to hide categories not assigned to any posts.',
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
	 * Get category schema
	 *
	 * @return array
	 */
	public function get_category_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'category',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => 'Unique identifier for the category.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'count'       => array(
					'description' => 'Number of posts in the category.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'description' => array(
					'description' => 'The description of the category.',
					'type'        => 'string',
				),
				'link'        => array(
					'description' => 'The URL to the category archive.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'name'        => array(
					'description' => 'The name of the category.',
					'type'        => 'string',
				),
				'slug'        => array(
					'description' => 'The slug of the category.',
					'type'        => 'string',
				),
				'parent'      => array(
					'description' => 'The parent category ID.',
					'type'        => 'integer',
				),
			),
		);
	}

	/**
	 * Create a new category
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_category( $request ) {
		// Prepare category data.
		$category_data = $this->prepare_category_for_database( $request );

		// Validate parent category if specified.
		if ( isset( $category_data['parent'] ) && $category_data['parent'] > 0 ) {
			// For new categories, we use 0 as the category_id since it doesn't exist yet.
			// We only need to check if the parent exists and is valid.
			$parent_term = get_term( $category_data['parent'], 'category' );
			if ( is_wp_error( $parent_term ) || ! $parent_term ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Invalid parent category ID.', 'sg-ai-studio' ),
					),
					400
				);
			}
		}

		// Create the category.
		$result = wp_insert_term( $category_data['name'], 'category', $category_data );

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

		// Get the category.
		$category = get_term( $term_id, 'category' );

		// Format the response.
		$response = $this->prepare_category_for_response( $category );

		// Log the activity.
		/* translators: %1$s is the category name, %2$d is the category ID. */
		Activity_Log_Helper::add_log_entry( 'Categories', sprintf( __( 'Category Created: %1$s (ID: %2$d)', 'sg-ai-studio' ), $category->name, $term_id ) );

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
	 * Update an existing category
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_category( $request ) {
		$term_id  = $request['id'];
		$category = get_term( $term_id, 'category' );

		if ( is_wp_error( $category ) || ! $category ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid category ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare category data.
		$category_data = $this->prepare_category_for_database( $request );

		// Validate parent category if specified.
		if ( isset( $category_data['parent'] ) && $category_data['parent'] > 0 ) {
			$validation_result = $this->validate_parent_category( $term_id, $category_data['parent'] );
			if ( true !== $validation_result ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $validation_result,
					),
					400
				);
			}
		}

		// Update the category.
		$result = wp_update_term( $term_id, 'category', $category_data );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		// Get the updated category.
		$category = get_term( $term_id, 'category' );

		// Format the response.
		$response = $this->prepare_category_for_response( $category );

		// Log the activity.
		/* translators: %1$s is the category name, %2$d is the category ID. */
		Activity_Log_Helper::add_log_entry( 'Categories', sprintf( __( 'Category Updated: %1$s (ID: %2$d)', 'sg-ai-studio' ), $category->name, $term_id ) );

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
	 * Delete a category
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_category( $request ) {
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

		$term_id  = $request['id'];
		$category = get_term( $term_id, 'category' );

		if ( is_wp_error( $category ) || ! $category ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid category ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Check if this is the default category.
		$default_category = (int) get_option( 'default_category' );
		if ( $term_id === $default_category ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The default category cannot be deleted. Please set a different default category first.', 'sg-ai-studio' ),
				),
				403
			);
		}

		// Get the category before deleting it.
		$previous = $this->prepare_category_for_response( $category );

		// Delete the category.
		$result = wp_delete_term( $term_id, 'category' );

		if ( is_wp_error( $result ) || ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The category could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		/* translators: %1$s is the category name, %2$d is the category ID. */
		Activity_Log_Helper::add_log_entry( 'Categories', sprintf( __( 'Category Deleted: %1$s (ID: %2$d)', 'sg-ai-studio' ), $previous['name'], $term_id ) );

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
				'message' => __( 'The category has been permanently deleted.', 'sg-ai-studio' ),
				'data'    => $previous,
			),
			200
		);
	}

	/**
	 * Get a list of categories
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_categories( $request ) {
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
			'taxonomy'   => 'category',
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

		// Handle parent parameter.
		if ( isset( $request['parent'] ) ) {
			$args['parent'] = $request['parent'];
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

		// Get categories.
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
			$data[] = $this->prepare_category_for_response( $term );
		}

		// Get total count for pagination.
		$total_args         = $args;
		$total_args['number'] = 0;
		$total_args['offset'] = 0;
		$total_categories   = get_terms( $total_args );
		$total              = is_array( $total_categories ) ? count( $total_categories ) : 0;
		$total_pages        = ceil( $total / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'categories'  => $data,
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
	 * Get a single category
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_category( $request ) {
		$term_id  = $request['id'];
		$category = get_term( $term_id, 'category' );

		if ( is_wp_error( $category ) || ! $category ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid category ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_category_for_response( $category );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Validate parent category to prevent circular references
	 *
	 * @param int $category_id The category ID being created/updated.
	 * @param int $parent_id The proposed parent ID.
	 * @return bool|string True if valid, error message if invalid.
	 */
	protected function validate_parent_category( $category_id, $parent_id ) {
		// Parent cannot be the category itself (self-parenting).
		if ( $category_id === $parent_id ) {
			return __( 'A category cannot be its own parent.', 'sg-ai-studio' );
		}

		// Check for circular parent relationship.
		$current_parent_id = $parent_id;
		$visited           = array();
		$max_depth         = 100; // Prevent infinite loops.
		$depth             = 0;

		while ( $current_parent_id > 0 && $depth < $max_depth ) {
			// If we've seen this parent before, it's a circular reference.
			if ( in_array( $current_parent_id, $visited, true ) ) {
				return __( 'Circular parent relationship detected. A category cannot be a descendant of itself.', 'sg-ai-studio' );
			}

			// If the parent chain leads back to the category being updated, it's circular.
			if ( $current_parent_id === $category_id ) {
				return __( 'Circular parent relationship detected. A category cannot be a descendant of itself.', 'sg-ai-studio' );
			}

			$visited[] = $current_parent_id;

			// Get the next parent in the chain.
			$parent_term = get_term( $current_parent_id, 'category' );
			if ( is_wp_error( $parent_term ) || ! $parent_term ) {
				return __( 'Invalid parent category ID.', 'sg-ai-studio' );
			}

			$current_parent_id = $parent_term->parent;
			$depth++;
		}

		return true;
	}

	/**
	 * Prepare a category for database insertion
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_category.
	 */
	protected function prepare_category_for_database( $request ) {
		$prepared_category = array();

		// Category name.
		if ( isset( $request['name'] ) ) {
			$prepared_category['name'] = sanitize_text_field( $request['name'] );
		}

		// Category description.
		if ( isset( $request['description'] ) ) {
			$prepared_category['description'] = sanitize_textarea_field( $request['description'] );
		}

		// Category slug.
		if ( isset( $request['slug'] ) ) {
			$prepared_category['slug'] = sanitize_title( $request['slug'] );
		}

		// Parent category.
		if ( isset( $request['parent'] ) ) {
			$prepared_category['parent'] = absint( $request['parent'] );
		}

		return $prepared_category;
	}

	/**
	 * Prepare a category for the response
	 *
	 * @param WP_Term $category Category object.
	 * @return array Prepared category data.
	 */
	protected function prepare_category_for_response( $category ) {
		// Prepare response.
		$data = array(
			'id'          => $category->term_id,
			'count'       => $category->count,
			'description' => $category->description,
			'link'        => get_term_link( $category->term_id, 'category' ),
			'name'        => $category->name,
			'slug'        => $category->slug,
			'parent'      => $category->parent,
		);

		return $data;
	}
}
