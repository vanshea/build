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
