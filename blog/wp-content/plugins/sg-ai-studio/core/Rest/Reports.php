<?php
/**
 * Reports API class for managing WooCommerce reports via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for WooCommerce report operations.
 */
class Reports extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'reports';

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

		// Register endpoint for retrieving all available reports.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_reports' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves a list of all available report types.',
			)
		);

		// Register endpoint for sales report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/sales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_sales_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'args'                => $this->get_report_date_args(),
				'description'         => 'Retrieves sales report data.',
			)
		);

		// Register endpoint for top sellers report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/top_sellers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_top_sellers_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'args'                => $this->get_report_date_args(),
				'description'         => 'Retrieves top sellers report data.',
			)
		);

		// Register endpoint for coupons totals report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/coupons/totals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_coupons_totals_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves coupons totals report data.',
			)
		);

		// Register endpoint for customers totals report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/customers/totals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_customers_totals_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves customers totals report data.',
			)
		);

		// Register endpoint for orders totals report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/orders/totals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_orders_totals_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves orders totals report data.',
			)
		);

		// Register endpoint for products totals report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/products/totals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_products_totals_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves products totals report data.',
			)
		);

		// Register endpoint for reviews totals report.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/reviews/totals',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_reviews_totals_report' ),
				'permission_callback' => array( $this, 'get_reports_permissions_check' ),
				'description'         => 'Retrieves reviews totals report data.',
			)
		);
	}

	/**
	 * Check if a user has permission to access reports
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_reports_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for reports with date filtering
	 *
	 * @return array
	 */
	protected function get_report_date_args() {
		return array(
			'period'   => array(
				'description' => 'Report period.',
				'type'        => 'string',
				'enum'        => array( 'week', 'month', 'last_month', 'year' ),
				'default'     => 'week',
				'required'    => false,
			),
			'date_min' => array(
				'description' => 'Return sales for a specific start date, the date need to be in the YYYY-MM-DD format.',
				'type'        => 'string',
				'format'      => 'date',
				'required'    => false,
			),
			'date_max' => array(
				'description' => 'Return sales for a specific end date, the date need to be in the YYYY-MM-DD format.',
				'type'        => 'string',
				'format'      => 'date',
				'required'    => false,
			),
		);
	}

	/**
	 * Get a list of all available reports
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_reports( $request ) {
		$reports = array(
			array(
				'slug'        => 'sales',
				'description' => 'Sales report.',
			),
			array(
				'slug'        => 'top_sellers',
				'description' => 'Top sellers report.',
			),
			array(
				'slug'        => 'coupons/totals',
				'description' => 'Coupons totals report.',
			),
			array(
				'slug'        => 'customers/totals',
				'description' => 'Customers totals report.',
			),
			array(
				'slug'        => 'orders/totals',
				'description' => 'Orders totals report.',
			),
			array(
				'slug'        => 'products/totals',
				'description' => 'Products totals report.',
			),
			array(
				'slug'        => 'reviews/totals',
				'description' => 'Reviews totals report.',
			),
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $reports,
			),
			200
		);
	}

	/**
	 * Get sales report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_sales_report( $request ) {
		$date_range = $this->get_date_range( $request );

		// Get orders within date range.
		$args = array(
			'limit'        => -1,
			'status'       => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
			'date_created' => $date_range['start'] . '...' . $date_range['end'],
		);

		$orders = \wc_get_orders( $args );

		$total_sales    = 0;
		$total_orders   = count( $orders );
		$total_items    = 0;
		$total_tax      = 0;
		$total_shipping = 0;

		foreach ( $orders as $order ) {
			$total_sales    += $order->get_total();
			$total_items    += $order->get_item_count();
			$total_tax      += $order->get_total_tax();
			$total_shipping += $order->get_shipping_total();
		}

		$average_sales = $total_orders > 0 ? $total_sales / $total_orders : 0;

		$data = array(
			'total_sales'    => number_format( $total_sales, 2, '.', '' ),
			'net_sales'      => number_format( $total_sales - $total_tax - $total_shipping, 2, '.', '' ),
			'average_sales'  => number_format( $average_sales, 2, '.', '' ),
			'total_orders'   => $total_orders,
			'total_items'    => $total_items,
			'total_tax'      => number_format( $total_tax, 2, '.', '' ),
			'total_shipping' => number_format( $total_shipping, 2, '.', '' ),
			'date_range'     => array(
				'start' => $date_range['start'],
				'end'   => $date_range['end'],
			),
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get top sellers report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_top_sellers_report( $request ) {
		global $wpdb;

		$date_range = $this->get_date_range( $request );

		// Check if HPOS (High-Performance Order Storage) is enabled.
		$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		if ( $hpos_enabled ) {
			// Query for HPOS (custom orders table).
			$query = "
				SELECT
					order_items.order_item_name as product_name,
					SUM(order_item_meta.meta_value) as quantity,
					SUM(order_item_meta_total.meta_value) as total
				FROM {$wpdb->prefix}woocommerce_order_items as order_items
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_total ON order_items.order_item_id = order_item_meta_total.order_item_id
				LEFT JOIN {$wpdb->prefix}wc_orders AS orders ON order_items.order_id = orders.id
				WHERE orders.type = 'shop_order'
				AND orders.status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
				AND order_items.order_item_type = 'line_item'
				AND order_item_meta.meta_key = '_qty'
				AND order_item_meta_total.meta_key = '_line_total'
				AND orders.date_created_gmt >= %s
				AND orders.date_created_gmt <= %s
				GROUP BY order_items.order_item_name
				ORDER BY quantity DESC
				LIMIT 10
			";
		} else {
			// Legacy query for posts table.
			$query = "
				SELECT
					order_items.order_item_name as product_name,
					SUM(order_item_meta.meta_value) as quantity,
					SUM(order_item_meta_total.meta_value) as total
				FROM {$wpdb->prefix}woocommerce_order_items as order_items
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_total ON order_items.order_item_id = order_item_meta_total.order_item_id
				LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
				WHERE posts.post_type = 'shop_order'
				AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
				AND order_items.order_item_type = 'line_item'
				AND order_item_meta.meta_key = '_qty'
				AND order_item_meta_total.meta_key = '_line_total'
				AND posts.post_date >= %s
				AND posts.post_date <= %s
				GROUP BY order_items.order_item_name
				ORDER BY quantity DESC
				LIMIT 10
			";
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				$query,      // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$date_range['start'],
				$date_range['end']
			)
		);

		$data = array();
		foreach ( $results as $result ) {
			$data[] = array(
				'product_name' => $result->product_name,
				'quantity'     => (int) $result->quantity,
				'total'        => number_format( $result->total, 2, '.', '' ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'top_sellers' => $data,
					'date_range'  => array(
						'start' => $date_range['start'],
						'end'   => $date_range['end'],
					),
				),
			),
			200
		);
	}

	/**
	 * Get coupons totals report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_coupons_totals_report( $request ) {
		global $wpdb;

		// Get coupon counts by status.
		$query = "
			SELECT COUNT(*) as count
			FROM {$wpdb->posts}
			WHERE post_type = 'shop_coupon'
			AND post_status = 'publish'
		";

		$total = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		$data = array(
			array(
				'slug'  => 'all',
				'name'  => __( 'All Coupons', 'sg-ai-studio' ),
				'total' => (int) $total,
			),
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get customers totals report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_customers_totals_report( $request ) {
		$customer_query = new \WP_User_Query(
			array(
				'role'   => 'customer',
				'fields' => 'ID',
			)
		);

		$total_customers = $customer_query->get_total();

		// Get customers with orders.
		global $wpdb;

		// Check if HPOS (High-Performance Order Storage) is enabled.
		$hpos_enabled = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		if ( $hpos_enabled ) {
			// Query for HPOS (custom orders table).
			$query = "
				SELECT COUNT(DISTINCT customer_id) as count
				FROM {$wpdb->prefix}wc_orders
				WHERE customer_id > 0
				AND type = 'shop_order'
			";
		} else {
			// Legacy query for postmeta table.
			$query = "
				SELECT COUNT(DISTINCT meta_value) as count
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_customer_user'
				AND meta_value > 0
			";
		}

		$paying_customers = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		$data = array(
			array(
				'slug'  => 'all',
				'name'  => __( 'All Customers', 'sg-ai-studio' ),
				'total' => (int) $total_customers,
			),
			array(
				'slug'  => 'paying',
				'name'  => __( 'Paying Customers', 'sg-ai-studio' ),
				'total' => (int) $paying_customers,
			),
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get orders totals report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_orders_totals_report( $request ) {
		$order_statuses = \wc_get_order_statuses();
		$data           = array();

		foreach ( $order_statuses as $slug => $name ) {
			$count = \wc_orders_count( $slug );

			$data[] = array(
				'slug'  => str_replace( 'wc-', '', $slug ),
				'name'  => $name,
				'total' => (int) $count,
			);
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
	 * Get products totals report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_products_totals_report( $request ) {
		$product_statuses = array(
			'publish' => __( 'Published', 'sg-ai-studio' ),
			'draft'   => __( 'Draft', 'sg-ai-studio' ),
			'pending' => __( 'Pending', 'sg-ai-studio' ),
			'private' => __( 'Private', 'sg-ai-studio' ),
		);

		$data = array();

		foreach ( $product_statuses as $slug => $name ) {
			$products = \wc_get_products(
				array(
					'status' => $slug,
					'limit'  => -1,
					'return' => 'ids',
				)
			);

			$data[] = array(
				'slug'  => $slug,
				'name'  => $name,
				'total' => count( $products ),
			);
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
	 * Get reviews totals report
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object on success.
	 */
	public function get_reviews_totals_report( $request ) {
		global $wpdb;

		$ratings = array();

		for ( $i = 1; $i <= 5; $i++ ) {
			$query = "
				SELECT COUNT(*) as count
				FROM {$wpdb->comments}
				WHERE comment_type = 'review'
				AND comment_approved = '1'
				AND comment_parent = 0
				AND comment_content LIKE %s
			";

			// This is a simplified approach. In production, you'd want to check the actual rating meta.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var(
				$wpdb->prepare(
					"
					SELECT COUNT(DISTINCT c.comment_ID) as count
					FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
					WHERE c.comment_type = 'review'
					AND c.comment_approved = '1'
					AND cm.meta_key = 'rating'
					AND cm.meta_value = %d
					",
					$i
				)
			);

			$ratings[] = array(
				'slug'  => $i . '-star',
				// translators: number of stars.
				'name'  => sprintf( __( '%d Star', 'sg-ai-studio' ), $i ),
				'total' => (int) $count,
			);
		}

		// Get all reviews count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$all_reviews = $wpdb->get_var(
			"
			SELECT COUNT(*) as count
			FROM {$wpdb->comments}
			WHERE comment_type = 'review'
			AND comment_approved = '1'
		"
		);

		$data = array_merge(
			array(
				array(
					'slug'  => 'all',
					'name'  => __( 'All Reviews', 'sg-ai-studio' ),
					'total' => (int) $all_reviews,
				),
			),
			$ratings
		);

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get date range for reports
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Array with start and end dates.
	 */
	protected function get_date_range( $request ) {
		if ( isset( $request['date_min'] ) && isset( $request['date_max'] ) ) {
			return array(
				'start' => $request['date_min'] . ' 00:00:00',
				'end'   => $request['date_max'] . ' 23:59:59',
			);
		}

		$period = isset( $request['period'] ) ? $request['period'] : 'week';

		switch ( $period ) {
			case 'year':
				$start = gmdate( 'Y-01-01 00:00:00' );
				$end   = gmdate( 'Y-12-31 23:59:59' );
				break;
			case 'last_month':
				$start = gmdate( 'Y-m-01 00:00:00', strtotime( 'first day of last month' ) );
				$end   = gmdate( 'Y-m-t 23:59:59', strtotime( 'last day of last month' ) );
				break;
			case 'month':
				$start = gmdate( 'Y-m-01 00:00:00' );
				$end   = gmdate( 'Y-m-t 23:59:59' );
				break;
			case 'week':
			default:
				$start = gmdate( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) );
				$end   = gmdate( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) );
				break;
		}

		return array(
			'start' => $start,
			'end'   => $end,
		);
	}
}
