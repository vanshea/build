<?php
/**
 * BlocksManager class for managing Gutenberg blocks
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Blocks;

use SG_AI_Studio\Helper\Helper;

/**
 * Manages Gutenberg blocks for the plugin.
 */
class BlocksManager {
	/**
	 * Register custom Gutenberg blocks
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Register scripts and styles for the block.
		wp_register_script(
			'sg-ai-studio-prompt-editor-script',
			\SG_AI_Studio\URL . '/assets/blocks/aistudio-prompt/index.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-api-fetch' ),
			\SG_AI_Studio\VERSION,
			true
		);

		// Localize the script right after registering it.
		wp_localize_script(
			'sg-ai-studio-prompt-editor-script',
			'sgAiStudioBlock',
			array(
				'nonce'       => wp_create_nonce( 'sg_ai_studio_gutenberg_nonce' ),
				'settingsUrl' => esc_url( admin_url( 'admin.php?page=' . \SG_AI_Studio\PLUGIN_SLUG ) ),
				'hasApiKey'   => ! empty( get_option( 'sg_ai_studio_client_id' ) ),
			)
		);

		wp_register_style(
			'sg-ai-studio-prompt-editor-style',
			\SG_AI_Studio\URL . '/assets/blocks/aistudio-prompt/index.css',
			array(),
			\SG_AI_Studio\VERSION
		);

		// Register AI Studio Prompt block.
		register_block_type(
			'sg-ai-studio/aistudio-prompt',
			array(
				'editor_script'   => 'sg-ai-studio-prompt-editor-script',
				'editor_style'    => 'sg-ai-studio-prompt-editor-style',
				'render_callback' => array( $this, 'sg_ai_studio_render_prompt_block' ),
			)
		);
	}

	/**
	 * Renders the AI Studio prompt block on the server.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block HTML.
	 */
	public function sg_ai_studio_render_prompt_block( $attributes ) {
		// This block is only for generating content in the editor.
		// It doesn't need to display anything on the frontend.
		return '';
	}

}
