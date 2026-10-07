<?php
/**
 * Gutenberg API class for AI-powered content operations
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Rest;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use SG_AI_Studio\Helper\Helper;

/**
 * Handles REST API endpoints for Gutenberg AI operations.
 */
class Gutenberg extends Rest_Controller_Base {
	/**
	 * REST API base
	 *
	 * @var string
	 */
	private $base = 'gutenberg';


	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		// Register endpoint for text editing.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/edit-text',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'edit_text' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_edit_text_args(),
				'description'         => 'Processes text with AI based on custom or predefined prompts.',
			)
		);

		// Register endpoint for image generation.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/generate-image',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_image' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_generate_image_args(),
				'description'         => 'Generates an image based on AI description and aspect ratio.',
			)
		);

		// Register endpoint for block generation.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/generate-block',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_block' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_generate_block_args(),
				'description'         => 'Generates Gutenberg block(s) based on AI prompt.',
			)
		);

		// Register endpoint for deleting generated images.
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/delete-image',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_image' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->get_delete_image_args(),
				'description'         => 'Deletes an AI-generated image from the media library.',
			)
		);
	}

	/**
	 * Check if a user has permission to edit text
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function permissions_check( $request ) {
		if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sg_ai_studio_gutenberg_nonce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid security token.', 'sg-ai-studio' ),
				array( 'status' => 403 )
			);
		}

		if ( ! \current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to edit content.', 'sg-ai-studio' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get arguments for text editing.
	 *
	 * @return array
	 */
	protected function get_edit_text_args() {
		return array(
			'text'          => array(
				'description' => 'The original text to process (can be empty for generation mode).',
				'type'        => 'string',
				'required'    => false,
				'default'     => '',
			),
			'prompt'        => array(
				'description' => 'The AI instruction or prompt.',
				'type'        => 'string',
				'required'    => true,
			),
			'action_type'   => array(
				'description' => 'Type of action (custom, improve, fix-grammar, make-shorter, make-longer).',
				'type'        => 'string',
				'enum'        => array( 'custom', 'improve', 'fix-grammar', 'make-shorter', 'make-longer' ),
				'default'     => 'custom',
				'required'    => false,
			),
			'post_id'       => array(
				'description' => 'The WordPress post ID to attach generated content to.',
				'type'        => 'integer',
				'required'    => false,
				'default'     => 0,
			),
		);
	}

	/**
	 * Get arguments for image generation.
	 *
	 * @return array
	 */
	protected function get_generate_image_args() {
		return array(
			'description'   => array(
				'description' => 'Description of the image to generate.',
				'type'        => 'string',
				'required'    => true,
			),
			'aspect_ratio'  => array(
				'description' => 'Aspect ratio for the generated image.',
				'type'        => 'string',
				'enum'        => array( 'square', 'landscape', 'portrait', 'wide' ),
				'default'     => 'square',
				'required'    => false,
			),
			'post_id'       => array(
				'description' => 'The WordPress post ID to attach the generated image to.',
				'type'        => 'integer',
				'required'    => false,
				'default'     => 0,
			),
		);
	}

	/**
	 * Get arguments for block generation.
	 *
	 * @return array
	 */
	protected function get_generate_block_args() {
		return array(
			'prompt'  => array(
				'description' => 'Description of the block(s) to generate.',
				'type'        => 'string',
				'required'    => true,
			),
			'post_id' => array(
				'description' => 'The WordPress post ID context.',
				'type'        => 'integer',
				'required'    => false,
				'default'     => 0,
			),
		);
	}

	/**
	 * Get arguments for image deletion.
	 *
	 * @return array
	 */
	protected function get_delete_image_args() {
		return array(
			'image_id' => array(
				'description' => 'The WordPress attachment ID of the image to delete.',
				'type'        => 'integer',
				'required'    => true,
			),
		);
	}

	/**
	 * Edit text using AI
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function edit_text( $request ) {
		$text        = $request->get_param( 'text' );
		$prompt      = $request->get_param( 'prompt' );
		$action_type = $request->get_param( 'action_type' );

		// Validate required parameters.
		// Note: text can be empty for content generation mode
		if ( empty( $prompt ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Prompt is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Process text with AI service (text can be empty for generation).
		$edited_text = $this->process_text_with_ai( $text, $prompt, $action_type );

		// Check if processing returned an error.
		if ( \is_wp_error( $edited_text ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $edited_text->get_error_message(),
					'code'    => $edited_text->get_error_code(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'original_text' => $text,
					'edited_text'   => $edited_text,
					'prompt'        => $prompt,
					'action_type'   => $action_type,
				),
			),
			200
		);
	}

	/**
	 * Generate image using AI
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function generate_image( $request ) {
		$description  = $request->get_param( 'description' );
		$aspect_ratio = $request->get_param( 'aspect_ratio' );
		$post_id      = $request->get_param( 'post_id' ) ?: 0;

		// Validate required parameters.
		if ( empty( $description ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Description is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Generate image using AI service.
		$image_data = $this->generate_image_with_ai( $description, $aspect_ratio, $post_id );

		// Check if generation returned an error.
		if ( \is_wp_error( $image_data ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $image_data->get_error_message(),
					'code'    => $image_data->get_error_code(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'id'           => $image_data['id'],
					'url'          => $image_data['url'],
					'description'  => $description,
					'aspect_ratio' => $aspect_ratio,
				),
			),
			201
		);
	}

	/**
	 * Generate Gutenberg block(s) using AI
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function generate_block( $request ) {
		$prompt  = $request->get_param( 'prompt' );
		$post_id = $request->get_param( 'post_id' ) ?: 0;

		// Validate required parameters.
		if ( empty( $prompt ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Prompt is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Generate block markup using AI service.
		$block_markup = $this->generate_block_with_ai( $prompt, $post_id );

		// Check if generation returned an error.
		if ( \is_wp_error( $block_markup ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $block_markup->get_error_message(),
					'code'    => $block_markup->get_error_code(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'block_markup' => $block_markup,
					'prompt'       => $prompt,
				),
			),
			201
		);
	}

	/**
	 * Delete an AI-generated image from the media library
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_image( $request ) {
		$image_id = $request->get_param( 'image_id' );

		// Validate required parameters.
		if ( empty( $image_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Image ID is required.', 'sg-ai-studio' ),
				),
				400
			);
		}

		// Verify the attachment exists and user has permission to delete it.
		$attachment = \get_post( $image_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid image ID.', 'sg-ai-studio' ),
				),
				404
			);
		}

		// Delete the attachment (force delete, not trash).
		$deleted = \wp_delete_attachment( $image_id, true );

		if ( false === $deleted ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Failed to delete the image.', 'sg-ai-studio' ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Image deleted successfully.', 'sg-ai-studio' ),
				'data'    => array(
					'id' => $image_id,
				),
			),
			200
		);
	}

	/**
	 * Process text with AI
	 *
	 * @param string $text        Original text.
	 * @param string $prompt      AI prompt.
	 * @param string $action_type Type of action.
	 * @return string|\WP_Error Processed text or WP_Error on failure.
	 */
	private function process_text_with_ai( $text, $prompt, $action_type ) {
		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_Error(
				'auth_failed',
				\__( 'Failed to generate authentication token.', 'sg-ai-studio' )
			);
		}

		// Build the message to send to AI.
		$message = $this->build_text_message( $text, $prompt, $action_type );

		// Process request using Helper class (handles NDJSON response parsing).
		$result = Helper::process_chat_request( $message, $auth_token );

		// Check if processing failed.
		if ( ! $result['success'] ) {
			return new \WP_Error(
				'api_request_failed',
				\sprintf(
					/* translators: %s is the error message from the AI request. */
					\__( 'AI request failed: %s', 'sg-ai-studio' ),
					$result['message']
				)
			);
		}

		// Check if we have a reply.
		if ( empty( $result['reply'] ) ) {
			return new \WP_Error(
				'empty_response',
				\__( 'AI service returned an empty response.', 'sg-ai-studio' )
			);
		}

		return $result['reply'];
	}

	/**
	 * Build message for text editing AI request.
	 *
	 * @param string $text        Original text.
	 * @param string $prompt      AI prompt.
	 * @param string $action_type Type of action.
	 * @return string Formatted message.
	 */
	private function build_text_message( $text, $prompt, $action_type ) {
		$instruction = '';

		// Check if this is a generation request (empty text).
		$is_generation = empty( $text );

		switch ( $action_type ) {
			case 'improve':
				$instruction = 'Improve this text by enhancing clarity, professionalism, and readability.';
				break;

			case 'fix-grammar':
				$instruction = 'Fix all spelling and grammar errors in this text.';
				break;

			case 'make-shorter':
				$instruction = 'Make this text shorter while keeping the main message.';
				break;

			case 'make-longer':
				$instruction = 'Expand this text with additional context and details.';
				break;

			case 'custom':
			default:
				$instruction = $prompt;
				break;
		}

		// For generation mode, just return the prompt as instruction.
		if ( $is_generation ) {
			return \sprintf(
				"%s\n\nPlease return only the generated content without any additional commentary or explanation.",
				$instruction
			);
		}

		// For edit mode, include the text to edit.
		return \sprintf(
			"%s\n\nText to edit:\n%s\n\nPlease return only the edited text without any additional commentary or explanation.",
			$instruction,
			$text
		);
	}

	/**
	 * Generate image with AI
	 *
	 * @param string  $description  Image description.
	 * @param string  $aspect_ratio Aspect ratio.
	 * @param integer $post_id      Post ID to attach image to.
	 * @return array|\WP_Error Image data array or WP_Error on failure.
	 */
	private function generate_image_with_ai( $description, $aspect_ratio, $post_id = 0 ) {
		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_Error(
				'auth_failed',
				\__( 'Failed to generate authentication token.', 'sg-ai-studio' )
			);
		}

		// Map aspect ratio to dimensions for the prompt.
		$aspect_ratio_map = array(
			'square'    => '1:1 (square)',
			'landscape' => '16:9 (landscape)',
			'portrait'  => '9:16 (portrait)',
			'wide'      => '21:9 (wide)',
		);

		$aspect_description = isset( $aspect_ratio_map[ $aspect_ratio ] ) ? $aspect_ratio_map[ $aspect_ratio ] : '1:1 (square)';

		// Build the message to send to AI with image generation request.
		// Use a more explicit prompt to ensure the AI uses the image_generation tool
		$message = \sprintf(
			"I need you to generate an image using the image_generation tool.\n\nDescription: %s\nAspect ratio: %s\n\nIMPORTANT: You MUST use the image_generation tool to create this image. Do not provide placeholder images or describe the image - actually generate it using the tool.",
			$description,
			$aspect_description
		);

		// Process request using Helper class (handles image generation automatically).
		// Pass post_id to attach generated images to the post
		$result = Helper::process_chat_request( $message, $auth_token, '', '', $post_id );

		// Check if processing failed.
		if ( ! $result['success'] ) {
			return new \WP_Error(
				'api_request_failed',
				\sprintf(
					/* translators: %s is the error message from the AI request. */
					\__( 'AI request failed: %s', 'sg-ai-studio' ),
					$result['message']
				)
			);
		}

		// Check if images were generated.
		if ( empty( $result['image_ids'] ) || empty( $result['images'] ) ) {
			// Provide more detailed error message
			$debug_info = 'Result keys: ' . implode( ', ', array_keys( $result ) );
			if ( ! empty( $result['reply'] ) ) {
				$debug_info .= '; Reply: ' . substr( $result['reply'], 0, 200 );
			}

			return new \WP_Error(
				'no_image_generated',
				\sprintf(
					/* translators: %s is the debug information about the failed image generation. */
					\__( 'AI service did not generate any images. Debug: %s', 'sg-ai-studio' ),
					$debug_info
				)
			);
		}

		// Return the first generated image.
		$first_image = $result['images'][0];

		return array(
			'id'  => $first_image['id'],
			'url' => $first_image['url'],
		);
	}

	/**
	 * Generate Gutenberg block markup with AI
	 *
	 * @param string  $prompt  User's block description.
	 * @param integer $post_id Post ID context.
	 * @return string|\WP_Error Block markup string or WP_Error on failure.
	 */
	private function generate_block_with_ai( $prompt, $post_id = 0 ) {
		$auth_token = Helper::generate_ai_studio_token();

		if ( false === $auth_token ) {
			return new \WP_Error(
				'auth_failed',
				\__( 'Failed to generate authentication token.', 'sg-ai-studio' )
			);
		}

		// Build enhanced prompt for block generation.
		$message = \sprintf(
			"Create Gutenberg block(s) for: %s\n\nCRITICAL Rules - Follow EXACTLY:\n\n1. Return ONLY valid Gutenberg block markup\n2. Every block: opening comment (<!-- wp:blockname -->), HTML, closing comment (<!-- /wp:blockname -->)\n3. NEVER self-closing blocks (<!-- wp:blockname /-->)\n4. Match WordPress core block structure EXACTLY\n5. HTML classes must match the JSON attributes EXACTLY\n\n## Image Blocks (if needed):\n- Use image_generation tool to create images\n- Format:\n  <!-- wp:image {\"id\":1,\"sizeSlug\":\"large\"} -->\n  <figure class=\"wp-block-image size-large\"><img src=\"url\" alt=\"description\" class=\"wp-image-1\"/></figure>\n  <!-- /wp:image -->\n\n## Cover Blocks (if needed):\n- Use image_generation tool for background\n- Format EXACTLY like this:\n  <!-- wp:cover {\"url\":\"image-url\",\"id\":1,\"dimRatio\":50,\"align\":\"full\"} -->\n  <div class=\"wp-block-cover alignfull\"><img class=\"wp-block-cover__image-background wp-image-1\" alt=\"\" src=\"image-url\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim\"></span><div class=\"wp-block-cover__inner-container\">\n  <!-- Inner blocks here -->\n  </div></div>\n  <!-- /wp:cover -->\n- Key rules for cover:\n  * Use <img> tag, NOT background-image style\n  * dimRatio in JSON must match has-background-dim-XX class (or omit class if 50)\n  * Don't add color classes unless in JSON attributes\n  * Keep structure simple and clean\n\n## Standard Block Examples:\n\n<!-- wp:paragraph -->\n<p>Text here.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":2} -->\n<h2 class=\"wp-block-heading\">Heading</h2>\n<!-- /wp:heading -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><li>Item</li></ul>\n<!-- /wp:list -->\n\n<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column --><div class=\"wp-block-column\"></div><!-- /wp:column --></div>\n<!-- /wp:columns -->\n\n<!-- wp:group -->\n<div class=\"wp-block-group\"></div>\n<!-- /wp:group -->\n\n<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button --><div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\">Text</a></div><!-- /wp:button --></div>\n<!-- /wp:buttons -->\n\n## Important:\n- Copy standard WordPress block HTML structure EXACTLY\n- Don't invent classes not in WordPress core\n- JSON attributes must match HTML classes perfectly\n- Keep it simple and standard\n- No extra styling unless requested\n\nOutput ONLY the block markup, nothing else.",
			$prompt
		);

		// Process request using Helper class specialized method for Gutenberg blocks.
		$result = Helper::process_gutenberg_block_request( $message, $auth_token, '', '', $post_id );

		// Check if processing failed.
		if ( ! $result['success'] ) {
			return new \WP_Error(
				'api_request_failed',
				\sprintf(
					/* translators: %s is the error message from the AI request. */
					\__( 'AI request failed: %s', 'sg-ai-studio' ),
					$result['message']
				)
			);
		}

		// Check if we have a reply.
		if ( empty( $result['reply'] ) ) {
			return new \WP_Error(
				'empty_response',
				\__( 'AI service returned an empty response.', 'sg-ai-studio' )
			);
		}

		$block_markup = $result['reply'];

		// Clean up any text before the first block comment (error messages, explanations, etc.)
		if ( preg_match( '/<!--\s*wp:/', $block_markup, $matches, PREG_OFFSET_CAPTURE ) ) {
			$first_block_pos = $matches[0][1];
			if ( $first_block_pos > 0 ) {
				$block_markup = substr( $block_markup, $first_block_pos );
			}
		}

		// Clean up deprecated or invalid CSS classes that cause validation errors
		$block_markup = $this->clean_block_markup( $block_markup );

		// Fix cover blocks that use old background-image format
		$block_markup = $this->fix_cover_block_markup( $block_markup );

		// If images were generated, update image IDs in the block markup
		if ( ! empty( $result['image_ids'] ) ) {
			$block_markup = $this->fix_image_ids_in_block_markup( $block_markup, $result['image_ids'] );
		}

		return $block_markup;
	}

	/**
	 * Clean up block markup by removing deprecated or invalid CSS classes
	 *
	 * @param string $block_markup The block markup to clean.
	 * @return string Cleaned block markup.
	 */
	private function clean_block_markup( $block_markup ) {
		// List of deprecated/invalid CSS classes that cause validation errors
		$deprecated_classes = array(
			'are-vertically-aligned-top',
			'are-vertically-aligned-center',
			'are-vertically-aligned-bottom',
		);

		// Remove deprecated classes from HTML class attributes
		foreach ( $deprecated_classes as $class ) {
			// Remove the class from class="..." attributes
			$block_markup = preg_replace(
				'/class="([^"]*)\b' . preg_quote( $class, '/' ) . '\b\s*([^"]*)"/i',
				'class="$1$2"',
				$block_markup
			);

			// Clean up any double spaces in class attributes
			$block_markup = preg_replace( '/class="([^"]*)\s{2,}([^"]*)"/i', 'class="$1 $2"', $block_markup );

			// Clean up trailing/leading spaces in class attributes
			$block_markup = preg_replace( '/class="\s+([^"]*)"/i', 'class="$1"', $block_markup );
			$block_markup = preg_replace( '/class="([^"]*)\s+"/i', 'class="$1"', $block_markup );
		}

		return $block_markup;
	}

	/**
	 * Fix cover blocks that use old background-image inline styles format
	 * Converts to new format with <img> tag
	 *
	 * @param string $block_markup The block markup to fix.
	 * @return string Fixed block markup.
	 */
	private function fix_cover_block_markup( $block_markup ) {
		// Fix cover blocks that use old format with background-image inline styles
		$block_markup = preg_replace_callback(
			'/<!--\s*wp:cover\s+({[^}]*"url"\s*:\s*"([^"]+)"[^}]*"id"\s*:\s*(\d+)[^}]*})\s*-->.*?<div\s+class="([^"]*wp-block-cover[^"]*)"[^>]*>.*?<\/div>\s*<!--\s*\/wp:cover\s*-->/s',
			function( $matches ) {
				$attributes       = $matches[1];
				$image_url        = $matches[2];
				$image_id         = $matches[3];
				$cover_classes    = $matches[4];

				// Extract the inner container content
				preg_match( '/<div\s+class="wp-block-cover__inner-container"[^>]*>(.*?)<\/div>\s*<\/div>\s*<!--\s*\/wp:cover/s', $matches[0], $inner_matches );
				$inner_content = isset( $inner_matches[1] ) ? $inner_matches[1] : '';

				// Get dimRatio from attributes
				$dim_ratio = 50; // default
				if ( preg_match( '/"dimRatio"\s*:\s*(\d+)/', $attributes, $dim_matches ) ) {
					$dim_ratio = $dim_matches[1];
				}

				// Build the correct cover block HTML
				$correct_html = sprintf(
					'<div class="%s"><img class="wp-block-cover__image-background wp-image-%d" alt="" src="%s" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">%s</div></div>',
					esc_attr( $cover_classes ),
					$image_id,
					esc_url( $image_url ),
					$inner_content
				);

				return sprintf(
					'<!-- wp:cover %s -->%s%s<!-- /wp:cover -->',
					$attributes,
					"\n",
					$correct_html . "\n"
				);
			},
			$block_markup
		);

		return $block_markup;
	}

	/**
	 * Fix image IDs in block markup to use actual WordPress attachment IDs
	 *
	 * @param string $block_markup The block markup with placeholder image IDs.
	 * @param array  $image_ids    Array of actual WordPress attachment IDs.
	 * @return string Block markup with corrected image IDs.
	 */
	private function fix_image_ids_in_block_markup( $block_markup, $image_ids ) {
		if ( empty( $image_ids ) ) {
			return $block_markup;
		}

		$attachment_ids_array = array_values( $image_ids );
		$current_index        = 0;

		// Build a map of original URLs to WordPress URLs
		$url_map = array();
		foreach ( $attachment_ids_array as $attachment_id ) {
			$wp_url = wp_get_attachment_url( $attachment_id );
			if ( $wp_url ) {
				// Store WordPress URL for later use
				$url_map[ $attachment_id ] = $wp_url;
			}
		}

		// First: Fix cover blocks with images
		$block_markup = preg_replace_callback(
			'/<!--\s*wp:cover\s+({[^}]*"url"\s*:\s*"([^"]+)"[^}]*"id"\s*:\s*\d+[^}]*})\s*-->/s',
			function( $matches ) use ( $attachment_ids_array, $url_map, &$current_index ) {
				if ( ! isset( $attachment_ids_array[ $current_index ] ) ) {
					return $matches[0];
				}

				$attachment_id = $attachment_ids_array[ $current_index ];
				$wp_url        = isset( $url_map[ $attachment_id ] ) ? $url_map[ $attachment_id ] : '';

				if ( ! $wp_url ) {
					return $matches[0];
				}

				// Replace the URL and ID in the attributes JSON
				$attributes         = $matches[1];
				$updated_attributes = preg_replace( '/"url"\s*:\s*"[^"]+"/', '"url":"' . esc_url( $wp_url ) . '"', $attributes );
				$updated_attributes = preg_replace( '/"id"\s*:\s*\d+/', '"id":' . $attachment_id, $updated_attributes );

				$current_index++;

				return '<!-- wp:cover ' . $updated_attributes . ' -->';
			},
			$block_markup
		);

		// Also replace src attributes in cover block img tags
		$cover_img_index = 0;
		$block_markup    = preg_replace_callback(
			'/<img\s+class="wp-block-cover__image-background\s+wp-image-\d+"[^>]*src="[^"]*"[^>]*>/s',
			function( $matches ) use ( $attachment_ids_array, $url_map, &$cover_img_index ) {
				if ( ! isset( $attachment_ids_array[ $cover_img_index ] ) ) {
					return $matches[0];
				}

				$attachment_id = $attachment_ids_array[ $cover_img_index ];
				$wp_url        = isset( $url_map[ $attachment_id ] ) ? $url_map[ $attachment_id ] : '';

				if ( ! $wp_url ) {
					return $matches[0];
				}

				$img_tag = $matches[0];
				// Replace src
				$img_tag = preg_replace( '/src="[^"]*"/', 'src="' . esc_url( $wp_url ) . '"', $img_tag );
				// Replace wp-image class
				$img_tag = preg_replace( '/wp-image-\d+/', 'wp-image-' . $attachment_id, $img_tag );

				$cover_img_index++;

				return $img_tag;
			},
			$block_markup
		);

		// Reset index for regular image blocks
		$current_index = 0;

		// First pass: Replace "id":X in block attributes and inject <img> tags
		$block_markup = preg_replace_callback(
			'/<!--\s*wp:image\s+({[^}]*"id"\s*:\s*\d+[^}]*})\s*-->\s*<figure[^>]*>(.*?)<\/figure>\s*<!--\s*\/wp:image\s*-->/s',
			function( $matches ) use ( $attachment_ids_array, &$current_index ) {
				if ( ! isset( $attachment_ids_array[ $current_index ] ) ) {
					return $matches[0];
				}

				$attachment_id = $attachment_ids_array[ $current_index ];
				$image_url     = wp_get_attachment_url( $attachment_id );
				$image_alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

				// Replace the ID in the attributes JSON
				$attributes       = $matches[1];
				$updated_attributes = preg_replace( '/"id"\s*:\s*\d+/', '"id":' . $attachment_id, $attributes );

				// Get the figure content and attributes
				$figure_content = $matches[2];
				preg_match( '/<figure([^>]*)>/', $matches[0], $figure_matches );
				$figure_attrs = isset( $figure_matches[1] ) ? $figure_matches[1] : '';

				// Replace wp-image-X class in figure attributes if present
				$figure_attrs = preg_replace( '/wp-image-\d+/', 'wp-image-' . $attachment_id, $figure_attrs );

				// Check if img tag already exists
				if ( strpos( $figure_content, '<img' ) === false ) {
					// No img tag - inject one
					$img_tag = sprintf(
						'<img src="%s" alt="%s" class="wp-image-%d"/>',
						esc_url( $image_url ),
						esc_attr( $image_alt ),
						$attachment_id
					);
					$figure_content = $img_tag . $figure_content;
				} else {
					// img tag exists - update src and class
					$figure_content = preg_replace_callback(
						'/<img([^>]*)>/',
						function( $img_matches ) use ( $image_url, $image_alt, $attachment_id ) {
							$img_attrs = $img_matches[1];
							// Update or add src
							if ( strpos( $img_attrs, 'src=' ) !== false ) {
								$img_attrs = preg_replace( '/src="[^"]*"/', 'src="' . esc_url( $image_url ) . '"', $img_attrs );
							} else {
								$img_attrs = ' src="' . esc_url( $image_url ) . '"' . $img_attrs;
							}
							// Update or add alt
							if ( strpos( $img_attrs, 'alt=' ) !== false ) {
								$img_attrs = preg_replace( '/alt="[^"]*"/', 'alt="' . esc_attr( $image_alt ) . '"', $img_attrs );
							} else {
								$img_attrs .= ' alt="' . esc_attr( $image_alt ) . '"';
							}
							// Update wp-image class
							$img_attrs = preg_replace( '/wp-image-\d+/', 'wp-image-' . $attachment_id, $img_attrs );
							return '<img' . $img_attrs . '>';
						},
						$figure_content
					);
				}

				$current_index++;

				// Reconstruct the block
				return sprintf(
					'<!-- wp:image %s -->%s<figure%s>%s</figure>%s<!-- /wp:image -->',
					$updated_attributes,
					"\n",
					$figure_attrs,
					$figure_content,
					"\n"
				);
			},
			$block_markup
		);

		return $block_markup;
	}
}
