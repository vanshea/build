<?php
/**
 * Server-side rendering for the AI Studio prompt block
 *
 * @package SG_AI_Studio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the AI Studio prompt block on the server.
 *
 * @param array $attributes Block attributes.
 * @return string Rendered block HTML.
 */
function sg_ai_studio_render_prompt_block( $attributes ) {
	// This block is only for generating content in the editor.
	// It doesn't need to display anything on the frontend.
	return '';
}
