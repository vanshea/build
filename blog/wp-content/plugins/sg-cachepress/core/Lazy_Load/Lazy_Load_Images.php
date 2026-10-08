<?php
namespace SiteGround_Optimizer\Lazy_Load;

/**
 * SG Lazy_Load_Images main plugin class
 */
class Lazy_Load_Images extends Abstract_Lazy_Load {

	/**
	 * Filter for excluding specific image.
	 *
	 * @var string
	 */
	public $exclude_assets_filter = 'sgo_lazy_load_exclude_images';

	/**
	 * Prefix used to identify already processed images.
	 *
	 * @var string
	 */
	public $replacement_src = 'data:image';

	/**
	 * Placeholder URL, for the Image placeholder 'src'.
	 *
	 * @var string
	 */
	public $replacement_placeholder = 'https://lazy-src-placeholder.com';

	/**
	 * Lazy Load placeholder, which we can set, because WP_HTML_Tag_Processor->set_attribute() does not accept non-URL values as 'src'.
	 */
	public $image_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

	/**
	 * Original image tags, indexed by their markers.
	 *
	 * @var array
	 */
	public $original_images = array();

	/**
	 * Get the HTML tag for the Image lazy load implementation.
	 *
	 * @return string The HTML tag name.
	 */
	public function get_tag() {
		return 'IMG';
	}

	/**
	 * Process the current HTML tag.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 */
	public function process_tag( $processor ) {
		// Get the original tag first, before modifications.
		$original_image = $this->get_original_img_tag( $processor );

		// Create a marker for the image, so we  can connect it to its original later.
		$marker = 'sgo-lazy-load-' . count( $this->original_images );

		// Store the original image and its marker.
		$this->original_images[ $marker ] = $original_image;

		// Add the temporary marker.
		$processor->set_attribute( 'data-sgo-lazy-load', $marker );

		// Get the original attributes before modifying the tag.
		$src    = $processor->get_attribute( 'src' );
		$srcset = $processor->get_attribute( 'srcset' );

		// Replace the 'src' with a temporary valid URL.
		$processor->set_attribute(
			'src',
			$this->replacement_placeholder
		);

		// Store the original source.
		$processor->set_attribute( 'data-src', $src );

		// Replace the 'srcset' attribute if available.
		if ( ! empty( $srcset ) ) {
			$processor->set_attribute( 'data-srcset', $srcset );
			$processor->remove_attribute( 'srcset' );
		}
	}

	/**
	 * Extract the original <img> tag, before modifications, so it can used for the <noscript> tag that will be added.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 */
	public function get_original_img_tag( $processor ) {
		$html = '<img';

		// Get all attributes, and add them to an ima tag, practically reconstructing the original.
		foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$value = $processor->get_attribute( $name );

			if ( null === $value ) {
				continue;
			}

			if ( true === $value ) {
				$html .= ' ' . $name;
				continue;
			}

			$html .= sprintf(
				' %s="%s"',
				$name,
				esc_attr( (string) $value )
			);
		}

		$html .= '>';

		return $html;
	}

	/**
	 * Finalize the processed HTML and add the <noscript> tag.
	 *
	 * @param string $html Processed HTML.
	 *
	 * @return string Processed HTML.
	 */
	public function finalize_html( $html ) {
		// Loop over the processed images.
		foreach ( $this->original_images as $marker => $original_image ) {
			// Find the processed image.
			preg_match(
				sprintf(
					'/<img\b[^>]*data-sgo-lazy-load="%s"[^>]*>/i',
					preg_quote( $marker, '/' )
				),
				$html,
				$match
			);

			// Continue if no image is found.
			if ( empty( $match[0] ) ) {
				continue;
			}

			// Get the processed image.
			$processed_image = $match[0];

			// Remove the temporary marker.
			$processed_image = str_replace(
				sprintf(
					' data-sgo-lazy-load="%s"',
					$marker
				),
				'',
				$processed_image
			);

			// Order the attributes in the correct order.
			$processed_image = $this->order_lazyload_image_attributes( $processed_image );

			// Replace the image with itself and add the <noscript> fallback.
			$html = str_replace(
				$match[0],
				$processed_image . '<noscript>' . $original_image . '</noscript>',
				$html
			);
		}

		// Replace the temporary placeholder URL.
		$html = str_replace(
			$this->replacement_placeholder,
			$this->image_placeholder,
			$html
		);

		// Reset the stored images before processing another content block.
		$this->original_images = array();

		return $html;
	}

	/**
	 * Prepare responsive attributes for lazy-loaded images.
	 *
	 * @param string $image         Image HTML.
	 *
	 * @return string Modified image HTML.
	 */
	public function prepare_lazyload_image_attributes( $image ) {
		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $image;
		}

		$processor = new \WP_HTML_Tag_Processor( $image );

		if ( ! $processor->next_tag( 'IMG' ) ) {
			return $image;
		}

		if ( ! $processor->has_class( 'lazyload' ) ) {
			return $image;
		}

		// Preserve responsive sources for the lazy-load script.
		$srcset = $processor->get_attribute( 'srcset' );

		if ( ! empty( $srcset ) ) {
			$processor->set_attribute( 'data-srcset', $srcset );
			$processor->remove_attribute( 'srcset' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Check whether the current tag has already been processed.
	 *
	 * @param \WP_HTML_Tag_Processor $processor HTML tag processor.
	 *
	 * @return bool
	 */
	protected function is_tag_processed( $processor ) {
		// Get the 'src' attribute.
		$src = $processor->get_attribute( 'src' );

		// Check if the 'src' is already replaced (value comes from the child class).
		if (
			is_string( $src ) &&
			0 === stripos( $src, $this->replacement_src )
		) {
			return true;
		}

		return false;
	}

	/**
	* Re-arrange a lazy-loaded image with src before data-src.
	*
	* @param string $image Image HTML.
	*
	* @return string Image HTML.
	*/
	public function order_lazyload_image_attributes( $image ) {
		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $image;
		}

		$processor = new \WP_HTML_Tag_Processor( $image );

		if ( ! $processor->next_tag( 'IMG' ) ) {
			return $image;
		}

		$attributes = array();

		// Store the values of all attributes.
		foreach ( $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$value = $processor->get_attribute( $name );

			if ( null !== $value ) {
				$attributes[ $name ] = $value;
			}
		}

		// WordPress must encounter the actual src attribute before data-src.
		$attribute_names = array_unique(
			array_merge(
				array( 'src', 'data-src' ),
				array_keys( $attributes )
			)
		);

		// Build the tag with attributes arranged in the desired order.
		$html = '<img';

		foreach ( $attribute_names as $name ) {
			if ( ! array_key_exists( $name, $attributes ) ) {
				continue;
			}

			// In case of boolean attribute (e.g. disabled).
			if ( true === $attributes[ $name ] ) {
				$html .= ' ' . $name;
				continue;
			}

			$html .= sprintf(
				' %s="%s"',
				$name,
				esc_attr( (string) $attributes[ $name ] )
			);
		}

		return $html . '>';
	}
}
