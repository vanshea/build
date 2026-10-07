<?php
/**
 * Media API class for managing WordPress media via REST API
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Query;
use SG_AI_Studio\Activity_Log\Activity_Log_Helper;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for media operations.
 */
class Media extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'media';


	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for uploading new media.
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'upload_media' ),
					'permission_callback' => array( $this, 'upload_media_permissions_check' ),
					'args'                => $this->get_upload_media_args(),
					'description'         => 'Uploads a new media file with the provided data.',
				),
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_media' ),
					'permission_callback' => array( $this, 'get_media_permissions_check' ),
					'args'                => $this->get_media_args(),
					'description'         => 'Retrieves a list of media items based on the provided filters.',
				),
				'schema' => array( $this, 'get_media_schema' ),
			)
		);

		// Register endpoint for retrieving, updating, and deleting a single media item.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_media_item' ),
					'permission_callback' => array( $this, 'get_media_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => 'Unique identifier for the media item.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Retrieves a specific media item by ID.',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_media' ),
					'permission_callback' => array( $this, 'update_media_permissions_check' ),
					'args'                => $this->get_update_media_args(),
					'description'         => 'Updates a specific media item with the provided data.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_media' ),
					'permission_callback' => array( $this, 'delete_media_permissions_check' ),
					'args'                => array(
						'id'    => array(
							'description' => 'Unique identifier for the media item.',
							'type'        => 'integer',
							'required'    => true,
						),
					),
					'description'         => 'Deletes a specific media item. By default, moves to trash unless force=true.',
				),
				'schema' => array( $this, 'get_media_schema' ),
			)
		);

		// Register endpoint for bulk operations on media.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/batch',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'batch_update_media' ),
					'permission_callback' => array( $this, 'update_media_permissions_check' ),
					'args'                => $this->get_batch_update_media_args(),
					'description'         => 'Updates multiple media items in a single request.',
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'batch_delete_media' ),
					'permission_callback' => array( $this, 'delete_media_permissions_check' ),
					'args'                => $this->get_batch_delete_media_args(),
					'description'         => 'Deletes multiple media items in a single request.',
				),
				'schema' => array( $this, 'get_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a user has permission to upload media
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to upload media, WP_Error object otherwise.
	 */
	public function upload_media_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read media
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read items, WP_Error object otherwise.
	 */
	public function get_media_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to read a specific media item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to read the item, WP_Error object otherwise.
	 */
	public function get_media_item_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to update a media item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_media_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Check if a user has permission to delete a media item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_media_permissions_check( $request ) {
		return $this->check_jwt_authorization( $request );
	}

	/**
	 * Get arguments for uploading media.
	 *
	 * @return array
	 */
	protected function get_upload_media_args() {
		return array(
			'file'        => array(
				'description' => 'The file to upload (base64 encoded).',
				'type'        => 'string',
				'required'    => false,
			),
			'file_url'    => array(
				'description' => 'URL of the file to upload.',
				'type'        => 'string',
				'format'      => 'uri',
				'required'    => false,
			),
			'filename'    => array(
				'description' => 'The filename for the uploaded file.',
				'type'        => 'string',
				'required'    => false,
			),
			'title'       => array(
				'description' => 'The title for the media item.',
				'type'        => 'string',
				'required'    => false,
			),
			'caption'     => array(
				'description' => 'The caption for the media item.',
				'type'        => 'string',
				'required'    => false,
			),
			'description' => array(
				'description' => 'The description for the media item.',
				'type'        => 'string',
				'required'    => false,
			),
			'alt_text'    => array(
				'description' => 'Alternative text to describe the media item.',
				'type'        => 'string',
				'required'    => false,
			),
			'post'        => array(
				'description' => 'The ID of the associated post.',
				'type'        => 'integer',
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
	 * Get arguments for updating a media item.
	 *
	 * @return array
	 */
	protected function get_update_media_args() {
		return array(
			'id' => array(
				'description' => 'Unique identifier for the media item.',
				'type'        => 'integer',
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for retrieving media
	 *
	 * @return array
	 */
	protected function get_media_args() {
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
			'author'     => array(
				'description' => 'Limit result set to attachments assigned to specific authors.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'post'       => array(
				'description' => 'Limit result set to attachments assigned to specific parent posts.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => false,
			),
			'media_type' => array(
				'description' => 'Limit result set to attachments of a particular media type.',
				'type'        => 'string',
				'enum'        => array( 'image', 'video', 'audio', 'application' ),
				'required'    => false,
			),
			'mime_type'  => array(
				'description' => 'Limit result set to attachments of a particular MIME type.',
				'type'        => 'string',
				'required'    => false,
			),
			'orderby'    => array(
				'description' => 'Sort collection by object attribute.',
				'type'        => 'string',
				'default'     => 'date',
				'enum'        => array( 'date', 'title', 'modified', 'author', 'menu_order' ),
				'required'    => false,
			),
			'order'      => array(
				'description' => 'Order sort attribute ascending or descending.',
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
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
	 * Get arguments for batch updating media
	 *
	 * @return array
	 */
	protected function get_batch_update_media_args() {
		return array(
			'media' => array(
				'description' => 'List of media items to update.',
				'type'        => 'array',
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->get_update_media_args(),
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get arguments for batch deleting media
	 *
	 * @return array
	 */
	protected function get_batch_delete_media_args() {
		return array(
			'ids'   => array(
				'description' => 'List of media IDs to delete.',
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'required'    => true,
			),
		);
	}

	/**
	 * Get media schema
	 *
	 * @return array
	 */
	public function get_media_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'attachment',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => 'Unique identifier for the media item.',
					'type'        => 'integer',
					'readonly'    => true,
				),
				'date'          => array(
					'description' => 'The date the media item was uploaded.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_gmt'      => array(
					'description' => 'The date the media item was uploaded, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'modified'      => array(
					'description' => 'The date the media item was last modified.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'modified_gmt'  => array(
					'description' => 'The date the media item was last modified, as GMT.',
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'slug'          => array(
					'description' => 'The slug of the media item.',
					'type'        => 'string',
				),
				'status'        => array(
					'description' => 'The status of the media item.',
					'type'        => 'string',
					'enum'        => array( 'inherit', 'private', 'trash' ),
				),
				'type'          => array(
					'description' => 'The type of the post.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'link'          => array(
					'description' => 'The URL to the media item.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'title'         => array(
					'description' => 'The title of the media item.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Title for the media item, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML title for the media item, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'caption'       => array(
					'description' => 'The caption of the media item.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Caption for the media item, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML caption for the media item, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'description'   => array(
					'description' => 'The description of the media item.',
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => 'Description for the media item, as it exists in the database.',
							'type'        => 'string',
						),
						'rendered' => array(
							'description' => 'HTML description for the media item, transformed for display.',
							'type'        => 'string',
							'readonly'    => true,
						),
					),
				),
				'alt_text'      => array(
					'description' => 'Alternative text to describe the media item.',
					'type'        => 'string',
				),
				'author'        => array(
					'description' => 'The ID of the user who uploaded the media item.',
					'type'        => 'integer',
				),
				'post'          => array(
					'description' => 'The ID of the associated post.',
					'type'        => 'integer',
				),
				'source_url'    => array(
					'description' => 'URL to the original media file.',
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'mime_type'     => array(
					'description' => 'The MIME type of the media item.',
					'type'        => 'string',
					'readonly'    => true,
				),
				'media_type'    => array(
					'description' => 'The media type of the media item.',
					'type'        => 'string',
					'enum'        => array( 'image', 'video', 'audio', 'file' ),
					'readonly'    => true,
				),
				'media_details' => array(
					'description' => 'Details about the media file, specific to its type.',
					'type'        => 'object',
					'readonly'    => true,
				),
				'meta'          => array(
					'description' => 'Meta fields.',
					'type'        => 'object',
				),
			),
		);
	}

	/**
	 * Get batch schema.
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
	 * Validate file content matches expected MIME type for security.
	 *
	 * @param string $file_data Binary file data.
	 * @param string $filename Filename to validate against.
	 * @return bool|string True if valid, error message if invalid.
	 */
	private function validate_file_content( $file_data, $filename ) {
		// Get WordPress allowed MIME types.
		$allowed_mimes = get_allowed_mime_types();

		// Detect actual MIME type from file content.
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( ! $finfo ) {
			// If finfo is not available, skip content validation.
			return true;
		}

		$detected_mime = finfo_buffer( $finfo, $file_data );
		finfo_close( $finfo );

		if ( ! $detected_mime ) {
			return __( 'Could not determine file type.', 'sg-ai-studio' );
		}

		// Check if detected MIME type is in WordPress allowed types.
		if ( ! in_array( $detected_mime, $allowed_mimes, true ) ) {
			return sprintf(
				/* translators: %s: detected MIME type */
				__( 'File type "%s" is not allowed by WordPress.', 'sg-ai-studio' ),
				$detected_mime
			);
		}

		// Get expected extension from filename.
		$file_parts = pathinfo( $filename );
		$extension  = isset( $file_parts['extension'] ) ? strtolower( $file_parts['extension'] ) : '';

		if ( empty( $extension ) ) {
			return __( 'File has no extension.', 'sg-ai-studio' );
		}

		// Find expected MIME type(s) for this extension.
		$expected_mimes = array();
		foreach ( $allowed_mimes as $exts => $mime ) {
			$ext_array = explode( '|', $exts );
			if ( in_array( $extension, $ext_array, true ) ) {
				// Some entries have multiple MIME types separated by |.
				$mime_array = explode( '|', $mime );
				$expected_mimes = array_merge( $expected_mimes, $mime_array );
			}
		}

		if ( empty( $expected_mimes ) ) {
			return sprintf(
				/* translators: %s: file extension */
				__( 'File extension "%s" is not allowed.', 'sg-ai-studio' ),
				$extension
			);
		}

		// Verify detected MIME matches one of the expected MIME types for this extension.
		if ( ! in_array( $detected_mime, $expected_mimes, true ) ) {
			return sprintf(
				/* translators: 1: file extension, 2: detected MIME type, 3: expected MIME types */
				__( 'File content mismatch. File with extension "%1$s" has content type "%2$s" but expected one of: %3$s', 'sg-ai-studio' ),
				$extension,
				$detected_mime,
				implode( ', ', $expected_mimes )
			);
		}

		// Additional validation for images to ensure they're valid image files.
		if ( strpos( $detected_mime, 'image/' ) === 0 ) {
			$image_info = getimagesizefromstring( $file_data );
			if ( false === $image_info || ! is_array( $image_info ) ) {
				return __( 'File claims to be an image but is not a valid image format.', 'sg-ai-studio' );
			}
		}

		return true;
	}

	/**
	 * Validate file extension to prevent uploading executable files.
	 *
	 * @param string $filename The filename to validate.
	 * @return bool|string True if valid, error message if invalid.
	 */
	private function validate_file_extension( $filename ) {
		// List of forbidden executable extensions.
		$forbidden_extensions = array(
			'php',
			'php3',
			'php4',
			'php5',
			'php7',
			'phtml',
			'phar',
			'sh',
			'bash',
			'zsh',
			'csh',
			'ksh',
			'exe',
			'com',
			'bat',
			'cmd',
			'msi',
			'app',
			'deb',
			'rpm',
			'py',
			'pyc',
			'pyo',
			'rb',
			'pl',
			'cgi',
			'jar',
			'war',
			'js',
			'jsx',
			'ts',
			'tsx',
			'vbs',
			'vbe',
			'wsf',
			'wsh',
			'scr',
			'dll',
			'so',
			'dylib',
			'bin',
			'run',
			'out',
			'elf',
			'ipa',
			'apk',
			'ps1',
			'psm1',
		);

		// Get file extension.
		$file_parts = pathinfo( $filename );
		$extension  = isset( $file_parts['extension'] ) ? strtolower( $file_parts['extension'] ) : '';

		// Check if extension is forbidden.
		if ( in_array( $extension, $forbidden_extensions, true ) ) {
			return sprintf(
				/* translators: %s: file extension */
				__( 'File type "%s" is not allowed for security reasons. Executable files cannot be uploaded.', 'sg-ai-studio' ),
				$extension
			);
		}

		// Check for double extensions (e.g., file.php.jpg).
		$full_filename = isset( $file_parts['filename'] ) ? $file_parts['filename'] : '';
		foreach ( $forbidden_extensions as $forbidden_ext ) {
			if ( preg_match( '/\.' . preg_quote( $forbidden_ext, '/' ) . '$/i', $full_filename ) ) {
				return sprintf(
					/* translators: %s: file extension */
					__( 'File contains forbidden extension "%s" in the filename. Executable files cannot be uploaded.', 'sg-ai-studio' ),
					$forbidden_ext
				);
			}
		}

		return true;
	}

	/**
	 * Upload a new media file
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function upload_media( $request ) {
		// Check if file or file_url is provided.
		if ( empty( $request['file'] ) && empty( $request['file_url'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Please provide either file data or a file URL.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Include necessary files.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$file_array    = array();
		$upload_result = null;

		if ( ! empty( $request['file'] ) ) {
			// Handle base64 encoded file.
			$file_data = base64_decode( $request['file'] );
			if ( ! $file_data ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Invalid file data.', 'sg-ai-studio' ),
					),
					400
				);
			}

			$filename = isset( $request['filename'] ) ? sanitize_file_name( $request['filename'] ) : 'upload.bin';

			// Validate file extension for security.
			$validation_result = $this->validate_file_extension( $filename );
			if ( true !== $validation_result ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $validation_result,
					),
					400
				);
			}

			// Validate file content matches expected MIME type.
			$content_validation = $this->validate_file_content( $file_data, $filename );
			if ( true !== $content_validation ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $content_validation,
					),
					400
				);
			}

			// Create temporary file.
			$tmp_file = wp_tempnam( $filename );
			file_put_contents( $tmp_file, $file_data );

			$file_array = array(
				'name'     => $filename,
				'tmp_name' => $tmp_file,
				'error'    => 0,
				'size'     => filesize( $tmp_file ),
			);

		} elseif ( ! empty( $request['file_url'] ) ) {
			// Handle file URL.
			$file_url = esc_url_raw( $request['file_url'] );
			// Strip query parameters from URL before extracting filename.
			$url_without_params = strtok( $file_url, '?' );
			$filename           = isset( $request['filename'] ) ? sanitize_file_name( $request['filename'] ) : basename( $url_without_params );

			// Validate file extension for security.
			$validation_result = $this->validate_file_extension( $filename );
			if ( true !== $validation_result ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $validation_result,
					),
					400
				);
			}

			// Download file from URL.
			$response = wp_remote_get( $file_url );
			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $response->get_error_message(),
					),
					400
				);
			}

			$file_data = wp_remote_retrieve_body( $response );
			if ( empty( $file_data ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Could not download file from URL.', 'sg-ai-studio' ),
					),
					400
				);
			}

			// Validate file content matches expected MIME type.
			$content_validation = $this->validate_file_content( $file_data, $filename );
			if ( true !== $content_validation ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $content_validation,
					),
					400
				);
			}

			// Create temporary file.
			$tmp_file = wp_tempnam( $filename );
			file_put_contents( $tmp_file, $file_data );

			$file_array = array(
				'name'     => $filename,
				'tmp_name' => $tmp_file,
				'error'    => 0,
				'size'     => filesize( $tmp_file ),
			);
		}

		// Upload the file.
		$upload_result = wp_handle_sideload( $file_array, array( 'test_form' => false ) );

		// Clean up temporary file.
		if ( isset( $tmp_file ) && file_exists( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
		}

		if ( isset( $upload_result['error'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $upload_result['error'],
				),
				400
			);
		}

		// Prepare attachment data.
		$attachment_data = array(
			'post_mime_type' => $upload_result['type'],
			'post_title'     => isset( $request['title'] ) ? sanitize_text_field( $request['title'] ) : '',
			'post_content'   => isset( $request['description'] ) ? wp_kses_post( $request['description'] ) : '',
			'post_excerpt'   => isset( $request['caption'] ) ? wp_kses_post( $request['caption'] ) : '',
			'post_status'    => 'inherit',
		);

		if ( isset( $request['post'] ) ) {
			$attachment_data['post_parent'] = absint( $request['post'] );
		}

		// Insert the attachment.
		$attachment_id = wp_insert_attachment( $attachment_data, $upload_result['file'] );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $attachment_id->get_error_message(),
				),
				500
			);
		}

		// Generate attachment metadata.
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $upload_result['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

		// Handle alt text if provided.
		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $attachment_id, $meta_key, $meta_value );
			}
		}

		// Get the attachment.
		$attachment = get_post( $attachment_id );

		// Log the activity.
		/* translators: %1$s is the media title, %2$d is the media ID. */
		Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Media Uploaded: %1$s (Media ID: %2$d)', 'sg-ai-studio' ), $attachment->post_title, $attachment_id ) );

		// Format the response.
		$response = $this->prepare_media_for_response( $attachment );

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
	 * Update an existing media item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_media( $request ) {
		$media_id = $request['id'];
		$media    = get_post( $media_id );

		if ( ! $media || 'attachment' !== $media->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid media ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Prepare attachment data.
		$attachment_data = array(
			'ID' => $media_id,
		);

		if ( isset( $request['title'] ) ) {
			$attachment_data['post_title'] = sanitize_text_field( $request['title'] );
		}

		if ( isset( $request['description'] ) ) {
			$attachment_data['post_content'] = wp_kses_post( $request['description'] );
		}

		if ( isset( $request['caption'] ) ) {
			$attachment_data['post_excerpt'] = wp_kses_post( $request['caption'] );
		}

		if ( isset( $request['post'] ) ) {
			$attachment_data['post_parent'] = absint( $request['post'] );
		}

		// Update the attachment.
		$updated = wp_update_post( $attachment_data, true );

		if ( is_wp_error( $updated ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $updated->get_error_message(),
				),
				207
			);
		}

		// Handle alt text if provided.
		if ( isset( $request['alt_text'] ) ) {
			update_post_meta( $media_id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
		}

		// Handle meta fields if provided.
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
			foreach ( $request['meta'] as $meta_key => $meta_value ) {
				update_post_meta( $media_id, $meta_key, $meta_value );
			}
		}

		// Get the updated attachment.
		$media = get_post( $media_id );

		// Log the activity.
		/* translators: %1$s is the media title, %2$d is the media ID. */
		Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Media Updated: %1$s (Media ID: %2$d)', 'sg-ai-studio' ), $media->post_title, $media_id ) );

		// Format the response.
		$response = $this->prepare_media_for_response( $media );

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
	 * Delete a media item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_media( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$media_id = $request['id'];
		$force    = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$media    = get_post( $media_id );

		if ( ! $media || 'attachment' !== $media->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid media ID.', 'sg-ai-studio' ),
				),
				207
			);
		}

		// Get the media before deleting it.
		$previous = $this->prepare_media_for_response( $media );

		// Delete the media.
		$result = wp_delete_attachment( $media_id, $force );

		if ( ! $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'The media item could not be deleted.', 'sg-ai-studio' ),
				),
				207
			);
		}

		// Log the activity.
		$media_title = $previous['title']['raw'] ? $previous['title']['raw'] : "Media ID: {$media_id}";
		if ( $force ) {
			/* translators: %1$s is the media title, %2$d is the media ID. */
			Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Media Permanently Deleted: %1$s (Media ID: %2$d)', 'sg-ai-studio' ), $media_title, $media_id ) );
		} else {
			/* translators: %1$s is the media title, %2$d is the media ID. */
			Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Media Moved to Trash: %1$s (Media ID: %2$d)', 'sg-ai-studio' ), $media_title, $media_id ) );
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
					'message' => __( 'The media item has been permanently deleted.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'The media item has been moved to the trash.', 'sg-ai-studio' ),
					'data'    => $previous,
				),
				200
			);
		}
	}

	/**
	 * Get a list of media items
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_media( $request ) {
		// Prepare query arguments.
		$args = array(
			'post_type'      => 'attachment',
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
			'orderby'        => $request['orderby'],
			'order'          => $request['order'],
			'post_status'    => 'inherit',
		);

		// Handle search parameter.
		if ( isset( $request['search'] ) && ! empty( $request['search'] ) ) {
			$args['s'] = $request['search'];
		}

		// Handle author parameter.
		if ( isset( $request['author'] ) && ! empty( $request['author'] ) ) {
			$args['author__in'] = $request['author'];
		}

		// Handle post parent parameter.
		if ( isset( $request['post'] ) && ! empty( $request['post'] ) ) {
			$args['post_parent__in'] = $request['post'];
		}

		// Handle media type parameter.
		if ( isset( $request['media_type'] ) && ! empty( $request['media_type'] ) ) {
			$mime_types = array();
			switch ( $request['media_type'] ) {
				case 'image':
					$mime_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
					break;
				case 'video':
					$mime_types = array( 'video/mp4', 'video/avi', 'video/mov', 'video/wmv' );
					break;
				case 'audio':
					$mime_types = array( 'audio/mp3', 'audio/wav', 'audio/ogg' );
					break;
				case 'application':
					$mime_types = array( 'application/pdf', 'application/zip', 'application/msword' );
					break;
			}
			if ( ! empty( $mime_types ) ) {
				$args['post_mime_type'] = $mime_types;
			}
		}

		// Handle mime type parameter.
		if ( isset( $request['mime_type'] ) && ! empty( $request['mime_type'] ) ) {
			$args['post_mime_type'] = $request['mime_type'];
		}

		// Handle include parameter.
		if ( isset( $request['include'] ) && ! empty( $request['include'] ) ) {
			$args['post__in'] = $request['include'];
		}

		// Handle exclude parameter.
		if ( isset( $request['exclude'] ) && ! empty( $request['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			$args['post__not_in'] = $request['exclude'];
		}

		// Get media items.
		$query       = new WP_Query( $args );
		$media_items = $query->posts;

		// Format the response.
		$data = array();
		foreach ( $media_items as $media ) {
			$data[] = $this->prepare_media_for_response( $media );
		}

		// Prepare pagination headers.
		$total_items = $query->found_posts;
		$max_pages   = $query->max_num_pages;

		$response = new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'media'       => $data,
					'total'       => $total_items,
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
	 * Get a single media item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_media_item( $request ) {
		$media_id = $request['id'];
		$media    = get_post( $media_id );

		if ( ! $media || 'attachment' !== $media->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid media ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Format the response.
		$response = $this->prepare_media_for_response( $media );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response,
			),
			200
		);
	}

	/**
	 * Batch update media items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_update_media( $request ) {
		$media_items = $request['media'];
		$results     = array();
		$errors      = array();

		foreach ( $media_items as $key => $media_data ) {
			if ( ! isset( $media_data['id'] ) ) {
				$errors[ $key ] = array(
					'success' => false,
					'message' => __( 'Media ID is required for updating media items.', 'sg-ai-studio' ),
				);
				continue;
			}

			// Create a new request for each media item.
			$sub_request = new WP_REST_Request( 'PUT', '/' . $this->namespace . '/' . $this->base . '/' . $media_data['id'] );

			// Add media data to the request.
			foreach ( $media_data as $param_key => $param_value ) {
				$sub_request->set_param( $param_key, $param_value );
			}

			// Update the media item.
			$response = $this->update_media( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $key ] = $response->get_data();
			} else {
				$results[ $key ] = $response->get_data()['data'];
			}
		}

		$success = empty( $errors );

		// Log the batch activity.
		$updated_count = count( $results );
		$error_count   = count( $errors );
		/* translators: %1$d is the number of media items updated, %2$d is the number of errors. */
		Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Batch Media Update: %1$d updated, %2$d errors', 'sg-ai-studio' ), $updated_count, $error_count ) );

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
	 * Batch delete media items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function batch_delete_media( $request ) {
		if ( ! get_option( 'sg_ai_studio_powermode', false ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Powermode is disabled. This operation is not allowed.', 'sg-ai-studio' ),
				),
				412
			);
		}
		$ids     = $request['ids'];
		$force   = isset( $request['force'] ) ? \SG_AI_Studio\Helper\Helper::validate_force_param( $request['force'] ) : false;
		$results = array();
		$errors  = array();

		foreach ( $ids as $key => $media_id ) {
			// Create a new request for each media item.
			$sub_request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/' . $this->base . '/' . $media_id );
			$sub_request->set_param( 'id', $media_id );
			$sub_request->set_param( 'force', $force );

			// Delete the media item.
			$response = $this->delete_media( $sub_request );

			if ( $response->is_error() || ! $response->get_data()['success'] ) {
				$errors[ $media_id ] = $response->get_data();
			} else {
				$results[ $media_id ] = $response->get_data()['message'];
			}
		}

		$success = empty( $errors );

		// Log the batch activity.
		$deleted_count = count( $results );
		$error_count   = count( $errors );
		if ( $force ) {
			/* translators: %1$d is the number of media items deleted, %2$d is the number of errors. */
			Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Batch Media Permanently Deleted: %1$d deleted, %2$d errors', 'sg-ai-studio' ), $deleted_count, $error_count ) );
		} else {
			/* translators: %1$d is the number of media items moved to trash, %2$d is the number of errors. */
			Activity_Log_Helper::add_log_entry( 'Media', sprintf( __( 'Batch Media Moved to Trash: %1$d deleted, %2$d errors', 'sg-ai-studio' ), $deleted_count, $error_count ) );
		}

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
	 * Prepare a media item for the response.
	 *
	 * @param \WP_Post $media Media object.
	 * @return array Prepared media data.
	 */
	protected function prepare_media_for_response( $media ) {
		// Get alt text.
		$alt_text = get_post_meta( $media->ID, '_wp_attachment_image_alt', true );

		// Get media details.
		$media_details = wp_get_attachment_metadata( $media->ID );

		// Determine media type.
		$mime_type  = $media->post_mime_type;
		$media_type = 'file';
		if ( strpos( $mime_type, 'image/' ) === 0 ) {
			$media_type = 'image';
		} elseif ( strpos( $mime_type, 'video/' ) === 0 ) {
			$media_type = 'video';
		} elseif ( strpos( $mime_type, 'audio/' ) === 0 ) {
			$media_type = 'audio';
		}

		// Prepare response.
		$data = array(
			'id'            => $media->ID,
			'date'          => mysql_to_rfc3339( $media->post_date ),
			'date_gmt'      => mysql_to_rfc3339( $media->post_date_gmt ),
			'modified'      => mysql_to_rfc3339( $media->post_modified ),
			'modified_gmt'  => mysql_to_rfc3339( $media->post_modified_gmt ),
			'slug'          => $media->post_name,
			'status'        => $media->post_status,
			'type'          => $media->post_type,
			'link'          => get_permalink( $media->ID ),
			'title'         => array(
				'raw'      => $media->post_title,
				'rendered' => get_the_title( $media->ID ),
			),
			'caption'       => array(
				'raw'      => $media->post_excerpt,
				'rendered' => apply_filters( 'the_excerpt', $media->post_excerpt ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			),
			'description'   => array(
				'raw'      => $media->post_content,
				'rendered' => apply_filters( 'the_content', $media->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			),
			'alt_text'      => $alt_text,
			'author'        => (int) $media->post_author,
			'post'          => (int) $media->post_parent,
			'source_url'    => wp_get_attachment_url( $media->ID ),
			'mime_type'     => $mime_type,
			'media_type'    => $media_type,
			'media_details' => $media_details ? $media_details : new \stdClass(),
		);

		return $data;
	}
}
