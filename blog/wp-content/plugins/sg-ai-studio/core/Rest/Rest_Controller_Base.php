<?php
/**
 * Base REST Controller class
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use SG_AI_Studio\Helper\Helper;

/**
 * Abstract base class for REST API controllers.
 * Provides common functionality for all REST endpoints.
 */
abstract class Rest_Controller_Base {
	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'sg-ai-studio';

	/**
	 * Check JWT authorization for REST API requests
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	protected function check_jwt_authorization( $request ) {
		return Helper::check_jwt_authorization( $request );
	}

	/**
	 * Verify user has upload permissions and post ownership if applicable.
	 *
	 * Shared by every endpoint that can persist AI-generated images to the media
	 * library, so the `upload_files` capability is enforced consistently.
	 *
	 * @param int $post_id Optional post ID to verify ownership.
	 * @return true|\WP_REST_Response True if authorized, WP_REST_Response error otherwise.
	 */
	protected function verify_upload_authorization( $post_id = 0 ) {
		// Check if user has upload_files capability.
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'You do not have permission to upload files.', 'sg-ai-studio' ),
				),
				403
			);
		}

		// If post_id is provided, verify user can edit that specific post.
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );

			if ( ! $post ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Invalid post ID.', 'sg-ai-studio' ),
					),
					404
				);
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'You do not have permission to edit this post.', 'sg-ai-studio' ),
					),
					403
				);
			}
		}

		return true;
	}

	/**
	 * Purge the site caches after a write.
	 *
	 * Wraps the SiteGround Speed Optimizer purge when that plugin is active and
	 * always flushes the object cache, so callers do not repeat the guard.
	 *
	 * @return void
	 */
	protected function purge_caches() {
		if ( \function_exists( '\sg_cachepress_purge_cache' ) ) {
			\sg_cachepress_purge_cache();
		}

		\wp_cache_flush();
	}

	/**
	 * Parse the `_fields` query parameter into a normalized list of field names.
	 *
	 * Only top-level field selection is supported, so any dot-notation field
	 * (e.g. `title.rendered`) is reduced to its first segment.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return string[] Requested field names, or an empty array when none were requested.
	 */
	protected function get_requested_fields( $request ) {
		if ( ! isset( $request['_fields'] ) ) {
			return array();
		}

		$fields    = wp_parse_list( $request['_fields'] );
		$top_level = array();

		foreach ( $fields as $field ) {
			$field = trim( $field );
			if ( '' === $field ) {
				continue;
			}

			$segments    = explode( '.', $field );
			$top_level[] = $segments[0];
		}

		return array_values( array_unique( $top_level ) );
	}

	/**
	 * Reduce a prepared item to only the requested fields.
	 *
	 * Mirrors WordPress core's `_fields` behavior: when fields are requested, the
	 * `id` field is always retained so callers can still reference the item.
	 *
	 * @param array    $item   Prepared item data (associative array of field => value).
	 * @param string[] $fields Requested field names. An empty array returns the item unchanged.
	 * @return array The filtered item.
	 */
	protected function filter_item_fields( $item, $fields ) {
		if ( empty( $fields ) || ! is_array( $item ) || $this->is_sequential_array( $item ) ) {
			return $item;
		}

		if ( ! in_array( 'id', $fields, true ) ) {
			$fields[] = 'id';
		}

		return array_intersect_key( $item, array_flip( $fields ) );
	}

	/**
	 * Apply `_fields` selection to the `data` payload of a response envelope.
	 *
	 * Handles the three shapes our GET endpoints produce:
	 *  - Collection envelope: an associative array carrying an items list plus
	 *    pagination metadata (identified by the `total_pages` marker). Each item in
	 *    every list-valued entry is reduced to the requested fields.
	 *  - Single-item envelope: the item itself, reduced to the requested fields.
	 *  - Bare list of items: each item is reduced to the requested fields.
	 *
	 * Filtering is applied only at the top level of each item, matching core's
	 * `_fields` semantics — nested object lists (e.g. a product's `images`) are kept
	 * whole when their key is requested.
	 *
	 * @param mixed    $data   The `data` value from a response envelope.
	 * @param string[] $fields Requested field names. An empty array returns $data unchanged.
	 * @return mixed The filtered data.
	 */
	protected function filter_fields_in_data( $data, $fields ) {
		if ( empty( $fields ) || ! is_array( $data ) ) {
			return $data;
		}

		// Collection envelope (items list + pagination metadata).
		if ( isset( $data['total_pages'] ) ) {
			foreach ( $data as $key => $value ) {
				if ( $this->is_sequential_array( $value ) ) {
					$data[ $key ] = $this->filter_item_list( $value, $fields );
				}
			}

			return $data;
		}

		// Bare list of items.
		if ( $this->is_sequential_array( $data ) ) {
			return $this->filter_item_list( $data, $fields );
		}

		// Single-item envelope.
		return $this->filter_item_fields( $data, $fields );
	}

	/**
	 * Reduce every item in a list to the requested fields.
	 *
	 * @param array    $items  Sequential array of items.
	 * @param string[] $fields Requested field names.
	 * @return array The filtered list.
	 */
	private function filter_item_list( $items, $fields ) {
		return array_map(
			function ( $item ) use ( $fields ) {
				return is_array( $item ) ? $this->filter_item_fields( $item, $fields ) : $item;
			},
			$items
		);
	}

	/**
	 * Determine whether a value is a sequential (list-style) array.
	 *
	 * @param mixed $value Value to test.
	 * @return bool True when $value is a non-empty array with sequential integer keys.
	 */
	private function is_sequential_array( $value ) {
		if ( ! is_array( $value ) || array() === $value ) {
			return false;
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/**
	 * Check permissions for creating items
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function create_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check permissions for reading a single item
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function read_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check permissions for listing items (collection)
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function list_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check permissions for updating items
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function update_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check permissions for deleting items
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool|\WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function delete_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}
}
