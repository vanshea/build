<?php
namespace SiteGround_Optimizer\Lazy_Load;

/**
 * SG Lazy_Load_Images main plugin class
 */
class Lazy_Load_Videos extends Abstract_Lazy_Load {

	/**
	 * Filter for excluding specific video.
	 *
	 * @var string
	 */
	public $exclude_assets_filter = 'sgo_lazy_load_exclude_videos';

	/**
	 * Get the HTML tag for the video lazy load implementation.
	 *
	 * @return string The HTML tag name.
	 */
	public function get_tag() {
		return 'VIDEO';
	}

	/**
	 * Check whether the current video tag has already been processed.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 *
	 * @return bool True if the tag has already been processed, false otherwise.
	 */
	public function is_tag_processed( $processor ) {
		return $processor->has_class( 'lazyload' );
	}
}
