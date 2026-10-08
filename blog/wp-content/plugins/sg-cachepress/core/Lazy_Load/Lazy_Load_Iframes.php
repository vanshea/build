<?php
namespace SiteGround_Optimizer\Lazy_Load;

/**
 * SG Lazy_Load_Images main plugin class
 */
class Lazy_Load_Iframes extends Abstract_Lazy_Load {

	/**
	 * Filter for excluding specific iframe by source.
	 *
	 * @var string
	 */
	public $exclude_assets_filter = 'sgo_lazy_load_exclude_iframes';

	/**
	 * Get the HTML tag for the iframe lazy load implementation.
	 *
	 * @return string The HTML tag name.
	 */
	public function get_tag() {
		return 'IFRAME';
	}

	/**
	 * Check whether the current iframe tag has already been processed.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 *
	 * @return bool True if the tag has already been processed, false otherwise.
	 */
	public function is_tag_processed( $processor ) {
		return $processor->has_class( 'lazyload' );
	}
}
