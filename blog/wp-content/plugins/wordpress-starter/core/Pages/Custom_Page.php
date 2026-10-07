<?php
namespace SiteGround_Central\Pages;

/**
 * SG Custom_Page main class
 */
abstract class Custom_Page {

	/**
	 * The constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Set custom menus.
	}

	/**
	 * Styles to be dequeued.
	 *
	 * @var array
	 */
	public $dequeued_styles = array(
		'auxin-front-icon', // Phlox Theme.
		'mks_shortcodes_simple_line_icons', // Meks Flexible Shortcodes.
		'onthego-admin-styles', // Toolset Types
	);

	/**
	 * Check if we are on the correct page for loading the scripts.
	 *
	 * @since  1.0.0
	 *
	 * @return boolean If we are on the required page.
	 */
	public function maybe_page() {
		// Get the current screen.
		$current_screen = \get_current_screen();
		// Check if we meet the page requirements.
		if (
			$this->page_id !== $current_screen->id &&
			$this->page_id_network !== $current_screen->id
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if option for custom pages is set in order to load the methods for building them.
	 * If we need actions to be set independently from the custom pages,
	 * we move this check after the required independent actions in the child constructor.
	 *
	 * @since  1.0.0
	 *
	 * @return bool Either enable or disable custom menus and options.
	 */
	public function maybe_show_page() {
		if ( 'yes' === get_option( $this->option_name, 'no' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Regenerate the admin menu for the specific page.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		// Add the sub-menu page.
		$page = add_submenu_page(
			$this->parent_slug,
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->capability,
			$this->menu_slug,
			array( $this, 'render' )
		);

		// Finally return the page hook_suffix.
		return $page;
	}

	/**
	 * Replace the submenu button link.
	 *
	 * Since the the pages are part of the core files
	 * and we are not able to modify the link, this is the only possible way
	 * to replace the default link with the custom one.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url  The complete admin area URL including scheme and path.
	 * @param  string $path Path relative to the admin area URL. Blank string if no path is specified.
	 *
	 * @return string $url  Modified url.
	 */
	public function replace_submenu_button_link( $url, $path ) {
		// Check whether is the path we are looking for
		// adn replace the url with the new one.
		if ( $this->submenu_slug === $path ) {
			$url = admin_url( $this->parent_slug . '?page=' . $this->menu_slug );
		}
		// Finlly return the url.
		return $url;
	}

	/**
	 * Get the page title.
	 *
	 * @since 1.0.0
	 */
	public function get_page_title() {
		return __( 'Add new', 'siteground-wizard' );
	}

	/**
	 * Get the menu title.
	 *
	 * @since 1.0.0
	 */
	public function get_menu_title() {
		return __( 'Add new', 'siteground-wizard' );
	}

	/**
	 * Render the submenu page.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function render() {
		// Include the partial.
		include \SiteGround_Central\DIR . '/templates/' . $this->parent_slug;
	}

}
