<?php
/**
 * Users API class for managing WordPress users via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_User_Query;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for user operations.
 */
class Users extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'users';

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for creating a new user.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_user' ),
					'permission_callback' => array( $this, 'create_user_permissions_check' ),
					'args'                => $this->get_create_user_args(),
					'description'         => 'Creates a new user with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_users' ),
					'permission_callback' => array( $this, 'get_users_permissions_check' ),
					'args'                => $this->get_users_args(),
					'description'         => 'Retrieves a list of users based on the provided filters.',
				),
				'schema' => array( $this, 'get_user_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single user.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_user' ),
					'permission_callback' => array( $this, 'get_user_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the user.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific user by ID.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_user' ),
					'permission_callback' => array( $this, 'update_user_permissions_check' ),
					'args'                => $this->get_update_user_args(),
					'description'         => 'Updates a specific user with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_user' ),
					'permission_callback' => array( $this, 'delete_user_permissions_check' ),
					'args'                => array(
						'id'       => array(
							'description' => 'Unique identifier for the user.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific user. Optionally reassign their content to another user.',
				),
				'schema' => array( $this, 'get_user_schema' ),
			)
		);

		// Register endpoint for bulk operations on users.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batch_create_users' ),
					'permission_callback' => array( $this, 'create_user_permissions_check' ),
					'args'                => $this->get_batch_create_users_args(),
					'description'         => 'Creates multiple users in a single request.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_users' ),
					'permission_callback' => array( $this, 'update_user_permissions_check' ),
					'args'                => $this->get_batch_update_users_args(),
					'description'         => 'Updates multiple users in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_users' ),
					'permission_callback' => array( $this, 'delete_user_permissions_check' ),
					'args'                => $this->get_batch_delete_users_args(),
					'description'         => 'Deletes multiple users in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to create users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_user_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_users_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read a specific user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read the item, WP_Error object otherwise.
	 */
	public function get_user_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to update a user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_user_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to delete a user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_user_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for creating a user.
	 *
	 * @return array
	 */
	protected function get_create_user_args() {
		return array(
			'username'    => array(
				'description' => 'Login name for the user.',
				'type'        => 'string',
				'required'    => true,
			),
			'email'       => array(
				'description' => 'The email address for the user.',
				'type'        => 'string',
				'format'      => 'email',
				'required'    => true,
			),
			'password'    => array(
				'description' => 'Password for the user (never included).',
				'type'        => 'string',
				'required'    => true,
			),
			'first_name'  => array(
				'description' => 'First name for the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'last_name'   => array(
				'description' => 'Last name for the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'name'        => array(
				'description' => 'Display name for the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'nickname'    => array(
				'description' => 'The nickname for the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'description' => array(
				'description' => 'Description of the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'url'         => array(
				'description' => 'URL of the user.',
				'type'        => 'string',
				'format'      => 'uri',
				'required'    => false,
			),
			'locale'      => array(
				'description' => 'Locale for the user.',
				'type'        => 'string',
				'required'    => false,
			),
			'roles'       => array(
				'description' => 'Roles assigned to the user.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => false,
			),
			'meta'        => array(
				'description' => 'Meta fields.',
				'type'        => 'object',
				'required'    => false,
			),
		);
	}

	/**
	 * Get arguments for updating a user
	 *
	 * @return array
	 */
	protected function get_update_user_args() {
		$args = [];

		// Add user ID.
		$args['id'] = array(
			'description' => 'Unique identifier for the user.',
			'type'        => 'integer',
			'required'    => true,
		);

		return $args;
	}

	/**
	 * Get arguments for retrieving users
	 *
	 * @return array
	 */
	protected function get_users_args() {
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
			'roles'    => array(
				'description' => 'Limit result set to users matching at least one role.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'required'    => false,
			),
			'orderby'  => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'name',
				'enum'        => array( 'id', 'include', 'name', 'registered_date', 'slug', 'email', 'url' ),
				'required'    => false,
			),
			'order'    => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'asc',
				'enum'        => array( 'asc', 'desc' ),
				'required'    => false,
			),
			'include'  => array(
				'description' => 'Limit result set to specific IDs.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'exclude'  => array( // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
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
	 * Get arguments for batch creating users
	 *
	 * @return array
	 */
	protected function get_batch_create_users_args() {
		return array(
			'users' => array(
				'description' => 'List of users to create.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_create_user_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch updating users
	 *
	 * @return array
	 */
	protected function get_batch_update_users_args() {
		return array(
			'users' => array(
				'description' => 'List of users to update.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_update_user_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting users
	 *
	 * @return array
	 */
	protected function get_batch_delete_users_args() {
		return array(
			'ids'      => array(
				'description' => 'List of user IDs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => true,
			),
			'reassign' => array(
				'description' => 'Reassign the deleted users\' posts and links to this user ID.',
				'type'        => 'integer',
				'required'    => false,
			),
		);
	}

	/**
	 * Get user schema
	 *
	 * @return array
	 */
	public function get_user_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => 'Unique identifier for the user.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'username'           => array(
					'description' => 'Login name for the user.',
					'type'        => 'string',
				),
				'email'              => array(
					'description' => 'The email address for the user.',
					'type'        => 'string',
					'format'      => 'email',
				),
				'first_name'         => array(
					'description' => 'First name for the user.',
					'type'        => 'string',
				),
				'last_name'          => array(
					'description' => 'Last name for the user.',
					'type'        => 'string',
				),
				'name'               => array(
					'description' => 'Display name for the user.',
					'type'        => 'string',
				),
				'nickname'           => array(
					'description' => 'The nickname for the user.',
					'type'        => 'string',
				),
				'slug'               => array(
					'description' => 'An alphanumeric identifier for the user.',
					'type'        => 'string',
				),
				'description'        => array(
					'description' => 'Description of the user.',
					'type'        => 'string',
				),
				'url'                => array(
					'description' => 'URL of the user.',
					'type'        => 'string',
					'format'      => 'uri',
				),
				'link'               => array(
					'description' => 'Author URL of the user.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'locale'             => array(
					'description' => 'Locale for the user.',
					'type'        => 'string',
				),
				'registered_date'    => array(
					'description' => 'Registration date for the user.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'roles'              => array(
					'description' => 'Roles assigned to the user.',
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
				),
				'capabilities'       => array(
					'description' => 'All capabilities assigned to the user.',
					'type'        => 'object',
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => 'Any extra capabilities assigned to the user.',
					'type'        => 'object',
					'readonly'    => true,
				),
				'avatar_urls'        => array(
					'description' => 'Avatar URLs for the user.',
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'24' => array(
							'description' => 'Avatar URL with image size of 24 pixels.',
							'type'        => 'string',
							'format'      => 'uri',
						),
						'48' => array(
							'description' => 'Avatar URL with image size of 48 pixels.',
							'type'        => 'string',
							'format'      => 'uri',
						),
						'96' => array(
							'description' => 'Avatar URL with image size of 96 pixels.',
							'type'        => 'string',
							'format'      => 'uri',
						),
					),
				),
				'meta'               => array(
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
	 * Create a new user
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_user( $request ) {
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

		// Prepare user data.
		$user_data = $this->prepare_user_for_database( $request );

		// Check if username or email already exists.
		if ( username_exists( $user_data['user_login'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Username already exists.', 'sg-ai-studio' ),
				),
				400
			);
		}

		if ( email_exists( $user_data['user_email'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Email address already exists.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Create the user.
		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $user_id->get_error_message(),
				),
				400
			);
		}

		// Handle roles if provided.
		if ( isset( $request['roles'] ) && is_array( $request['roles'] ) ) {
			$user = new \WP_User( $user_id );
			$user->set_role( '' ); // Remove default role.
			foreach ( $request['roles'] as $role ) {
				$user->add_role( $role );
			}
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_user_meta( $user_id, $meta_key, $meta_value );
			}
		}

		// Get the user.
		$user = get_userdata( $user_id );

		// Log the activity.
		/* translators: %1$s is the username, %2$d is the user ID. */
		$log_description = sprintf( __( 'User Created: %1$s (ID: %2$d)', 'sg-ai-studio' ), $user->user_login, $user_id );
		Activity_Log_Helper::add_log_entry( 'Users', $log_description );

		// Format the response.
		$response = $this->prepare_user_for_response( $user );

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
	 * Update an existing user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_user( $request ) {
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

		$user_id = $request['id'];
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid user ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare user data.
		$user_data       = $this->prepare_user_for_database( $request );
		$user_data['ID'] = $user_id;

		// Check if username or email already exists (excluding current user).
		if ( isset( $user_data['user_login'] ) && username_exists( $user_data['user_login'] ) && $user->user_login !== $user_data['user_login'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Username already exists.', 'sg-ai-studio' ),
				),
				400
			);
		}

		if ( isset( $user_data['user_email'] ) && email_exists( $user_data['user_email'] ) && $user->user_email !== $user_data['user_email'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Email address already exists.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Update the user.
		$updated = wp_update_user( $user_data );

		if ( is_wp_error( $updated ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $updated->get_error_message(),
				),
				400
			);
		}

		// Handle roles if provided.
		if ( isset( $request['roles'] ) && is_array( $request['roles'] ) ) {
			$user = new \WP_User( $user_id );
			$user->set_role( '' ); // Remove all roles.
			foreach ( $request['roles'] as $role ) {
				$user->add_role( $role );
			}
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_user_meta( $user_id, $meta_key, $meta_value );
			}
		}

		// Get the updated user.
		$user = get_userdata( $user_id );

		// Log the activity.
		/* translators: %1$s is the username, %2$d is the user ID. */
		$log_description = sprintf( __( 'User Updated: %1$s (ID: %2$d)', 'sg-ai-studio' ), $user->user_login, $user_id );
		Activity_Log_Helper::add_log_entry( 'Users', $log_description );

		// Format the response.
		$response = $this->prepare_user_for_response( $user );

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
	 * Delete a user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_user( $request ) {
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

		$user_id  = $request['id'];
		$reassign = isset( $request['reassign'] ) ? absint( $request['reassign'] ) : null;
		$user     = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid user ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Get the user before deleting it.
		$previous = $this->prepare_user_for_response( $user );

		// Include necessary files.
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		// Delete the user.
		$result = wp_delete_user( $user_id, $reassign );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The user could not be deleted.', 'sg-ai-studio' ),
				),
				500
			);
		}

		// Log the activity.
		/* translators: %1$s is the username, %2$d is the user ID. */
		$log_description = sprintf( __( 'User Deleted: %1$s (ID: %2$d)', 'sg-ai-studio' ), $previous['user_login'], $user_id );
		Activity_Log_Helper::add_log_entry( 'Users', $log_description );

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
				'message' => __( 'The user has been deleted.', 'sg-ai-studio' ),
				'data'    => $previous,
			),
			200
		);
	}

	/**
	 * Get a list of users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_users( $request ) {
		// Prepare query arguments.
		$args = array(
			'number'  => $request['per_page'],
			'offset'  => ( $request['page'] - 1 ) * $request['per_page'],
			'orderby' => $request['orderby'],
			'order'   => $request['order'],
		);

		// Handle search parameter.
		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['search']         = '*' . $request['search'] . '*';
			$args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email', 'display_name' );
		}

		// Handle roles parameter.
		if ( isset( $request['roles'] ) && ! empty( $request['roles'] ) ) {
			$args['role__in'] = $request['roles'];
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

		// Get users.
		$query = new WP_User_Query( $args );
		$users = $query->get_results();

		// Format the response.
		$data = array();
		foreach ( $users as $user ) {
			$data[] = $this->prepare_user_for_response( $user );
		}

		// Get total count for pagination.
		$total_args                = $args;
		$total_args['count_total'] = true;
		unset( $total_args['number'], $total_args['offset'] );
		$total_query = new WP_User_Query( $total_args );
		$total_users = $total_query->get_total();

		$max_pages = ceil( $total_users / $request['per_page'] );

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'users'       => $data,
					'total'       => $total_users,
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
	 * Get a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_user( $request ) {
		$user_id = $request['id'];
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid user ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_user_for_response( $user );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Batch create users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_create_users( $request ) {
		$users   = $request['users'];
		$results = array();
		$errors  = array();

		foreach ( $users as $key => $user_data ) {
			// Create a new request for each user.
			$sub_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/' . $this->base );

			// Add user data to the request.
			foreach ( $user_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Create the user.
			$response = $this->create_user( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

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
	 * Batch update users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_users( $request ) {
		$users   = $request['users'];
		$results = array();
		$errors  = array();

		foreach ( $users as $key => $user_data ) {
			if ( ! isset( $user_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'User ID is required for updating users.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each user.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $user_data['id'] );

			// Add user data to the request.
			foreach ( $user_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the user.
			$response = $this->update_user( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

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
	 * Batch delete users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_users( $request ) {
		$ids      = $request['ids'];
		$reassign = isset( $request['reassign'] ) ? absint( $request['reassign'] ) : null;
		$results  = array();
		$errors   = array();

		foreach ( $ids as $key => $user_id ) {
			// Create a new request for each user.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $user_id );
			$sub_request->set_param( 'id', $user_id );
			if ( $reassign ) {
				$sub_request->set_param( 'reassign', $reassign );
			}

			// Delete the user.
			$response = $this->delete_user( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $user_id ] = $response->get_data();
			} else {
				$results[ $user_id ] = $response->get_data()['message'];
			}
		}

		$success = empty( $errors );

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
	 * Prepare a user for database insertion.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array $prepared_user.
	 */
	protected function prepare_user_for_database( $request ) {
		$prepared_user = array();

		// User login.
		if ( isset( $request['username'] ) ) {
			$prepared_user['user_login'] = sanitize_user( $request['username'] );
		}

		// User email.
		if ( isset( $request['email'] ) ) {
			$prepared_user['user_email'] = sanitize_email( $request['email'] );
		}

		// User password.
		if ( isset( $request['password'] ) ) {
			$prepared_user['user_pass'] = $request['password'];
		}

		// First name.
		if ( isset( $request['first_name'] ) ) {
			$prepared_user['first_name'] = sanitize_text_field( $request['first_name'] );
		}

		// Last name.
		if ( isset( $request['last_name'] ) ) {
			$prepared_user['last_name'] = sanitize_text_field( $request['last_name'] );
		}

		// Display name.
		if ( isset( $request['name'] ) ) {
			$prepared_user['display_name'] = sanitize_text_field( $request['name'] );
		}

		// Nickname.
		if ( isset( $request['nickname'] ) ) {
			$prepared_user['nickname'] = sanitize_text_field( $request['nickname'] );
		}

		// Description.
		if ( isset( $request['description'] ) ) {
			$prepared_user['description'] = sanitize_textarea_field( $request['description'] );
		}

		// URL.
		if ( isset( $request['url'] ) ) {
			$prepared_user['user_url'] = esc_url_raw( $request['url'] );
		}

		// Locale.
		if ( isset( $request['locale'] ) ) {
			$prepared_user['locale'] = sanitize_text_field( $request['locale'] );
		}

		return $prepared_user;
	}

	/**
	 * Prepare a user for the response.
	 *
	 * @param \WP_User $user User object.
	 * @return array Prepared user data.
	 */
	protected function prepare_user_for_response( $user ) {
		// Get avatar URLs.
		$avatar_urls  = array();
		$avatar_sizes = array( 24, 48, 96 );
		foreach ( $avatar_sizes as $size ) {
			$avatar_urls[ $size ] = get_avatar_url( $user->ID, array( 'size' => $size ) );
		}

		// Prepare response.
		$data = array(
			'id'                 => $user->ID,
			'username'           => $user->user_login,
			'email'              => $user->user_email,
			'first_name'         => $user->first_name,
			'last_name'          => $user->last_name,
			'name'               => $user->display_name,
			'nickname'           => $user->nickname,
			'slug'               => $user->user_nicename,
			'description'        => $user->description,
			'url'                => $user->user_url,
			'link'               => get_author_posts_url( $user->ID, $user->user_nicename ),
			'locale'             => get_user_locale( $user ),
			'registered_date'    => mysql_to_rfc3339( $user->user_registered ),
			'roles'              => $user->roles,
			'capabilities'       => $user->allcaps,
			'extra_capabilities' => $user->caps,
			'avatar_urls'        => $avatar_urls,
		);

		return $data;
	}
}
