<?php
/**
 * Products API class for managing WooCommerce products via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WooCommerce product operations.
 */
class Products extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'products';

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

		// Register endpoint for creating and getting products.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_product' ),
					'permission_callback' => array( $this, 'create_permissions_check' ),
					'args'                => $this->get_create_product_args(),
					'description'         => 'Creates a new WooCommerce product with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'list_permissions_check' ),
					'args'                => $this->get_products_args(),
					'description'         => 'Retrieves a list of WooCommerce products based on the provided filters.',
				),
				'schema' => array( $this, 'get_product_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single product.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_product' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the product.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific WooCommerce product by ID.',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_product' ),
					'permission_callback' => array( $this, 'update_permissions_check' ),
					'args'                => $this->get_update_product_args(),
					'description'         => 'Updates a specific WooCommerce product with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_product' ),
					'permission_callback' => array( $this, 'delete_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the product.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific WooCommerce product. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_product_schema' ),
			)
		);

		// Register endpoint for bulk operations on products.
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
	 * Get arguments for creating a product
	 *
	 * @return array
	 */
	protected function get_create_product_args() {
		return array(
			'name'               => array(
				'description' => 'Product name.',
				'type'        => 'string',
				'required'    => true,
			),
			'description'        => array(
				'description' => 'Product description.',
				'type'        => 'string',
				'required'    => false,
			),
			'short_description'  => array(
				'description' => 'Product short description.',
				'type'        => 'string',
				'required'    => false,
			),
			'sku'                => array(
				'description' => 'Unique identifier.',
				'type'        => 'string',
				'required'    => false,
			),
			'regular_price'      => array(
				'description' => 'Product regular price.',
				'type'        => 'string',
				'required'    => false,
			),
			'sale_price'         => array(
				'description' => 'Product sale price.',
				'type'        => 'string',
				'required'    => false,
			),
			'status'             => array(
				'description' => 'Product status.',
				'type'        => 'string',
				'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
				'default'     => 'publish',
				'required'    => false,
			),
			'catalog_visibility' => array(
				'description' => 'Catalog visibility.',
				'type'        => 'string',
				'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
				'default'     => 'visible',
				'required'    => false,
			),
			'featured'           => array(
				'description' => 'Featured product.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'virtual'            => array(
				'description' => 'If the product is virtual.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'downloadable'       => array(
				'description' => 'If the product is downloadable.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'manage_stock'       => array(
				'description' => 'Stock management at product level.',
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			),
			'stock_quantity'     => array(
				'description' => 'Stock quantity.',
				'type'        => 'integer',
				'required'    => false,
			),
			'stock_status'       => array(
				'description' => 'Controls the stock status of the product.',
				'type'        => 'string',
				'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
				'default'     => 'instock',
				'required'    => false,
			),
			'categories'         => array(
				'description' => 'List of categories.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'tags'               => array(
				'description' => 'List of tags.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'weight'             => array(
				'description' => 'Product weight.',
				'type'        => 'string',
				'required'    => false,
			),
			'dimensions'         => array(
				'description' => 'Product dimensions.',
				'type'        => 'object',
				'properties'  => array(
					'length' => array(
						'description' => 'Product length.',
						'type'        => 'string',
					),
					'width'  => array(
						'description' => 'Product width.',
						'type'        => 'string',
					),
					'height' => array(
						'description' => 'Product height.',
						'type'        => 'string',
					),
				),
				'required'    => false,
			),
			'images'             => array(
				'description' => 'List of images.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array(
							'description' => 'Image ID.',
							'type'        => 'integer',
						),
						'src'      => array(
							'description' => 'Image URL.',
							'type'        => 'string',
						),
						'name'     => array(
							'description' => 'Image name.',
							'type'        => 'string',
						),
						'alt'      => array(
							'description' => 'Image alternative text.',
							'type'        => 'string',
						),
						'position' => array(
							'description' => 'Image position. 0 means that the image is featured.',
							'type'        => 'integer',
						),
					),
				),
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a product
	 *
	 * @return array
	 */
	protected function get_update_product_args() {
		$args = [];

		// Add product ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the product.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving products
	 *
	 * @return array
	 */
	protected function get_products_args() {
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
			'status'       => array(
				'description' => 'Limit result set to products with specific statuses.',
				'type'        => 'string',
				'enum'        => array( 'any', 'draft', 'pending', 'private', 'publish' ),
				'default'     => 'any',
				'required'    => false,
			),
			'sku'          => array(
				'description' => 'Limit result set to products with specific SKU.',
				'type'        => 'string',
				'required'    => false,
			),
			'featured'     => array(
				'description' => 'Limit result set to featured products.',
				'type'        => 'boolean',
				'required'    => false,
			),
			'category'     => array(
				'description' => 'Limit result set to products assigned a specific category ID.',
				'type'        => 'string',
				'required'    => false,
			),
			'tag'          => array(
				'description' => 'Limit result set to products assigned a specific tag ID.',
				'type'        => 'string',
				'required'    => false,
			),
			'min_price'    => array(
				'description' => 'Limit result set to products based on a minimum price.',
				'type'        => 'string',
				'required'    => false,
			),
			'max_price'    => array(
				'description' => 'Limit result set to products based on a maximum price.',
				'type'        => 'string',
				'required'    => false,
			),
			'stock_status' => array(
				'description' => 'Limit result set to products with specified stock status.',
				'type'        => 'string',
				'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
				'required'    => false,
			),
			'orderby'      => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'id', 'include', 'title', 'slug', 'price', 'popularity', 'rating' ),
				'required'    => false,
			),
			'order'        => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Get product schema
	 *
	 * @return array
	 */
	public function get_product_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => 'Unique identifier for the product.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'name'               => array(
					'description' => 'Product name.',
					'type'        => 'string',
				),
				'slug'               => array(
					'description' => 'Product slug.',
					'type'        => 'string',
				),
				'permalink'          => array(
					'description' => 'Product URL.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'date_created'       => array(
					'description' => 'The date the product was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'date_modified'      => array(
					'description' => 'The date the product was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'type'               => array(
					'description' => 'Product type.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'status'             => array(
					'description' => 'Product status.',
					'type'        => 'string',
					'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
				),
				'featured'           => array(
					'description' => 'Featured product.',
					'type'        => 'boolean',
				),
				'catalog_visibility' => array(
					'description' => 'Catalog visibility.',
					'type'        => 'string',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
				),
				'description'        => array(
					'description' => 'Product description.',
					'type'        => 'string',
				),
				'short_description'  => array(
					'description' => 'Product short description.',
					'type'        => 'string',
				),
				'sku'                => array(
					'description' => 'Unique identifier.',
					'type'        => 'string',
				),
				'price'              => array(
					'description' => 'Current product price.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'regular_price'      => array(
					'description' => 'Product regular price.',
					'type'        => 'string',
				),
				'sale_price'         => array(
					'description' => 'Product sale price.',
					'type'        => 'string',
				),
				'price_html'         => array(
					'description' => 'Price formatted in HTML.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'on_sale'            => array(
					'description' => 'Shows if the product is on sale.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'purchasable'        => array(
					'description' => 'Shows if the product can be bought.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'total_sales'        => array(
					'description' => 'Amount of sales.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'virtual'            => array(
					'description' => 'If the product is virtual.',
					'type'        => 'boolean',
				),
				'downloadable'       => array(
					'description' => 'If the product is downloadable.',
					'type'        => 'boolean',
				),
				'external_url'       => array(
					'description' => 'Product external URL.',
					'type'        => 'string',
				),
				'button_text'        => array(
					'description' => 'Product external button text.',
					'type'        => 'string',
				),
				'tax_status'         => array(
					'description' => 'Tax status.',
					'type'        => 'string',
					'enum'        => array( 'taxable', 'shipping', 'none' ),
				),
				'tax_class'          => array(
					'description' => 'Tax class.',
					'type'        => 'string',
				),
				'manage_stock'       => array(
					'description' => 'Stock management at product level.',
					'type'        => 'boolean',
				),
				'stock_quantity'     => array(
					'description' => 'Stock quantity.',
					'type'        => 'integer',
				),
				'stock_status'       => array(
					'description' => 'Controls the stock status of the product.',
					'type'        => 'string',
					'enum'        => array( 'instock', 'outofstock', 'onbackorder' ),
				),
				'backorders'         => array(
					'description' => 'If managing stock, this controls if backorders are allowed.',
					'type'        => 'string',
					'enum'        => array( 'no', 'notify', 'yes' ),
				),
				'backorders_allowed' => array(
					'description' => 'Shows if backorders are allowed.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'backordered'        => array(
					'description' => 'Shows if the product is on backordered.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'sold_individually'  => array(
					'description' => 'Allow one item to be bought in a single order.',
					'type'        => 'boolean',
				),
				'weight'             => array(
					'description' => 'Product weight.',
					'type'        => 'string',
				),
				'dimensions'         => array(
					'description' => 'Product dimensions.',
					'type'        => 'object',
					'properties'  => array(
						'length' => array(
							'description' => 'Product length.',
							'type'        => 'string',
						),
						'width'  => array(
							'description' => 'Product width.',
							'type'        => 'string',
						),
						'height' => array(
							'description' => 'Product height.',
							'type'        => 'string',
						),
					),
				),
				'shipping_required'  => array(
					'description' => 'Shows if the product need to be shipped.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'shipping_taxable'   => array(
					'description' => 'Shows whether or not the product shipping is taxable.',
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'shipping_class'     => array(
					'description' => 'Shipping class slug.',
					'type'        => 'string',
				),
				'shipping_class_id'  => array(
					'description' => 'Shipping class ID.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'reviews_allowed'    => array(
					'description' => 'Allow reviews.',
					'type'        => 'boolean',
				),
				'average_rating'     => array(
					'description' => 'Reviews average rating.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'rating_count'       => array(
					'description' => 'Amount of reviews that the product have.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'related_ids'        => array(
					'description' => 'List of related products IDs.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'readonly'    => true,
				),
				'upsell_ids'         => array(
					'description' => 'List of up-sell products IDs.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'cross_sell_ids'     => array(
					'description' => 'List of cross-sell products IDs.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'parent_id'          => array(
					'description' => 'Product parent ID.',
					'type'        => 'integer',
				),
				'purchase_note'      => array(
					'description' => 'Optional note to send the customer after purchase.',
					'type'        => 'string',
				),
				'categories'         => array(
					'description' => 'List of categories.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => 'Category ID.',
								'type'        => 'integer',
							),
							'name' => array(
								'description' => 'Category name.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'slug' => array(
								'description' => 'Category slug.',
								'type'        => 'string',
								'readonly'    => true,
							),
						),
					),
				),
				'tags'               => array(
					'description' => 'List of tags.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => 'Tag ID.',
								'type'        => 'integer',
							),
							'name' => array(
								'description' => 'Tag name.',
								'type'        => 'string',
								'readonly'    => true,
							),
							'slug' => array(
								'description' => 'Tag slug.',
								'type'        => 'string',
								'readonly'    => true,
							),
						),
					),
				),
				'images'             => array(
					'description' => 'List of images.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'            => array(
								'description' => 'Image ID.',
								'type'        => 'integer',
							),
							'date_created'  => array(
								'description' => 'The date the image was created.',
								'type'        => 'string',
								'format'      => 'date-time',
								'readonly'    => true,
							),
							'date_modified' => array(
								'description' => 'The date the image was last modified.',
								'type'        => 'string',
								'format'      => 'date-time',
								'readonly'    => true,
							),
							'src'           => array(
								'description' => 'Image URL.',
								'type'        => 'string',
							),
							'name'          => array(
								'description' => 'Image name.',
								'type'        => 'string',
							),
							'alt'           => array(
								'description' => 'Image alternative text.',
								'type'        => 'string',
							),
							'position'      => array(
								'description' => 'Image position. 0 means that the image is featured.',
								'type'        => 'integer',
							),
						),
					),
				),
				'attributes'         => array(
					'description' => 'List of attributes.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array(
								'description' => 'Attribute ID.',
								'type'        => 'integer',
							),
							'name'      => array(
								'description' => 'Attribute name.',
								'type'        => 'string',
							),
							'position'  => array(
								'description' => 'Attribute position.',
								'type'        => 'integer',
							),
							'visible'   => array(
								'description' => 'Define if the attribute is visible on the "Additional information" tab in the product\'s page.',
								'type'        => 'boolean',
							),
							'variation' => array(
								'description' => 'Define if the attribute can be used as variation.',
								'type'        => 'boolean',
							),
							'options'   => array(
								'description' => 'List of available term names of the attribute.',
								'type'        => 'array',
								'items'       => array(
									'type' => 'string',
								),
							),
						),
					),
				),
				'default_attributes' => array(
					'description' => 'Defaults variation attributes.',
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'description' => 'Attribute ID.',
								'type'        => 'integer',
							),
							'name'   => array(
								'description' => 'Attribute name.',
								'type'        => 'string',
							),
							'option' => array(
								'description' => 'Selected attribute term name.',
								'type'        => 'string',
							),
						),
					),
				),
				'variations'         => array(
					'description' => 'List of variations IDs.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'readonly'    => true,
				),
				'grouped_products'   => array(
					'description' => 'List of grouped products ID.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'menu_order'         => array(
					'description' => 'Menu order, used to custom sort products.',
					'type'        => 'integer',
				),
			),
		);
	}

	/**
	 * Create a new product
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_product( $request ) {
		$product = new \WC_Product_Simple();
		$this->update_product_data( $product, $request );

		$product_id = $product->save();

		if ( is_wp_error( $product_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $product_id->get_error_message(),
				),
				400
			);
		}

		// Log the activity.
		/* translators: %1$s is the product name, %2$d is the product ID. */
		Activity_Log_Helper::add_log_entry( 'Products', sprintf( __( 'Product Created: %1$s (Product ID: %2$d)', 'sg-ai-studio' ), $product->get_name(), $product->get_id() ) );

		$response = $this->prepare_product_for_response( $product );

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
	 * Update an existing product
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_product( $request ) {
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
		$product_id = $request['id'];
		$product    = \wc_get_product( $product_id );

		if ( ! $product ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid product ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$this->update_product_data( $product, $request );
		$product->save();

		// Log the activity.
		/* translators: %1$s is the product name, %2$d is the product ID. */
		Activity_Log_Helper::add_log_entry( 'Products', sprintf( __( 'Product Updated: %1$s (Product ID: %2$d)', 'sg-ai-studio' ), $product->get_name(), $product->get_id() ) );

		$response = $this->prepare_product_for_response( $product );

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
	 * Delete a product
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_product( $request ) {
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
		$product_id = $request['id'];
		$force      = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$product    = \wc_get_product( $product_id );

		if ( ! $product ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid product ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$previous = $this->prepare_product_for_response( $product );

		if ( $force ) {
			$result = $product->delete( true );
		} else {
			wp_trash_post( $product_id );
			$result = 'trash' === get_post_status( $product_id );
		}

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The product could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		$product_name = $previous['name'];
		if ( $force ) {
			/* translators: %1$s is the product name, %2$d is the product ID. */
			Activity_Log_Helper::add_log_entry( 'Products', sprintf( __( 'Product Permanently Deleted: %1$s (Product ID: %2$d)', 'sg-ai-studio' ), $product_name, $product_id ) );
		} else {
			/* translators: %1$s is the product name, %2$d is the product ID. */
			Activity_Log_Helper::add_log_entry( 'Products', sprintf( __( 'Product Moved to Trash: %1$s (Product ID: %2$d)', 'sg-ai-studio' ), $product_name, $product_id ) );
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
					'message' => __( 'The product has been permanently deleted.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The product has been moved to the trash.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		}
	}

	/**
	 * Get a list of products
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_products( $request ) {
		$args = array(
			'status'  => $request['status'],
			'limit'   => $request['per_page'],
			'page'    => $request['page'],
			'orderby' => $request['orderby'],
			'order'   => $request['order'],
		);

		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['search'] = $request['search'];
		}

		if ( isset( $request['sku'] ) && ! empty( $request['sku'] ) ) {
			$args['sku'] = $request['sku'];
		}

		if ( isset( $request['featured'] ) ) {
			$args['featured'] = $request['featured'];
		}

		if ( isset( $request['category'] ) && ! empty( $request['category'] ) ) {
			$args['category'] = array( $request['category'] );
		}

		if ( isset( $request['tag'] ) && ! empty( $request['tag'] ) ) {
			$args['tag'] = array( $request['tag'] );
		}

		if ( isset( $request['min_price'] ) && ! empty( $request['min_price'] ) ) {
			$args['min_price'] = $request['min_price'];
		}

		if ( isset( $request['max_price'] ) && ! empty( $request['max_price'] ) ) {
			$args['max_price'] = $request['max_price'];
		}

		if ( isset( $request['stock_status'] ) && ! empty( $request['stock_status'] ) ) {
			$args['stock_status'] = $request['stock_status'];
		}

		$products       = \wc_get_products( $args );
		$total_products = \wc_get_products(
			array_merge(
				$args,
				array(
					'limit'  => -1,
					'return' => 'ids',
				)
			)
		);
		$total_products = count( $total_products );

		$data = array();
		foreach ( $products as $product ) {
			$data[] = $this->prepare_product_for_response( $product );
		}

		$max_pages = ceil( $total_products / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'products'    => $data,
					'total'       => $total_products,
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
	 * Get a single product
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_product( $request ) {
		$product_id = $request['id'];
		$product    = \wc_get_product( $product_id );

		if ( ! $product ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid product ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		$response = $this->prepare_product_for_response( $product );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Update product data from request
	 *
	 * @param \WC_Product     $product The product object.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return void
	 */
	protected function update_product_data( $product, $request ) {
		$fields = array(
			'name',
			'description',
			'short_description',
			'sku',
			'regular_price',
			'sale_price',
			'status',
			'catalog_visibility',
			'featured',
			'virtual',
			'downloadable',
			'manage_stock',
			'stock_quantity',
			'stock_status',
			'weight',
			'price',
		);

		foreach ( $fields as $field ) {
			if ( isset( $request[ $field ] ) ) {
				$method = 'set_' . $field;
				if ( method_exists( $product, $method ) ) {
					$product->$method( $request[ $field ] );
				}
			}
		}

		if ( isset( $request['dimensions'] ) && is_array( $request['dimensions'] ) ) {
			$dimensions = $request['dimensions'];
			if ( isset( $dimensions['length'] ) ) {
				$product->set_length( $dimensions['length'] );
			}
			if ( isset( $dimensions['width'] ) ) {
				$product->set_width( $dimensions['width'] );
			}
			if ( isset( $dimensions['height'] ) ) {
				$product->set_height( $dimensions['height'] );
			}
		}

		if ( isset( $request['categories'] ) && is_array( $request['categories'] ) ) {
			$product->set_category_ids( $request['categories'] );
		}

		if ( isset( $request['tags'] ) && is_array( $request['tags'] ) ) {
			$product->set_tag_ids( $request['tags'] );
		}

		if ( isset( $request['images'] ) && is_array( $request['images'] ) ) {
			$this->set_product_images( $product, $request['images'] );
		}
	}

	/**
	 * Set product images
	 *
	 * @param \WC_Product $product The product object.
	 * @param array       $images Array of image data.
	 * @return void
	 */
	protected function set_product_images( $product, $images ) {
		$gallery_ids = array();

		foreach ( $images as $index => $image ) {
			$attachment_id = null;

			// If image has an ID, use it.
			if ( isset( $image['id'] ) && ! empty( $image['id'] ) ) {
				$attachment_id = absint( $image['id'] );
			} elseif ( isset( $image['src'] ) && ! empty( $image['src'] ) ) {
				// Upload the image from URL.
				$attachment_id = $this->upload_image_from_url( $image['src'], $product->get_id() );
			}

			if ( $attachment_id ) {
				// Set image alt text if provided.
				if ( isset( $image['alt'] ) ) {
					update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $image['alt'] ) );
				}

				// Set image name if provided.
				if ( isset( $image['name'] ) ) {
					wp_update_post(
						array(
							'ID'         => $attachment_id,
							'post_title' => sanitize_text_field( $image['name'] ),
						)
					);
				}

				// First image or position 0 is the featured image.
				if ( 0 === $index || ( isset( $image['position'] ) && 0 === absint( $image['position'] ) ) ) {
					$product->set_image_id( $attachment_id );
				} else {
					$gallery_ids[] = $attachment_id;
				}
			}
		}

		// Set gallery images.
		if ( ! empty( $gallery_ids ) ) {
			$product->set_gallery_image_ids( $gallery_ids );
		}
	}

	/**
	 * Upload image from URL
	 *
	 * @param string $image_url Image URL.
	 * @param int    $product_id Product ID.
	 * @return int|false Attachment ID or false on failure.
	 */
	protected function upload_image_from_url( $image_url, $product_id ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download the image.
		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return false;
		}

		// Get the filename and extension.
		$file_array = array(
			'name'     => basename( $image_url ),
			'tmp_name' => $tmp,
		);

		// Upload the image.
		$attachment_id = media_handle_sideload( $file_array, $product_id );

		// Clean up temp file.
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $file_array['tmp_name'] );
			return false;
		}

		return $attachment_id;
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
				'description' => 'Array of products to create.',
				'required'    => false,
			),
			'update' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'object' ),
				'description' => 'Array of products to update.',
				'required'    => false,
			),
			'delete' => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'integer' ),
				'description' => 'Array of product IDs to delete.',
				'required'    => false,
			),
			'force' => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => 'Whether to permanently delete products (true) or move to trash (false).',
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

		foreach ( $items as $key => $product_data ) {
			// Create a new request for each product.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add product data to the request.
			foreach ( $product_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the product.
			$response = $this->create_product( $sub_request );

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

		foreach ( $items as $key => $product_data ) {
			if ( ! isset( $product_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Product ID is required for updating products.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each product.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base . '/' . $product_data['id'] );

			// Add product data to the request.
			foreach ( $product_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the product.
			$response = $this->update_product( $sub_request );

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
	 * @param array $ids Product IDs to delete.
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

		foreach ( $ids as $product_id ) {
			// Create a new request for each product.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $product_id );
			$sub_request->set_param( 'id', $product_id );
			$sub_request->set_param( 'force', $force );

			// Delete the product.
			$response = $this->delete_product( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $product_id ] = $response->get_data();
			} else {
				$results[ $product_id ] = array(
					'id'      => $product_id,
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
	 * Prepare a product for the response
	 *
	 * @param \WC_Product $product Product object.
	 * @return array Prepared product data.
	 */
	protected function prepare_product_for_response( $product ) {
		$categories = array();
		foreach ( $product->get_category_ids() as $category_id ) {
			$category = get_term( $category_id, 'product_cat' );
			if ( $category && ! is_wp_error( $category ) ) {
				$categories[] = array(
					'id'   => $category->term_id,
					'name' => $category->name,
					'slug' => $category->slug,
				);
			}
		}

		$tags = array();
		foreach ( $product->get_tag_ids() as $tag_id ) {
			$tag = get_term( $tag_id, 'product_tag' );
			if ( $tag && ! is_wp_error( $tag ) ) {
				$tags[] = array(
					'id'   => $tag->term_id,
					'name' => $tag->name,
					'slug' => $tag->slug,
				);
			}
		}

		$images = array();
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$attachment = get_post( $image_id );
			$images[] = array(
				'id'            => $image_id,
				'date_created'  => $attachment ? gmdate( 'c', strtotime( $attachment->post_date_gmt ) ) : null,
				'date_modified' => $attachment ? gmdate( 'c', strtotime( $attachment->post_modified_gmt ) ) : null,
				'src'           => wp_get_attachment_url( $image_id ),
				'name'          => $attachment ? $attachment->post_title : '',
				'alt'           => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'position'      => 0,
			);
		}

		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $position => $gallery_id ) {
			$attachment = get_post( $gallery_id );
			$images[] = array(
				'id'            => $gallery_id,
				'date_created'  => $attachment ? gmdate( 'c', strtotime( $attachment->post_date_gmt ) ) : null,
				'date_modified' => $attachment ? gmdate( 'c', strtotime( $attachment->post_modified_gmt ) ) : null,
				'src'           => wp_get_attachment_url( $gallery_id ),
				'name'          => $attachment ? $attachment->post_title : '',
				'alt'           => get_post_meta( $gallery_id, '_wp_attachment_image_alt', true ),
				'position'      => $position + 1,
			);
		}

		$data = array(
			'id'                 => $product->get_id(),
			'name'               => $product->get_name(),
			'slug'               => $product->get_slug(),
			'permalink'          => $product->get_permalink(),
			'date_created'       => $product->get_date_created() ? $product->get_date_created()->format( 'c' ) : null,
			'date_modified'      => $product->get_date_modified() ? $product->get_date_modified()->format( 'c' ) : null,
			'type'               => $product->get_type(),
			'status'             => $product->get_status(),
			'featured'           => $product->get_featured(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'description'        => $product->get_description(),
			'short_description'  => $product->get_short_description(),
			'sku'                => $product->get_sku(),
			'price'              => $product->get_price(),
			'regular_price'      => $product->get_regular_price(),
			'sale_price'         => $product->get_sale_price(),
			'price_html'         => $product->get_price_html(),
			'on_sale'            => $product->is_on_sale(),
			'purchasable'        => $product->is_purchasable(),
			'total_sales'        => $product->get_total_sales(),
			'virtual'            => $product->get_virtual(),
			'downloadable'       => $product->get_downloadable(),
			'manage_stock'       => $product->get_manage_stock(),
			'stock_quantity'     => $product->get_stock_quantity(),
			'stock_status'       => $product->get_stock_status(),
			'backorders'         => $product->get_backorders(),
			'backorders_allowed' => $product->backorders_allowed(),
			'backordered'        => $product->is_on_backorder(),
			'sold_individually'  => $product->get_sold_individually(),
			'weight'             => $product->get_weight(),
			'dimensions'         => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
			'shipping_required'  => $product->needs_shipping(),
			'shipping_taxable'   => $product->is_shipping_taxable(),
			'shipping_class'     => $product->get_shipping_class(),
			'shipping_class_id'  => $product->get_shipping_class_id(),
			'reviews_allowed'    => $product->get_reviews_allowed(),
			'average_rating'     => $product->get_average_rating(),
			'rating_count'       => $product->get_rating_count(),
			'related_ids'        => \wc_get_related_products( $product->get_id() ),
			'upsell_ids'         => $product->get_upsell_ids(),
			'cross_sell_ids'     => $product->get_cross_sell_ids(),
			'parent_id'          => $product->get_parent_id(),
			'purchase_note'      => $product->get_purchase_note(),
			'categories'         => $categories,
			'tags'               => $tags,
			'images'             => $images,
			'menu_order'         => $product->get_menu_order(),
		);

		return $data;
	}
}
