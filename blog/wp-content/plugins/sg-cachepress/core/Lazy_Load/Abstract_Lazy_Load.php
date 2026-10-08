<?php
namespace SiteGround_Optimizer\Lazy_Load;
use SiteGround_Optimizer\Helper\Helper;

/**
 * SG Abstract_Lazy_load main plugin class.
 */
abstract class Abstract_Lazy_Load {

	/**
	 * Check if it's feed, the content is empty or there are conflicting plugins.
	 *
	 * @since  5.6.0
	 *
	 * @param  string $content The page content.
	 *
	 * @return bool            Whether should proceed with replacements.
	 */
	public function should_process( $content ) {
		if (
			$this->is_lazy_url_excluded() ||
			$this->is_lazy_post_type_excluded() ||
			is_feed() ||
			empty( $content ) ||
			is_admin() ||
			( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) ||
			( method_exists( 'FLBuilderModel', 'is_builder_enabled' ) && true === \FLBuilderModel::is_builder_enabled() )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Filter the html output.
	 *
	 * @since  5.4.3
	 *
	 * @param  string $content The content.
	 *
	 * @return string          Modified content.
	 */
	public function filter_html( $content ) {
		// Bail if it's feed or if the content is empty.
		if ( $this->should_process( $content ) ) {
			return $content;
		}

		// Bail if the WP_HTML_Tag_Processor is not available.
		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		// Initiate the processor on the content.
		$processor = new \WP_HTML_Tag_Processor( $content );

		// Check for specific asset being excluded.
		$excluded_assets = apply_filters( $this->exclude_assets_filter, array() ); // phpcs:ignore

		// Get the list of ignored classes.
		$ignored_classes = apply_filters(
			'sgo_lazy_load_exclude_classes', // phpcs:ignore
			get_option( 'siteground_optimizer_excluded_lazy_load_classes', array() )
		);

		// Iterate the tags (IMG, VIDEO, IFRAME).
		while ( $processor->next_tag( $this->get_tag() ) ) {
			// Check if the tag has to be skipped ( already processed for example or excluded ).
			if ( $this->should_skip_tag( $processor, $excluded_assets, $ignored_classes ) ) {
				continue;
			}

			// Process the tag.
			$this->process_tag( $processor );

			// Add the 'lazyload' class.
			$processor->add_class( 'lazyload' );
		}

		// Get the HTML updated by the tag processor.
		$filtered_html = $processor->get_updated_html();

		// Perform the finalizing edits and return the HTML.
		return $this->finalize_html( $filtered_html );
	}

	/**
	 * Check if the specific url has been excluded from lazy loading.
	 *
	 * @since  7.1.3
	 *
	 * @return boolean True if it is excluded, false otherwise.
	 */
	public function is_lazy_url_excluded() {
		// Get the urls where lazy load is excluded.
		$excluded_urls = apply_filters( 'sgo_lazy_load_exclude_urls', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.

		// Bail if no excludes are found or we do not have a match.
		if ( empty( $excluded_urls ) && ! in_array( Helper::get_current_url(), $excluded_urls ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a specific post type is excluded for lazyloading.
	 *
	 * @since  7.1.3
	 *
	 * @return boolean True if it is excluded, false otherwise.
	 */
	public function is_lazy_post_type_excluded() {
		// Get the post_types with excluded lazy load.
		$excluded_post_types = apply_filters( 'sgo_lazy_load_exclude_post_types', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward-compatible public filter.

		// Bail if no excludes are found or we do not have a match.
		if ( empty( $excluded_post_types ) && ! in_array( get_post_type(), $excluded_post_types ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the current tag should be skipped.
	 *
	 * @param \WP_HTML_Tag_Processor $processor       HTML tag processor.
	 * @param array                  $excluded_assets Assets excluded from lazy loading.
	 * @param array                  $ignored_classes CSS classes excluded from lazy loading.
	 *
	 * @return bool True when the tag should be skipped, false otherwise.
	 */
	public function should_skip_tag( $processor, $excluded_assets, $ignored_classes ) {
		// Skip tags which have already been processed.
		if ( $this->is_tag_processed( $processor ) ) {
			return true;
		}

		// Skip explicitly excluded assets.
		if ( $this->is_asset_excluded( $processor, $excluded_assets ) ) {
			return true;
		}

		// Skip tags containing ignored CSS classes.
		if ( $this->has_ignored_class( $processor, $ignored_classes ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether the current asset is excluded.
	 *
	 * @param \WP_HTML_Tag_Processor $processor       HTML tag processor.
	 * @param array                  $excluded_assets Excluded asset URLs.
	 *
	 * @return bool                  True if the asset is excluded, false otherwise.
	 */
	protected function is_asset_excluded( $processor, $excluded_assets ) {
		if ( empty( $excluded_assets ) ) {
			return false;
		}

		// Get the 'src' attribute.
		$src = $processor->get_attribute( 'src' );

		if ( ! empty( $src ) ) {
			return in_array( $src, $excluded_assets, true );
		}

		return false;
	}

	/**
	 * Check whether the current tag has an ignored CSS class.
	 *
	 * @param \WP_HTML_Tag_Processor $processor       HTML tag processor.
	 * @param array                  $ignored_classes Ignored CSS classes.
	 *
	 * @return bool                  True if the class is ignored, false otherwise.
	 */
	protected function has_ignored_class( $processor, $ignored_classes ) {
		if ( empty( $ignored_classes ) ) {
			return false;
		}

		foreach ( $ignored_classes as $ignored_class ) {
			if ( $processor->has_class( $ignored_class ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process the current HTML tag.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 */
	public function process_tag( $processor ) {
		// Get the original source.
		$src = $processor->get_attribute( 'src' );

		// Bail if the video does not have a source.
		if ( empty( $src ) ) {
			return;
		}

		// Do not replace sources starting with "data".
		if ( 0 === stripos( $src, 'data' ) ) {
			return;
		}

		// Store the original source.
		$processor->set_attribute( 'data-src', $src );

		// Remove the original source to prevent immediate loading.
		$processor->remove_attribute( 'src' );
	}

	/**
	 * Finalize the processed HTML.
	 *
	 * @param string $html Processed HTML.
	 *
	 * @return string Processed HTML.
	 */
	public function finalize_html( $html ) {
		return $html;
	}
}

