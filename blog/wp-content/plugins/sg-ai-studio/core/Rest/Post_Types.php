<?php
/**
 * Post Types API class for retrieving post type information via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;

/**
 * Handles REST API endpoints for post type operations.
 */
class Post_Types extends Rest_Controller_Base {
	use Taxonomy_Support;

	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'types';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for listing all post types.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_post_types' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_post_type_args(),
					'description'         => 'Retrieves a list of registered post types.',
				),
				'schema' => array( $this, 'get_post_type_schema' ),
			)
		);

		// Register endpoint for retrieving a single post type.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<type>[\w-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_post_type' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'type' => array(
							'description' => 'Post type slug (e.g., post, page).',
							'type'        => 'string',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific post type by slug.',
				),
				'schema' => array( $this, 'get_post_type_schema' ),
			)
		);
	}

	/**
	 * Get arguments for retrieving post types
	 *
	 * @return array
	 */
	protected function get_post_type_args() {
		return array(
			'context' => array(
				'description' => 'Scope under which the request is made; determines fields present in response.',
				'type'        => 'string',
				'enum'        => array( 'view', 'edit', 'embed' ),
				'default'     => 'view',
				'required'    => false,
			),
		);
	}

	/**
	 * Get post type schema
	 *
	 * @return array
	 */
	public function get_post_type_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'post-type',
			'type'       => 'object',
			'properties' => array(
				'slug'             => array(
					'description' => 'Post type slug.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'name'             => array(
					'description' => 'Post type name.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'description'      => array(
					'description' => 'Post type description.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'hierarchical'     => array(
					'description' => 'Whether the post type is hierarchical.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'rest_base'        => array(
					'description' => 'REST API base route for the post type.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'capabilities'     => array(
					'description' => 'Capabilities for the post type.',
					'type'        => 'object',
					'readonly'    => true,
				),
				'labels'           => array(
					'description' => 'Labels for the post type.',
					'type'        => 'object',
					'readonly'    => true,
				),
				'supports'         => array(
					'description' => 'Features the post type supports.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'readonly'    => true,
				),
				'taxonomies'       => array(
					'description' => 'Taxonomy slugs associated with the post type. A slug on its own is not enough to build a terms route, use taxonomy_details for that.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
					'readonly'    => true,
				),
				'taxonomy_details' => array(
					'description' => 'The taxonomies of this post type that are visible over the REST API, each with the endpoint its terms can be read from. A taxonomy listed in taxonomies but missing here is registered without show_in_rest, so its terms cannot be reached.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'slug'           => array(
								'description' => 'Taxonomy slug.',
								'type'        => 'string',
							),
							'name'           => array(
								'description' => 'Human readable taxonomy label.',
								'type'        => 'string',
							),
							'hierarchical'   => array(
								'description' => 'Whether the taxonomy supports parent and child terms.',
								'type'        => 'boolean',
							),
							'rest_base'      => array(
								'description' => 'The REST base of the taxonomy, which frequently differs from its slug.',
								'type'        => 'string',
							),
							'terms_endpoint' => array(
								'description' => 'Endpoint the terms of this taxonomy are read from and created on.',
								'type'        => 'string',
							),
						),
					),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get a list of post types
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_post_types( $request ) {
		// Get all post types that show in REST.
		$post_types = get_post_types(
			array(
				'show_in_rest' => true,
			),
			'objects'
		);

		// Format the response.
		$data = array();
		foreach ( $post_types as $post_type_object ) {
			$data[] = $this->prepare_post_type_for_response( $post_type_object );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get a single post type
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_post_type( $request ) {
		$type_slug = $request['type'];

		// Get the post type object.
		$post_type_object = get_post_type_object( $type_slug );

		// Check if post type exists.
		if ( ! $post_type_object ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post type not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Check if post type is accessible via REST.
		if ( empty( $post_type_object->show_in_rest ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post type not found.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$data = $this->prepare_post_type_for_response( $post_type_object );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Prepare a post type object for the response
	 *
	 * @param object $post_type_object Post type object.
	 * @return array Prepared post type data.
	 */
	protected function prepare_post_type_for_response( $post_type_object ) {
		// Get taxonomies for this post type.
		$taxonomies = get_object_taxonomies( $post_type_object->name, 'names' );

		// Get supports array.
		$all_supports = get_all_post_type_supports( $post_type_object->name );
		$supports     = array_keys( $all_supports );

		// Prepare the response data.
		$data = array(
			'slug'             => $post_type_object->name,
			'name'             => $post_type_object->label,
			'description'      => $post_type_object->description,
			'hierarchical'     => (bool) $post_type_object->hierarchical,
			'rest_base'        => ! empty( $post_type_object->rest_base ) ? $post_type_object->rest_base : $post_type_object->name,
			'capabilities'     => (object) $post_type_object->cap,
			'labels'           => (object) $post_type_object->labels,
			'supports'         => $supports,
			'taxonomies'       => array_values( $taxonomies ),
			'taxonomy_details' => $this->prepare_taxonomy_details( $post_type_object->name ),
		);

		return $data;
	}

	/**
	 * Describe the REST visible taxonomies of a post type
	 *
	 * The bare slugs in `taxonomies` are not enough to reach a taxonomy's terms,
	 * because `rest_base` routinely differs from the slug. Carrying the route
	 * alongside the slug saves a second discovery round trip.
	 *
	 * A taxonomy registered without `show_in_rest` is absent here while still
	 * being listed in `taxonomies`, which is the only honest thing to report:
	 * its terms genuinely are unreachable over REST.
	 *
	 * @param string $post_type Post type slug.
	 * @return array List of taxonomy descriptors.
	 */
	protected function prepare_taxonomy_details( $post_type ) {
		$details = array();

		foreach ( $this->get_rest_visible_taxonomies( $post_type ) as $taxonomy ) {
			$details[] = array(
				'slug'           => $taxonomy->name,
				'name'           => $taxonomy->label,
				'hierarchical'   => (bool) $taxonomy->hierarchical,
				'rest_base'      => $this->get_taxonomy_rest_base( $taxonomy ),
				'terms_endpoint' => '/' . $this->namespace . '/taxonomies/' . $taxonomy->name . '/terms',
			);
		}

		return $details;
	}
}
