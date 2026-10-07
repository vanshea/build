<?php
/**
 * Gutenberg class for managing Gutenberg editor functionality
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Gutenberg;

use SG_AI_Studio\Helper\Helper;

/**
 * Handle all Gutenberg editor related functionality.
 */
class Gutenberg {

	/**
	 * Enqueue editor assets for Gutenberg
	 *
	 * This method adds a custom button to all Gutenberg blocks that contain text.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Register the JavaScript file for the editor.
		wp_enqueue_script(
			'sg-ai-studio-gutenberg',
			\SG_AI_Studio\URL . '/assets/js/gutenberg.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose', 'wp-hooks', 'wp-i18n', 'wp-plugins', 'wp-edit-post', 'wp-data', 'wp-api-fetch' ),
			\SG_AI_Studio\VERSION,
			true
		);

		// Register styles for the editor.
		wp_enqueue_style(
			'sg-ai-studio-gutenberg',
			\SG_AI_Studio\URL . '/assets/css/gutenberg.css',
			array(),
			\SG_AI_Studio\VERSION
		);

		// Localize script with necessary data.
		wp_localize_script(
			'sg-ai-studio-gutenberg',
			'sgAiStudioGutenberg',
			array(
				'buttonLabel'   => __( 'AI STUDIO', 'sg-ai-studio' ),
				'nonce'         => wp_create_nonce( 'sg_ai_studio_gutenberg_nonce' ),
				'restUrl'       => esc_url_raw( rest_url() ),
				'is_connected'  => get_option( 'sg_ai_studio_connected', false ),
				'settingsUrl'   => admin_url( 'admin.php?page=sg-ai-studio' ),
			)
		);
	}
}
