<?php
/**
 * Orders API class for managing WooCommerce orders via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WC_Order;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WooCommerce order operations.
 */
class Orders extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'orders';

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

		// Register endpoint for creating and getting orders.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_order' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_order_args(),
					'description'         => 'Creates a new WooCommerce order with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_orders' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_orders_args(),
					'description'         => 'Retrieves a list of WooCommerce orders based on the provided filters.',
				),
				'schema' => array( $this, 'get_order_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single order.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_order' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the order.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific WooCommerce order by ID.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_order' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_update_order_args(),
					'description'         => 'Updates a specific WooCommerce order with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_order' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the order.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific WooCommerce order. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_order_schema' ),
			)
		);

		// Register endpoint for bulk operations on orders.
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
	 * Get arguments for creating an order
	 *
	 * @return array
	 */
	protected function get_create_order_args() {
		return array(
			'parent_id'            => array(
				'description' => 'Parent order ID.',
				'type'        => 'integer',
				'required'    => false,
			),
			'status'               => array(
				'description' => 'Order status.',
				'type'        => 'string',
				'enum'        => array_keys( \wc_get_order_statuses() ),
				'default'     => 'wc-pending',
				'required'    => false,
			),
			'currency'             => array(
				'description' => 'Currency the order was created with, in ISO format.',
				'type'        => 'string',
				'required'    => false,
			),
			'customer_id'          => array(
				'description' => 'User ID who owns the order. 0 for guests.',
				'type'        => 'integer',
				'default'     => 0,
				'required'    => false,
			),
			'customer_note'        => array(
				'description' => 'Note left by customer during checkout.',
				'type'        => 'string',
				'required'    => false,
			),
			'billing'              => array(
				'description' => 'Billing address.',
				'type'        => 'object',
				'properties'  => array(
					'first_name' => array(
						'description' => 'First name.',
						'type'        => 'string',
					),
					'last_name'  => array(
						'description' => 'Last name.',
						'type'        => 'string',
					),
					'company'    => array(
						'description' => 'Company name.',
						'type'        => 'string',
					),
					'address_1'  => array(
						'description' => 'Address line 1.',
						'type'        => 'string',
					),
					'address_2'  => array(
						'description' => 'Address line 2.',
						'type'        => 'string',
					),
					'city'       => array(
						'description' => 'City name.',
						'type'        => 'string',
					),
					'state'      => array(
						'description' => 'ISO code or name of the state, province or district.',
						'type'        => 'string',
					),
					'postcode'   => array(
						'description' => 'Postal code.',
						'type'        => 'string',
					),
					'country'    => array(
						'description' => 'Country code in ISO 3166-1 alpha-2 format.',
						'type'        => 'string',
					),
					'email'      => array(
						'description' => 'Email address.',
						'type'        => 'string',
						'format'      => 'email',
					),
					'phone'      => array(
						'description' => 'Phone number.',
						'type'        => 'string',
					),
				),
				'required'    => false,
			),
			'shipping'             => array(
				'description' => 'Shipping address.',
				'type'        => 'object',
				'properties'  => array(
					'first_name' => array(
						'description' => 'First name.',
						'type'        => 'string',
					),
					'last_name'  => array(
						'description' => 'Last name.',
						'type'        => 'string',
					),
					'company'    => array(
						'description' => 'Company name.',
						'type'        => 'string',
					),
					'address_1'  => array(
						'description' => 'Address line 1.',
						'type'        => 'string',
					),
					'address_2'  => array(
						'description' => 'Address line 2.',
						'type'        => 'string',
					),
					'city'       => array(
						'description' => 'City name.',
						'type'        => 'string',
					),
					'state'      => array(
						'description' => 'ISO code or name of the state, province or district.',
						'type'        => 'string',
					),
					'postcode'   => array(
						'description' => 'Postal code.',
						'type'        => 'string',
					),
					'country'    => array(
						'description' => 'Country code in ISO 3166-1 alpha-2 format.',
						'type'        => 'string',
					),
				),
				'required'    => false,
			),
			'payment_method'       => array(
				'description' => 'Payment method ID.',
				'type'        => 'string',
				'required'    => false,
			),
			'payment_method_title' => array(
				'description' => 'Payment method title.',
				'type'        => 'string',
				'required'    => false,
			),
			'transaction_id'       => array(
				'description' => 'Unique transaction ID.',
				'type'        => 'string',
				'required'    => false,
			),
			'meta_data'            => array(
				'description' => 'Meta data.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'key'   => array(
							'description' => 'Meta key.',
							'type'        => 'string',
						),
						'value' => array(
							'description' => 'Meta value.',
							'type'        => 'string',
						),
					),
				),
				'required'    => false,
			),
			'line_items'           => array(
				'description' => 'Line items data.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'product_id'   => array(
							'description' => 'Product ID.',
							'type'        => 'integer',
						),
						'variation_id' => array(
							'description' => 'Variation ID, if applicable.',
							'type'        => 'integer',
						),
						'quantity'     => array(
							'description' => 'Quantity ordered.',
							'type'        => 'integer',
						),
					),
				),
				'required'    => false,
			),
			'shipping_lines'       => array(
				'description' => 'Shipping lines data.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'method_id'    => array(
							'description' => 'Shipping method ID.',
							'type'        => 'string',
						),
						'method_title' => array(
							'description' => 'Shipping method name.',
							'type'        => 'string',
						),
						'total'        => array(
							'description' => 'Line total (after discounts).',
							'type'        => 'string',
						),
					),
				),
				'required'    => false,
			),
			'fee_lines'            => array(
				'description' => 'Fee lines data.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'name'       => array(
							'description' => 'Fee name.',
							'type'        => 'string',
						),
						'tax_class'  => array(
							'description' => 'Tax class of fee.',
							'type'        => 'string',
						),
						'tax_status' => array(
							'description' => 'Tax status of fee.',
							'type'        => 'string',
							'enum'        => array( 'taxable', 'none' ),
						),
						'total'      => array(
							'description' => 'Line total (after discounts).',
							'type'        => 'string',
						),
					),
				),
				'required'    => false,
			),
			'coupon_lines'         => array(
				'description' => 'Coupons line data.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'code' => array(
							'description' => 'Coupon code.',
							'type'        => 'string',
						),
					),
				),
				'required'    => false,
			),
			'set_paid'             => array(
				'description' => 'Define if the order is paid. It will set the status to processing and reduce stock items.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating an order
	 *
	 * @return array
	 */
	protected function get_update_order_args() {
		$args = [];

		// Add order ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the order.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving orders
	 *
	 * @return array
	 */
	protected function get_orders_args() {
		return array(
			'page'           => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'required'          => false,
			),
			'per_page'       => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'required'          => false,
			),
			'search'         => array(
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => false,
			),
			'after'          => array(
				'description' => 'Limit response to orders published after a given ISO8601 compliant date.',
				'type'        => 'string',
				'format'      => 'date-time',
				'required'    => false,
			),
			'before'         => array(
				'description' => 'Limit response to orders published before a given ISO8601 compliant date.',
				'type'        => 'string',
				'format'      => 'date-time',
				'required'    => false,
			),
			'exclude'        => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'description' => 'Ensure result set excludes specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'include'        => array(
				'description' => 'Limit result set to specific ids.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'offset'         => array(
				'description' => 'Offset the result set by a specific number of items.',
				'type'        => 'integer',
				'required'    => false,
			),
			'order'          => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'orderby'        => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'id', 'include', 'title', 'slug' ),
				'required'    => false,
			),
			'parent'         => array(
				'description' => 'Limit result set to those of particular parent IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'parent_exclude' => array(
				'description' => 'Limit result set to all items except those of a particular parent ID.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'status'         => array(
				'description' => 'Limit result set to orders with specific statuses.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
					'enum' => array_keys( \wc_get_order_statuses() ),
				),
				'required'    => false,
			),
			'customer'       => array(
				'description' => 'Limit result set to orders assigned a specific customer.',
				'type'        => 'integer',
				'required'    => false,
			),
			'product'        => array(
				'description' => 'Limit result set to orders assigned a specific product.',
				'type'        => 'integer',
				'required'    => false,
			),
			'dp'             => array(
				'description' => 'Number of decimal points to use in each resource.',
				'type'        => 'integer',
				'default'     => 2,
				'required'    => false,
			),
		);
	}

	/**
	 * Get order schema
	 *
	 * @return array
	 */
	public function get_order_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'order',
			'type'       => 'object',
			'properties' => array(
				'id'                   => array(
					'description' => 'Unique identifier for the order.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'parent_id'            => array(
					'description' => 'Parent order ID.',
					'type'        => 'integer',
				),
				'number'               => array(
					'description' => 'Order number.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'order_key'            => array(
					'description' => 'Order key.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'created_via'          => array(
					'description' => 'Shows where the order was created.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'version'              => array(
					'description' => 'Version of WooCommerce which last updated the order.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'status'               => array(
					'description' => 'Order status.',
					'type'        => 'string',
					'enum'        => array_keys( \wc_get_order_statuses() ),
				),
				'currency'             => array(
					'description' => 'Currency the order was created with, in ISO format.',
					'type'        => 'string',
				),
				'date_created'         => array(
					'description' => 'The date the order was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_created_gmt'     => array(
					'description' => 'The date the order was created, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_modified'        => array(
					'description' => 'The date the order was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_modified_gmt'    => array(
					'description' => 'The date the order was last modified, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'discount_total'       => array(
					'description' => 'Total discount amount for the order.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'discount_tax'         => array(
					'description' => 'Total discount tax amount for the order.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'shipping_total'       => array(
					'description' => 'Total shipping amount for the order.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'shipping_tax'         => array(
					'description' => 'Total shipping tax amount for the order.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'cart_tax'             => array(
					'description' => 'Sum of line item taxes only.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'total'                => array(
					'description' => 'Grand total.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'total_tax'            => array(
					'description' => 'Sum of all taxes.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'prices_include_tax'   => array(
					'description' => 'True the prices included tax during checkout.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'customer_id'          => array(
					'description' => 'User ID who owns the order. 0 for guests.',
					'type'        => 'integer',
				),
				'customer_ip_address'  => array(
					'description' => 'Customer\'s IP address.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'customer_user_agent'  => array(
					'description' => 'User agent of the customer.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'customer_note'        => array(
					'description' => 'Note left by customer during checkout.',
					'type'        => 'string',
				),
				'billing'              => array(
					'description' => 'Billing address.',
					'type'        => 'object',
					'properties'  => array(
						'first_name' => array(
							'description' => 'First name.',
							'type'        => 'string',
						),
						'last_name'  => array(
							'description' => 'Last name.',
							'type'        => 'string',
						),
						'company'    => array(
							'description' => 'Company name.',
							'type'        => 'string',
						),
						'address_1'  => array(
							'description' => 'Address line 1.',
							'type'        => 'string',
						),
						'address_2'  => array(
							'description' => 'Address line 2.',
							'type'        => 'string',
						),
						'city'       => array(
							'description' => 'City name.',
							'type'        => 'string',
						),
						'state'      => array(
							'description' => 'ISO code or name of the state, province or district.',
							'type'        => 'string',
						),
						'postcode'   => array(
							'description' => 'Postal code.',
							'type'        => 'string',
						),
						'country'    => array(
							'description' => 'Country code in ISO 3166-1 alpha-2 format.',
							'type'        => 'string',
						),
						'email'      => array(
							'description' => 'Email address.',
							'type'        => 'string',
							'format'      => 'email',
						),
						'phone'      => array(
							'description' => 'Phone number.',
							'type'        => 'string',
						),
					),
				),
				'shipping'             => array(
					'description' => 'Shipping address.',
					'type'        => 'object',
					'properties'  => array(
						'first_name' => array(
							'description' => 'First name.',
							'type'        => 'string',
						),
						'last_name'  => array(
							'description' => 'Last name.',
							'type'        => 'string',
						),
						'company'    => array(
							'description' => 'Company name.',
							'type'        => 'string',
						),
						'address_1'  => array(
							'description' => 'Address line 1.',
							'type'        => 'string',
						),
						'address_2'  => array(
							'description' => 'Address line 2.',
							'type'        => 'string',
						),
						'city'       => array(
							'description' => 'City name.',
							'type'        => 'string',
						),
						'state'      => array(
							'description' => 'ISO code or name of the state, province or district.',
							'type'        => 'string',
						),
						'postcode'   => array(
							'description' => 'Postal code.',
							'type'        => 'string',
						),
						'country'    => array(
							'description' => 'Country code in ISO 3166-1 alpha-2 format.',
							'type'        => 'string',
						),
					),
				),
				'payment_method'       => array(
					'description' => 'Payment method ID.',
					'type'        => 'string',
				),
				'payment_method_title' => array(
					'description' => 'Payment method title.',
					'type'        => 'string',
				),
				'transaction_id'       => array(
					'description' => 'Unique transaction ID.',
					'type'        => 'string',
				),
				'date_paid'            => array(
					'description' => 'The date the order was paid.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_paid_gmt'        => array(
					'description' => 'The date the order was paid, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_completed'       => array(
					'description' => 'The date the order was completed.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_completed_gmt'   => array(
					'description' => 'The date the order was completed, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'cart_hash'            => array(
					'description' => 'MD5 hash of cart items to ensure orders are not modified.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'meta_data'            => array(
					'description' => 'Meta data.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => 'Meta ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'key'   => array(
								'description' => 'Meta key.',
								'type'        => 'string',
							),
							'value' => array(
								'description' => 'Meta value.',
								'type'        => 'mixed',
							),
						),
					),
				),
				'line_items'           => array(
					'description' => 'Line items data.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => 'Item ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'name'         => array(
								'description' => 'Product name.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'product_id'   => array(
								'description' => 'Product ID.',
								'type'        => 'integer',
							),
							'variation_id' => array(
								'description' => 'Variation ID, if applicable.',
								'type'        => 'integer',
							),
							'quantity'     => array(
								'description' => 'Quantity ordered.',
								'type'        => 'integer',
							),
							'tax_class'    => array(
								'description' => 'Tax class of product.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'subtotal'     => array(
								'description' => 'Line subtotal (before discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'subtotal_tax' => array(
								'description' => 'Line subtotal tax (before discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'total'        => array(
								'description' => 'Line total (after discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'total_tax'    => array(
								'description' => 'Line total tax (after discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'taxes'        => array(
								'description' => 'Line taxes.',
								'type'        => 'array',
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'       => array(
											'description' => 'Tax rate ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'total'    => array(
											'description' => 'Tax total.',
											'type'        => 'string',
											'readonly'    => true,
										),
										'subtotal' => array(
											'description' => 'Tax subtotal.',
											'type'        => 'string',
											'readonly'    => true,
										),
									),
								),
							),
							'meta_data'    => array(
								'description' => 'Meta data.',
								'type'        => 'array',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Meta ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'key'   => array(
											'description' => 'Meta key.',
											'type'        => 'string',
										),
										'value' => array(
											'description' => 'Meta value.',
											'type'        => 'mixed',
										),
									),
								),
							),
							'sku'          => array(
								'description' => 'Product SKU.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'price'        => array(
								'description' => 'Product price.',
								'type'        => 'string',
								'readonly'    => true,
							),
						),
					),
				),
				'tax_lines'            => array(
					'description' => 'Tax lines data.',
					'type'        => 'array',
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                 => array(
								'description' => 'Item ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'rate_code'          => array(
								'description' => 'Tax rate code.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'rate_id'            => array(
								'description' => 'Tax rate ID.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'label'              => array(
								'description' => 'Tax rate label.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'compound'           => array(
								'description' => 'Show if is a compound tax rate.',
								'type'        => 'boolean',
								'readonly'    => true,
							),
							'tax_total'          => array(
								'description' => 'Tax total (not including shipping taxes).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'shipping_tax_total' => array(
								'description' => 'Shipping tax total.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'meta_data'          => array(
								'description' => 'Meta data.',
								'type'        => 'array',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Meta ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'key'   => array(
											'description' => 'Meta key.',
											'type'        => 'string',
										),
										'value' => array(
											'description' => 'Meta value.',
											'type'        => 'mixed',
										),
									),
								),
							),
						),
					),
				),
				'shipping_lines'       => array(
					'description' => 'Shipping lines data.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => 'Item ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'method_title' => array(
								'description' => 'Shipping method name.',
								'type'        => 'string',
							),
							'method_id'    => array(
								'description' => 'Shipping method ID.',
								'type'        => 'string',
							),
							'total'        => array(
								'description' => 'Line total (after discounts).',
								'type'        => 'string',
							),
							'total_tax'    => array(
								'description' => 'Line total tax (after discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'taxes'        => array(
								'description' => 'Line taxes.',
								'type'        => 'array',
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Tax rate ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'total' => array(
											'description' => 'Tax total.',
											'type'        => 'string',
											'readonly'    => true,
										),
									),
								),
							),
							'meta_data'    => array(
								'description' => 'Meta data.',
								'type'        => 'array',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Meta ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'key'   => array(
											'description' => 'Meta key.',
											'type'        => 'string',
										),
										'value' => array(
											'description' => 'Meta value.',
											'type'        => 'mixed',
										),
									),
								),
							),
						),
					),
				),
				'fee_lines'            => array(
					'description' => 'Fee lines data.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'         => array(
								'description' => 'Item ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'name'       => array(
								'description' => 'Fee name.',
								'type'        => 'string',
							),
							'tax_class'  => array(
								'description' => 'Tax class of fee.',
								'type'        => 'string',
							),
							'tax_status' => array(
								'description' => 'Tax status of fee.',
								'type'        => 'string',
								'enum'        => array( 'taxable', 'none' ),
							),
							'total'      => array(
								'description' => 'Line total (after discounts).',
								'type'        => 'string',
							),
							'total_tax'  => array(
								'description' => 'Line total tax (after discounts).',
								'type'        => 'string',
								'readonly'    => true,
							),
							'taxes'      => array(
								'description' => 'Line taxes.',
								'type'        => 'array',
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'       => array(
											'description' => 'Tax rate ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'total'    => array(
											'description' => 'Tax total.',
											'type'        => 'string',
											'readonly'    => true,
										),
										'subtotal' => array(
											'description' => 'Tax subtotal.',
											'type'        => 'string',
											'readonly'    => true,
										),
									),
								),
							),
							'meta_data'  => array(
								'description' => 'Meta data.',
								'type'        => 'array',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Meta ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'key'   => array(
											'description' => 'Meta key.',
											'type'        => 'string',
										),
										'value' => array(
											'description' => 'Meta value.',
											'type'        => 'mixed',
										),
									),
								),
							),
						),
					),
				),
				'coupon_lines'         => array(
					'description' => 'Coupons line data.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => 'Item ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'code'         => array(
								'description' => 'Coupon code.',
								'type'        => 'string',
							),
							'discount'     => array(
								'description' => 'Discount total.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'discount_tax' => array(
								'description' => 'Discount total tax.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'meta_data'    => array(
								'description' => 'Meta data.',
								'type'        => 'array',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'    => array(
											'description' => 'Meta ID.',
											'type'        => 'integer',
											'readonly'    => true,
										),
										'key'   => array(
											'description' => 'Meta key.',
											'type'        => 'string',
										),
										'value' => array(
											'description' => 'Meta value.',
											'type'        => 'mixed',
										),
									),
								),
							),
						),
					),
				),
				'refunds'              => array(
					'description' => 'List of refunds.',
					'type'        => 'array',
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'description' => 'Refund ID.',
								'type'        => 'integer',
								'readonly'    => true,
							),
							'reason' => array(
								'description' => 'Refund reason.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'total'  => array(
								'description' => 'Refund total.',
								'type'        => 'string',
								'readonly'    => true,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Create a new order
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_order( $request ) {
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
		$order = \wc_create_order();

		if ( is_wp_error( $order ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $order->get_error_message(),
				),
				400
			);
		}

		$this->update_order_data( $order, $request );
		$order->save();

		// Set as paid if requested.
		if ( isset( $request['set_paid'] ) && $request['set_paid'] ) {
			$order->payment_complete();
		}

		// Log the activity.
		/* translators: %d is the order ID. */
		Activity_Log_Helper::add_log_entry( 'Orders', sprintf( __( 'Order Created (Order ID: %d)', 'sg-ai-studio' ), $order->get_id() ) );

		$response = $this->prepare_order_for_response( $order );

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
	 * Update an existing order
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_order( $request ) {
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
		$order_id = $request['id'];
		$order    = \wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid order ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$this->update_order_data( $order, $request );
		$order->save();

		// Log the activity.
		/* translators: %d is the order ID. */
		Activity_Log_Helper::add_log_entry( 'Orders', sprintf( __( 'Order Updated (Order ID: %d)', 'sg-ai-studio' ), $order->get_id() ) );

		$response = $this->prepare_order_for_response( $order );

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
	 * Delete an order
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_order( $request ) {
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
		$order_id = $request['id'];
		$force    = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$order    = \wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid order ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$previous = $this->prepare_order_for_response( $order );

		$result = $order->delete( $force );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The order could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		if ( $force ) {
			/* translators: %d is the order ID. */
			Activity_Log_Helper::add_log_entry( 'Orders', sprintf( __( 'Order Permanently Deleted (Order ID: %d)', 'sg-ai-studio' ), $order->get_id() ) );
		} else {
			/* translators: %d is the order ID. */
			Activity_Log_Helper::add_log_entry( 'Orders', sprintf( __( 'Order Moved to Trash (Order ID: %d)', 'sg-ai-studio' ), $order->get_id() ) );
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
					'message' => __( 'The order has been permanently deleted.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The order has been moved to the trash.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		}
	}

	/**
	 * Get a list of orders
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_orders( $request ) {
		$args = array(
			'limit'   => $request['per_page'],
			'page'    => $request['page'],
			'orderby' => $request['orderby'],
			'order'   => $request['order'],
		);

		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
		}

		if ( isset( $request['after'] ) && ! empty( $request['after'] ) ) {
			$args['date_created'] = '>=' . $request['after'];
		}

		if ( isset( $request['before'] ) && ! empty( $request['before'] ) ) {
			$args['date_created'] = '<=' . $request['before'];
		}

		if ( isset( $request['exclude'] ) && ! empty( $request['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$args['exclude'] = $request['exclude'];
		}

		if ( isset( $request['include'] ) && ! empty( $request['include'] ) ) {
			$args['include'] = $request['include'];
		}

		if ( isset( $request['offset'] ) && ! empty( $request['offset'] ) ) {
			$args['offset'] = $request['offset'];
		}

		if ( isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
			$args['parent'] = $request['parent'];
		}

		if ( isset( $request['parent_exclude'] ) && ! empty( $request['parent_exclude'] ) ) {
			$args['parent_exclude'] = $request['parent_exclude'];
		}

		if ( isset( $request['status'] ) && ! empty( $request['status'] ) ) {
			$args['status'] = $request['status'];
		}

		if ( isset( $request['customer'] ) && ! empty( $request['customer'] ) ) {
			$args['customer_id'] = $request['customer'];
		}

		if ( isset( $request['product'] ) && ! empty( $request['product'] ) ) {
			$args['product'] = $request['product'];
		}

		$orders       = \wc_get_orders( $args );
		$total_orders = \wc_get_orders(
			array_merge(
				$args,
				array(
					'limit'  => -1,
					'return' => 'ids',
				)
			)
		);
		$total_orders = count( $total_orders );

		$data = array();
		foreach ( $orders as $order ) {
			// Skip refund objects - they lack methods like get_order_number().
			if ( is_a( $order, 'WC_Order_Refund' ) ) {
				continue;
			}
			$data[] = $this->prepare_order_for_response( $order );
		}

		$max_pages = ceil( $total_orders / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'orders'      => $data,
					'total'       => $total_orders,
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
	 * Get a single order
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_order( $request ) {
		$order_id = $request['id'];
		$order    = \wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid order ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$response = $this->prepare_order_for_response( $order );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Update order data from request
	 *
	 * @param WC_Order        $order The order object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return void
	 */
	protected function update_order_data( $order, $request ) {
		$fields = array(
			'parent_id',
			'status',
			'currency',
			'customer_id',
			'customer_note',
			'payment_method',
			'payment_method_title',
			'transaction_id',
		);

		foreach ( $fields as $field ) {
			if ( isset( $request[ $field ] ) ) {
				$method = 'set_' . $field;
				if ( method_exists( $order, $method ) ) {
					$order->$method( $request[ $field ] );
				}
			}
		}

		// Handle billing address.
		if ( isset( $request['billing'] ) && is_array( $request['billing'] ) ) {
			foreach ( $request['billing'] as $key => $value ) {
				$method = 'set_billing_' . $key;
				if ( method_exists( $order, $method ) ) {
					$order->$method( $value );
				}
			}
		}

		// Handle shipping address.
		if ( isset( $request['shipping'] ) && is_array( $request['shipping'] ) ) {
			foreach ( $request['shipping'] as $key => $value ) {
				$method = 'set_shipping_' . $key;
				if ( method_exists( $order, $method ) ) {
					$order->$method( $value );
				}
			}
		}

		// Handle meta data.
		if ( isset( $request['meta_data'] ) && is_array( $request['meta_data'] ) ) {
			foreach ( $request['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) && isset( $meta['value'] ) ) {
					$order->update_meta_data( $meta['key'], $meta['value'] );
				}
			}
		}

		// Handle line items.
		if ( isset( $request['line_items'] ) && is_array( $request['line_items'] ) ) {
			// Remove existing line items.
			foreach ( $order->get_items() as $item_id => $item ) {
				$order->remove_item( $item_id );
			}

			// Add new line items.
			foreach ( $request['line_items'] as $line_item ) {
				if ( isset( $line_item['product_id'] ) ) {
					$product = \wc_get_product( $line_item['product_id'] );
					if ( $product ) {
						$quantity     = isset( $line_item['quantity'] ) ? $line_item['quantity'] : 1;
						$variation_id = isset( $line_item['variation_id'] ) ? $line_item['variation_id'] : 0;

						$item_id = $order->add_product(
							$product,
							$quantity,
							array(
								'variation_id' => $variation_id,
							)
						);
					}
				}
			}
		}

		// Handle shipping lines.
		if ( isset( $request['shipping_lines'] ) && is_array( $request['shipping_lines'] ) ) {
			// Remove existing shipping items.
			foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
				$order->remove_item( $item_id );
			}

			// Add new shipping items.
			foreach ( $request['shipping_lines'] as $shipping_line ) {
				$item = new \WC_Order_Item_Shipping();
				$item->set_method_title( $shipping_line['method_title'] );
				$item->set_method_id( $shipping_line['method_id'] );
				$item->set_total( $shipping_line['total'] );
				$order->add_item( $item );
			}
		}

		// Handle fee lines.
		if ( isset( $request['fee_lines'] ) && is_array( $request['fee_lines'] ) ) {
			// Remove existing fee items.
			foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
				$order->remove_item( $item_id );
			}

			// Add new fee items.
			foreach ( $request['fee_lines'] as $fee_line ) {
				$item = new \WC_Order_Item_Fee();
				$item->set_name( $fee_line['name'] );
				$item->set_tax_class( isset( $fee_line['tax_class'] ) ? $fee_line['tax_class'] : '' );
				$item->set_tax_status( isset( $fee_line['tax_status'] ) ? $fee_line['tax_status'] : 'taxable' );
				$item->set_total( $fee_line['total'] );
				$order->add_item( $item );
			}
		}

		// Handle coupon lines.
		if ( isset( $request['coupon_lines'] ) && is_array( $request['coupon_lines'] ) ) {
			// Remove existing coupon items.
			foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) {
				$order->remove_item( $item_id );
			}

			// Add new coupon items.
			foreach ( $request['coupon_lines'] as $coupon_line ) {
				$item = new \WC_Order_Item_Coupon();
				$item->set_code( $coupon_line['code'] );
				$order->add_item( $item );
			}
		}

		// Calculate totals.
		$order->calculate_totals();
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
				'description' => 'Array of orders to create.',
				'required'    => false,
			),
			'update' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'object' ),
				'description' => 'Array of orders to update.',
				'required'    => false,
			),
			'delete' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
				'description' => 'Array of order IDs to delete.',
				'required'    => false,
			),
			'force' => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => 'Whether to permanently delete orders (true) or move to trash (false).',
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

		foreach ( $items as $key => $order_data ) {
			// Create a new request for each order.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add order data to the request.
			foreach ( $order_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the order.
			$response = $this->create_order( $sub_request );

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

		foreach ( $items as $key => $order_data ) {
			if ( ! isset( $order_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Order ID is required for updating orders.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each order.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base . '/' . $order_data['id'] );

			// Add order data to the request.
			foreach ( $order_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the order.
			$response = $this->update_order( $sub_request );

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
	 * @param array $ids Order IDs to delete.
	 * @param bool  $force Whether to permanently delete (true) or move to trash (false).
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

		foreach ( $ids as $order_id ) {
			// Create a new request for each order.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $order_id );
			$sub_request->set_param( 'id', $order_id );
			$sub_request->set_param( 'force', $force );

			// Delete the order.
			$response = $this->delete_order( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $order_id ] = $response->get_data();
			} else {
				$results[ $order_id ] = array(
					'id'      => $order_id,
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
	 * Prepare an order for the response
	 *
	 * @param WC_Order $order Order object.
	 * @return array Prepared order data.
	 */
	protected function prepare_order_for_response( $order ) {
		$line_items = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$product      = $item->get_product();
			$line_items[] = array(
				'id'           => $item_id,
				'name'         => $item->get_name(),
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'quantity'     => $item->get_quantity(),
				'tax_class'    => $product ? $product->get_tax_class() : '',
				'subtotal'     => $order->get_line_subtotal( $item ),
				'total'        => $order->get_line_total( $item ),
				'total_tax'    => $order->get_line_tax( $item ),
				'sku'          => $product ? $product->get_sku() : '',
				'price'        => $product ? $product->get_price() : '',
			);
		}

		$shipping_lines = array();
		foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$shipping_lines[] = array(
				'id'           => $item_id,
				'method_title' => $item->get_method_title(),
				'method_id'    => $item->get_method_id(),
				'total'        => $item->get_total(),
				'total_tax'    => $item->get_total_tax(),
			);
		}

		$fee_lines = array();
		foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
			$fee_lines[] = array(
				'id'         => $item_id,
				'name'       => $item->get_name(),
				'tax_class'  => $item->get_tax_class(),
				'tax_status' => $item->get_tax_status(),
				'total'      => $item->get_total(),
				'total_tax'  => $item->get_total_tax(),
			);
		}

		$coupon_lines = array();
		foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) {
			$coupon_lines[] = array(
				'id'           => $item_id,
				'code'         => $item->get_code(),
				'discount'     => $item->get_discount(),
				'discount_tax' => $item->get_discount_tax(),
			);
		}

		$data = array(
			'id'                   => $order->get_id(),
			'parent_id'            => $order->get_parent_id(),
			'number'               => $order->get_order_number(),
			'order_key'            => $order->get_order_key(),
			'created_via'          => $order->get_created_via(),
			'version'              => $order->get_version(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'date_created'         => $order->get_date_created() ? $order->get_date_created()->format( 'c' ) : null,
			'date_created_gmt'     => $order->get_date_created() ? $order->get_date_created()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'date_modified'        => $order->get_date_modified() ? $order->get_date_modified()->format( 'c' ) : null,
			'date_modified_gmt'    => $order->get_date_modified() ? $order->get_date_modified()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'discount_total'       => $order->get_discount_total(),
			'discount_tax'         => $order->get_discount_tax(),
			'shipping_total'       => $order->get_shipping_total(),
			'shipping_tax'         => $order->get_shipping_tax(),
			'cart_tax'             => $order->get_cart_tax(),
			'total'                => $order->get_total(),
			'total_tax'            => $order->get_total_tax(),
			'prices_include_tax'   => $order->get_prices_include_tax(),
			'customer_id'          => $order->get_customer_id(),
			'customer_ip_address'  => $order->get_customer_ip_address(),
			'customer_user_agent'  => $order->get_customer_user_agent(),
			'customer_note'        => $order->get_customer_note(),
			'billing'              => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address_1'  => $order->get_billing_address_1(),
				'address_2'  => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'shipping'             => array(
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'company'    => $order->get_shipping_company(),
				'address_1'  => $order->get_shipping_address_1(),
				'address_2'  => $order->get_shipping_address_2(),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'transaction_id'       => $order->get_transaction_id(),
			'date_paid'            => $order->get_date_paid() ? $order->get_date_paid()->format( 'c' ) : null,
			'date_paid_gmt'        => $order->get_date_paid() ? $order->get_date_paid()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->format( 'c' ) : null,
			'date_completed_gmt'   => $order->get_date_completed() ? $order->get_date_completed()->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'c' ) : null,
			'cart_hash'            => $order->get_cart_hash(),
			'line_items'           => $line_items,
			'shipping_lines'       => $shipping_lines,
			'fee_lines'            => $fee_lines,
			'coupon_lines'         => $coupon_lines,
		);

		return $data;
	}
}
