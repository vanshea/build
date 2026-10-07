<?php
namespace SiteGround_Central\Traits;

/**
 * Trait used for SCO Excludes trait.
 */
trait Sco_Exclude_Trait {

	/**
	 * Plugin excludes for different SCO.
	 *
	 * @since 3.0.2
	 *
	 * @var array
	 */
	public $sco_plugin_excludes = array(
		2 => array( 33 ),
		3 => array(),
		4 => array( 33 ),
	);

	/**
	 * Themes excludes for different SCO.
	 *
	 * @since 3.0.2
	 *
	 * @var array
	 */
	public $sco_theme_excludes = array(
		2 => array(),
		3 => array(),
		4 => array(),
	);

	/**
	 * Plugin excludes based on app language.
	 *
	 * @since 3.0.2
	 *
	 * @var array
	 */
	public $lang_plugin_excludes = array(
		'it_IT'   => array(),
		'es_ES'   => array(),
		'default' => array( 33 ),
	);

	/**
	 * Themes excludes based on app language.
	 *
	 * @since 3.0.2
	 *
	 * @var array
	 */
	public $lang_theme_excludes = array(
		'it_IT'   => array(),
		'es_ES'   => array(),
		'default' => array(),
	);

	/**
	 * Maybe exclude an item from the response.
	 *
	 * @since 3.0.2
	 *
	 * @param  array $excludes The SCO based excludes, either theme or plugin.
	 * @param  array $items    The themes/plugins array.
	 *
	 * @return array $items    The items list with excluded items.
	 */
	public function maybe_exclude_items( $excludes, $items ) {
		// Get the SCO id.
		$sco_id = get_option( 'sco_id', 4 );

		// Bail and don't modify the items array.
		if ( empty( $excludes[ $sco_id ] ) ) {
			return $items;
		}

		// Loop the items and remove any matches with the excludes list.
		foreach ( $items as $key => $item ) {
			if ( in_array( $item['id'], $excludes[ $sco_id ] ) ) {
				unset( $items[ $key ] );
			}
		}

		// Re-index the array and return it.
		return array_values( $items );
	}

	/**
	 * Maybe exclude an item based on app language.
	 *
	 * @since 3.0.2
	 *
	 * @param  array $excludes The app language based excludes.
	 * @param  array $items    The themes/plugins array.
	 *
	 * @return array $items    The items list with excluded items.
	 */
	public function maybe_exclude_items_for_lang( $excludes, $items ) {
		// Get the locale.
		$locale = get_locale();

		// Prepare excludes array.
		$excludes_array = array_key_exists( $locale, $excludes ) ? $excludes[ $locale ] : $excludes['default'];

		if ( empty( $excludes_array ) ) {
			return $items;
		}

		// Loop the items and remove any matches with the excludes list.
		foreach ( $items as $key => $item ) {
			if ( in_array( $item['id'], $excludes_array ) ) {
				unset( $items[ $key ] );
			}
		}

		// Re-index the array and return it.
		return array_values( $items );
	}
}
