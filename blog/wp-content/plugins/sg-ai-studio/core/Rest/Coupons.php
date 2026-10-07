<?php
/**
 * Coupons API class for managing WooCommerce coupons via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WC_Coupon;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WooCommerce coupon operations.
 */
class Coupons extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'coupons';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Register endpoint for creating and getting coupons.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_coupon' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_coupon_args(),
					'description'         => 'Creates a new WooCommerce coupon with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_coupons' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_coupons_args(),
					'description'         => 'Retrieves a list of WooCommerce coupons based on the provided filters.',
				),
				'schema' => array( $this, 'get_coupon_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single coupon.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_coupon' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the coupon.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific WooCommerce coupon by ID.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_coupon' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_update_coupon_args(),
					'description'         => 'Updates a specific WooCommerce coupon with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_coupon' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the coupon.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific WooCommerce coupon.',
				),
				'schema' => array( $this, 'get_coupon_schema' ),
			)
		);

		// Register endpoint for bulk operations on coupons.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_operations' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_batch_args(),
					'description'         => 'Batch create, update, and delete operations in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Get arguments for creating a coupon
	 *
	 * @return array
	 */
	protected function get_create_coupon_args() {
		return array(
			'code'                        => array(
				'description' => 'Coupon code.',
				'type'        => 'string',
				'required'    => true,
			),
			'amount'                      => array(
				'description' => 'The amount of discount. Should always be numeric, even if setting a percentage.',
				'type'        => 'string',
				'required'    => false,
			),
			'discount_type'               => array(
				'description' => 'Determines the type of discount that will be applied.',
				'type'        => 'string',
				'enum'        => array( 'percent', 'fixed_cart', 'fixed_product' ),
				'default'     => 'fixed_cart',
				'required'    => false,
			),
			'description'                 => array(
				'description' => 'Coupon description.',
				'type'        => 'string',
				'required'    => false,
			),
			'date_expires'                => array(
				'description' => 'The date the coupon expires, in ISO 8601 format.',
				'type'        => 'string',
				'format'      => 'date-time',
				'required'    => false,
			),
			'individual_use'              => array(
				'description' => 'If true, the coupon can only be used individually.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'product_ids'                 => array(
				'description' => 'List of product IDs the coupon can be used on.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'excluded_product_ids'        => array(
				'description' => 'List of product IDs the coupon cannot be used on.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'usage_limit'                 => array(
				'description' => 'How many times the coupon can be used in total.',
				'type'        => 'integer',
				'required'    => false,
			),
			'usage_limit_per_user'        => array(
				'description' => 'How many times the coupon can be used per customer.',
				'type'        => 'integer',
				'required'    => false,
			),
			'limit_usage_to_x_items'      => array(
				'description' => 'Max number of items in the cart the coupon can be applied to.',
				'type'        => 'integer',
				'required'    => false,
			),
			'free_shipping'               => array(
				'description' => 'If true and if the free shipping method requires a coupon, this coupon will enable free shipping.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'product_categories'          => array(
				'description' => 'List of category IDs the coupon applies to.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'excluded_product_categories' => array(
				'description' => 'List of category IDs the coupon does not apply to.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude_sale_items'          => array(
				'description' => 'If true, this coupon will not be applied to items that have sale prices.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'minimum_amount'              => array(
				'description' => 'Minimum order amount that needs to be in the cart before coupon applies.',
				'type'        => 'string',
				'required'    => false,
			),
			'maximum_amount'              => array(
				'description' => 'Maximum order amount allowed when using the coupon.',
				'type'        => 'string',
				'required'    => false,
			),
			'email_restrictions'          => array(
				'description' => 'List of email addresses that can use this coupon.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a coupon
	 *
	 * @return array
	 */
	protected function get_update_coupon_args() {
		$args = [];
		// Add coupon ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the coupon.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving coupons
	 *
	 * @return array
	 */
	protected function get_coupons_args() {
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
			'code'     => array(
				'description' => 'Limit result set to resources with a specific code.',
				'type'        => 'string',
				'required'    => false,
			),
			'orderby'  => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'id', 'title' ),
				'required'    => false,
			),
			'order'    => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Get coupon schema
	 *
	 * @return array
	 */
	public function get_coupon_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'coupon',
			'type'       => 'object',
			'properties' => array(
				'id'                          => array(
					'description' => 'Unique identifier for the coupon.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'code'                        => array(
					'description' => 'Coupon code.',
					'type'        => 'string',
				),
				'amount'                      => array(
					'description' => 'The amount of discount.',
					'type'        => 'string',
				),
				'date_created'                => array(
					'description' => 'The date the coupon was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_created_gmt'            => array(
					'description' => 'The date the coupon was created, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_modified'               => array(
					'description' => 'The date the coupon was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_modified_gmt'           => array(
					'description' => 'The date the coupon was last modified, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'discount_type'               => array(
					'description' => 'Determines the type of discount that will be applied.',
					'type'        => 'string',
					'enum'        => array( 'percent', 'fixed_cart', 'fixed_product' ),
				),
				'description'                 => array(
					'description' => 'Coupon description.',
					'type'        => 'string',
				),
				'date_expires'                => array(
					'description' => 'The date the coupon expires.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_expires_gmt'            => array(
					'description' => 'The date the coupon expires, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'usage_count'                 => array(
					'description' => 'Number of times the coupon has been used already.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'individual_use'              => array(
					'description' => 'If true, the coupon can only be used individually.',
					'type'        => 'boolean',
				),
				'product_ids'                 => array(
					'description' => 'List of product IDs the coupon can be used on.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'excluded_product_ids'        => array(
					'description' => 'List of product IDs the coupon cannot be used on.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'usage_limit'                 => array(
					'description' => 'How many times the coupon can be used in total.',
					'type'        => 'integer',
				),
				'usage_limit_per_user'        => array(
					'description' => 'How many times the coupon can be used per customer.',
					'type'        => 'integer',
				),
				'limit_usage_to_x_items'      => array(
					'description' => 'Max number of items in the cart the coupon can be applied to.',
					'type'        => 'integer',
				),
				'free_shipping'               => array(
					'description' => 'If true and if the free shipping method requires a coupon, this coupon will enable free shipping.',
					'type'        => 'boolean',
				),
				'product_categories'          => array(
					'description' => 'List of category IDs the coupon applies to.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'excluded_product_categories' => array(
					'description' => 'List of category IDs the coupon does not apply to.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'exclude_sale_items'          => array(
					'description' => 'If true, this coupon will not be applied to items that have sale prices.',
					'type'        => 'boolean',
				),
				'minimum_amount'              => array(
					'description' => 'Minimum order amount that needs to be in the cart before coupon applies.',
					'type'        => 'string',
				),
				'maximum_amount'              => array(
					'description' => 'Maximum order amount allowed when using the coupon.',
					'type'        => 'string',
				),
				'email_restrictions'          => array(
					'description' => 'List of email addresses that can use this coupon.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
				),
			),
		);
	}

	/**
	 * Create a new coupon
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_coupon( $request ) {
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

		$coupon = new \WC_Coupon();
		$this->update_coupon_data( $coupon, $request );

		$coupon_id = $coupon->save();

		if ( is_wp_error( $coupon_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $coupon_id->get_error_message(),
				),
				400
			);
		}

		// Log the activity.
		// translators: %1$s is the coupon code, %2$d is the coupon ID.
		Activity_Log_Helper::add_log_entry( 'Coupons', sprintf( __( 'Coupon Created: %1$s (Coupon ID: %2$d)', 'sg-ai-studio' ), $coupon->get_code(), $coupon->get_id() ) );

		$response = $this->prepare_coupon_for_response( $coupon );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
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
	 * Update an existing coupon
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_coupon( $request ) {
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

		$coupon_id = $request['id'];
		$coupon    = new \WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid coupon ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$this->update_coupon_data( $coupon, $request );
		$coupon->save();

		// Log the activity.
		// translators: %1$s is the coupon code, %2$d is the coupon ID.
		Activity_Log_Helper::add_log_entry( 'Coupons', sprintf( __( 'Coupon Updated: %1$s (Coupon ID: %2$d)', 'sg-ai-studio' ), $coupon->get_code(), $coupon->get_id() ) );

		$response = $this->prepare_coupon_for_response( $coupon );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
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
	 * Delete a coupon
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_coupon( $request ) {
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

		$coupon_id = $request['id'];
		$coupon    = new \WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid coupon ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$previous = $this->prepare_coupon_for_response( $coupon );

		$result = $coupon->delete( true );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The coupon could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		$coupon_code = $previous['code'];
		// translators: %1$s is the coupon code, %2$d is the coupon ID.
		Activity_Log_Helper::add_log_entry( 'Coupons', sprintf( __( 'Coupon Deleted: %1$s (Coupon ID: %2$d)', 'sg-ai-studio' ), $coupon_code, $coupon_id ) );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'The coupon has been permanently deleted.', 'sg-ai-studio' ),
				'data'    => $previous,
			),
			200
		);
	}

	/**
	 * Get a list of coupons
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_coupons( $request ) {
		$args = array(
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
			'orderby'        => $request['orderby'],
			'order'          => $request['order'],
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
		);

		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['s'] = $request['search'];
		}

		if ( isset( $request['code'] ) && ! empty( $request['code'] ) ) {
			$args['s'] = $request['code'];
		}

		$query   = new \WP_Query( $args );
		$coupons = $query->posts;

		$data = array();
		foreach ( $coupons as $coupon_post ) {
			$coupon = new \WC_Coupon( $coupon_post->ID );
			$data[] = $this->prepare_coupon_for_response( $coupon );
		}

		$total_coupons = $query->found_posts;
		$max_pages     = $query->max_num_pages;

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'coupons'     => $data,
					'total'       => $total_coupons,
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
	 * Get a single coupon
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_coupon( $request ) {
		$coupon_id = $request['id'];
		$coupon    = new \WC_Coupon( $coupon_id );

		if ( ! $coupon->get_id() ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid coupon ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$response = $this->prepare_coupon_for_response( $coupon );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Update coupon data from request
	 *
	 * @param \WC_Coupon      $coupon The coupon object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return void
	 */
	protected function update_coupon_data( $coupon, $request ) {
		$fields = array(
			'code'                        => 'code',
			'amount'                      => 'amount',
			'discount_type'               => 'discount_type',
			'description'                 => 'description',
			'date_expires'                => 'date_expires',
			'individual_use'              => 'individual_use',
			'product_ids'                 => 'product_ids',
			'excluded_product_ids'        => 'excluded_product_ids',
			'usage_limit'                 => 'usage_limit',
			'usage_limit_per_user'        => 'usage_limit_per_user',
			'limit_usage_to_x_items'      => 'limit_usage_to_x_items',
			'free_shipping'               => 'free_shipping',
			'product_categories'          => 'product_categories',
			'excluded_product_categories' => 'excluded_product_categories',
			'exclude_sale_items'          => 'exclude_sale_items',
			'minimum_amount'              => 'minimum_amount',
			'maximum_amount'              => 'maximum_amount',
			'email_restrictions'          => 'email_restrictions',
		);

		foreach ( $fields as $field => $setter ) {
			if ( isset( $request[ $field ] ) ) {
				$method = 'set_' . $setter;
				if ( method_exists( $coupon, $method ) ) {
					$coupon->$method( $request[ $field ] );
				}
			}
		}
	}

	/**
	 * Get arguments for batch operations
	 *
	 * @return array
	 */
	protected function get_batch_args() {
		return array(
			'create' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'object' ),
				'description' => 'Array of coupons to create.',
				'required'    => false,
			),
			'update' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'object' ),
				'description' => 'Array of coupons to update.',
				'required'    => false,
			),
			'delete' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
				'description' => 'Array of coupon IDs to delete.',
				'required'    => false,
			),
			'force' => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => 'Whether to permanently delete coupons.',
				'required'    => false,
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
	 * Process batch create operations
	 *
	 * @param array $items Items to create.
	 * @return array Array with 'results' and 'errors' keys.
	 */
	protected function process_batch_creates( $items ) {
		if ( empty( $items ) ) {
			return array(
				'results' => array(),
				'errors'  => array(),
			);
		}

		$results = array();
		$errors  = array();

		foreach ( $items as $key => $coupon_data ) {
			// Create a new request for each coupon.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add coupon data to the request.
			foreach ( $coupon_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the coupon.
			$response = $this->create_coupon( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		return array(
			'results' => $results,
			'errors'  => $errors,
		);
	}

	/**
	 * Process batch update operations
	 *
	 * @param array $items Items to update.
	 * @return array Array with 'results' and 'errors' keys.
	 */
	protected function process_batch_updates( $items ) {
		if ( empty( $items ) ) {
			return array(
				'results' => array(),
				'errors'  => array(),
			);
		}

		$results = array();
		$errors  = array();

		foreach ( $items as $key => $coupon_data ) {
			if ( ! isset( $coupon_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Coupon ID is required for updating coupons.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each coupon.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base . '/' . $coupon_data['id'] );

			// Add coupon data to the request.
			foreach ( $coupon_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the coupon.
			$response = $this->update_coupon( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		return array(
			'results' => $results,
			'errors'  => $errors,
		);
	}

	/**
	 * Process batch delete operations
	 *
	 * @param array $ids Coupon IDs to delete.
	 * @param bool  $force Whether to permanently delete.
	 * @return array Array with 'results' and 'errors' keys.
	 */
	protected function process_batch_deletes( $ids, $force = false ) {
		if ( empty( $ids ) ) {
			return array(
				'results' => array(),
				'errors'  => array(),
			);
		}

		$results = array();
		$errors  = array();

		foreach ( $ids as $coupon_id ) {
			// Create a new request for each coupon.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $coupon_id );
			$sub_request->set_param( 'id', $coupon_id );
			$sub_request->set_param( 'force', $force );

			// Delete the coupon.
			$response = $this->delete_coupon( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $coupon_id ] = $response->get_data();
			} else {
				$results[ $coupon_id ] = array(
					'id'      => $coupon_id,
					'message' => $response->get_data()['message'],
				);
			}
		}

		return array(
			'results' => $results,
			'errors'  => $errors,
		);
	}

	/**
	 * Batch operations (create, update, delete)
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_operations( $request ) {
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

		// OBSERVABILITY: Log request for verification.
		error_log( 'Batch request - Method: ' . $request->get_method() );
		error_log( 'Batch request - Body: ' . wp_json_encode( $request->get_json_params() ) );

		$body = $request->get_json_params();

		$create_items = isset( $body['create'] ) ? $body['create'] : array();
		$update_items = isset( $body['update'] ) ? $body['update'] : array();
		$delete_ids   = isset( $body['delete'] ) ? $body['delete'] : array();
		$force        = isset( $body['force'] ) ? $body['force'] : false;

		// Process operations.
		$create_result = $this->process_batch_creates( $create_items );
		$update_result = $this->process_batch_updates( $update_items );
		$delete_result = $this->process_batch_deletes( $delete_ids, $force );

		// Check if all operations succeeded.
		$all_errors = array_merge(
			$create_result['errors'],
			$update_result['errors'],
			$delete_result['errors']
		);
		$success = empty( $all_errors );

		// Clear all caches.
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
			\wp_cache_flush();
		} else {
			\wp_cache_flush();
		}

		// Determine HTTP status.
		$has_creates = ! empty( $create_result['results'] );
		$http_status = $success ? ( $has_creates ? 201 : 200 ) : 207;

		return new WP_REST_Response(
			array(
				'success' => $success,
				'data'    => array(
					'create' => array_values( $create_result['results'] ),
					'update' => array_values( $update_result['results'] ),
					'delete' => array_values( $delete_result['results'] ),
					'errors' => array(
						'create' => $create_result['errors'],
						'update' => $update_result['errors'],
						'delete' => $delete_result['errors'],
					),
				),
			),
			$http_status
		);
	}

	/**
	 * Prepare a coupon for the response
	 *
	 * @param \WC_Coupon $coupon Coupon object.
	 * @return array Prepared coupon data.
	 */
	protected function prepare_coupon_for_response( $coupon ) {
		$data = array(
			'id'                          => $coupon->get_id(),
			'code'                        => $coupon->get_code(),
			'amount'                      => $coupon->get_amount(),
			'date_created'                => $coupon->get_date_created() ? $coupon->get_date_created()->format( 'c' ) : null,
			'date_created_gmt'            => $coupon->get_date_created() ? $coupon->get_date_created()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'date_modified'               => $coupon->get_date_modified() ? $coupon->get_date_modified()->format( 'c' ) : null,
			'date_modified_gmt'           => $coupon->get_date_modified() ? $coupon->get_date_modified()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'discount_type'               => $coupon->get_discount_type(),
			'description'                 => $coupon->get_description(),
			'date_expires'                => $coupon->get_date_expires() ? $coupon->get_date_expires()->format( 'c' ) : null,
			'date_expires_gmt'            => $coupon->get_date_expires() ? $coupon->get_date_expires()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'usage_count'                 => $coupon->get_usage_count(),
			'individual_use'              => $coupon->get_individual_use(),
			'product_ids'                 => $coupon->get_product_ids(),
			'excluded_product_ids'        => $coupon->get_excluded_product_ids(),
			'usage_limit'                 => $coupon->get_usage_limit(),
			'usage_limit_per_user'        => $coupon->get_usage_limit_per_user(),
			'limit_usage_to_x_items'      => $coupon->get_limit_usage_to_x_items(),
			'free_shipping'               => $coupon->get_free_shipping(),
			'product_categories'          => $coupon->get_product_categories(),
			'excluded_product_categories' => $coupon->get_excluded_product_categories(),
			'exclude_sale_items'          => $coupon->get_exclude_sale_items(),
			'minimum_amount'              => $coupon->get_minimum_amount(),
			'maximum_amount'              => $coupon->get_maximum_amount(),
			'email_restrictions'          => $coupon->get_email_restrictions(),
		);

		return $data;
	}
}
