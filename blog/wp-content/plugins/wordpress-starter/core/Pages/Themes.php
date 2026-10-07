<?php
namespace SiteGround_Central\Pages;

/**
 * SG Central Themes main class
 */
class Themes extends Custom_Page {
	/**
	 * Parent slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $parent_slug = 'themes.php';

	/**
	 * Capability.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $capability = 'install_themes';

	/**
	 * Menu slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $menu_slug = 'sg-themes-install.php';

	/**
	 * For checking the paths for overriding urls.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $submenu_slug = 'theme-install.php';

	/**
	 * Option which returns whether to hide or show custom page
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $option_name = 'siteground_wizard_hide_custom_themes';

	/**
	 * The page name for loading the correct scripts.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	public $page_id = 'appearance_page_sg-themes-install';

	/**
	 * The network page name for loading the correct scripts.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $page_id_network = 'appearance_page_sg-themes-install-network';

	/**
	 * The singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @var The singleton instance.
	 */
	private static $instance;

	/**
	 * The constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Bail if the page should not be replaced.
		if ( false === $this->maybe_show_page() ) {
			return;
		}

		// Construct the parent.
		parent::__construct();
	}

	/**
	 * Prepare the necessary scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		// Check if we are on the correct page.
		if ( false === $this->maybe_page() ) {
			return;
		}

		// Dequeue conflicting styles.
		foreach ( $this->dequeued_styles as $style ) {
			wp_dequeue_style( $style );
		}

	}

	/**
	 * Set the parent file to themes.php in order to hightlight
	 * the "Themes" when the current page is opened,
	 *
	 * @since  1.0.0
	 *
	 * @param  string $parent_file The parent file name.
	 *
	 * @return string $parent_file The modified parent file name.
	 */
	public function highlight_submenu_menu_item( $parent_file ) {
		// Get the current screen.
		$current_screen = get_current_screen();

		// Check whether is the custom dashboard page
		// and change the `parent_file` to themes.php.
		if ( 'appearance_page_sg-themes-install' === $current_screen->base ) {
			$parent_file = $this->parent_slug;
		}

		// Return the `parent_file`.
		return $parent_file;
	}

	/**
	 * Reorder or Remove button from the menu.
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $menu_order Flag if the menu order is enabled.
	 *
	 * @return bool $menu_order Flag if the menu order is enabled.
	 */
	public function reorder_submenu_pages( $menu_order ) {
		// Check user capabilities.
		if ( ! current_user_can( 'switch_themes' ) ) {
			return $menu_order;
		}

		// Load the global submenu.
		global $submenu;

		// Find the "Add New" menu ID.
		foreach ( $submenu['themes.php'] as $key => $key_array ) {
			if ( false !== array_search( 'sg-themes-install.php', $submenu['themes.php'][$key] ) ) {
				$menu_key_id = $key;
			}
		}

		// Remove the menu button.
		if ( filter_var( $menu_key_id, FILTER_VALIDATE_INT ) ) {
			unset( $submenu['themes.php'][$menu_key_id] );
		}

		return $menu_order;
	}
}
