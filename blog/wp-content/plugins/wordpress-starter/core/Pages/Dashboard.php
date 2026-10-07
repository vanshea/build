<?php
namespace SiteGround_Central\Pages;

use SiteGround_Central\Traits\Statistic_Trait;

/**
 * SG Central Dashboard main class
 */
class Dashboard extends Custom_Page {
    use Statistic_Trait;

	/**
	 * Parent slug.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $parent_slug = 'index.php';

	/**
	 * Capabilities that can see and interact with the custom dashboard page.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $capabilities = array( 'manage_options', 'manage_woocommerce' );

	/**
	 * Menu slug.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $menu_slug = 'siteground-dashboard.php';

	/**
	 * For checking the paths for overriding urls.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $submenu_slug = 'dashboard_page_custom-dashboard';

	/**
	 * Option which returns whether to hide or show custom page
	 *
	 * @since  3.0.0
	 *
	 * @var string
	 */
	public $option_name = 'siteground_wizard_hide_custom_dashboard';

	/**
	 * The page name for loading the correct scripts.
	 *
	 * @since  3.0.0
	 *
	 * @var string
	 */
	public $page_id = 'dashboard_page_siteground-dashboard';

	/**
	 * The network page name for loading the correct scripts.
	 *
	 * @since  3.0.0
	 *
	 * @var string
	 */
	public $page_id_network = 'dashboard_page_siteground-dashboard-network';

	/**
	 * The singleton instance.
	 *
	 * @since 3.0.0
	 *
	 * @var The singleton instance.
	 */
	private static $instance;

	/**
	 * The constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Bail if the page should not be replaced.
		if ( false === $this->maybe_show_page() ) {
			return;
		}

		// Construct the parent.
		parent::__construct();

		remove_all_actions('admin_notices');
	}

	/**
	 * Prepare the necessary scripts.
	 *
	 * @since  3.0.0
	 */
	public function enqueue_scripts() {
		// Check if we are on the correct page.
		if ( false === $this->maybe_page() && ! is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'siteground-dashboard',
			\SiteGround_Central\URL . '/assets/js/admin.js',
			array( 'jquery' ),
			\SiteGround_Central\VERSION
		);

		// Dequeue conflicting styles.
		foreach ( $this->dequeued_styles as $style ) {
			wp_dequeue_style( $style );
		}
	}

	/**
	 * Add option that will be used to check if the dashboard banner should be shown.
	 *
	 * @since  3.0.0
	 */
	public function switch_dashboard() {
		if (
			isset( $_GET['switch_dashboard'] ) &&
			wp_verify_nonce( $_GET['switch_dashboard'], 'switch_dashboard_nonce' )
		) {
			$value = isset( $_GET['value'] ) ? wp_unslash( $_GET['value'] ) : 'yes';
			$event = 'yes' === $value ? 'revert_dashboard' : 'dashboard_used_person';

			self::send_statistics( $event );
			update_option( 'siteground_wizard_hide_custom_dashboard', $value );

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Get the page title.
	 *
	 * @since 3.0.0
	 */
	public function get_page_title() {
		return __( 'Home', 'siteground-wizard' );
	}

	/**
	 * Get the menu title.
	 *
	 * @since 3.0.0
	 */
	public function get_menu_title() {
		return __( 'Home', 'siteground-wizard' );
	}

	/**
	 * Add additional styles to WordPress admin bar.
	 *
	 * @since  3.0.0
	 */
	public function additional_admin_bar_css() {
		if ( is_user_logged_in() && is_admin_bar_showing() ) :
			?>
			<style type="text/css">
				#wpadminbar ul li#wp-admin-bar-siteground-wizard-dashboard { padding-top: 12px; }
			</style>
		<?php
		endif;
	}

	/**
	 * Regenerate the admin menu for the specific page.
	 *
	 * @since 3.0.0
	 */
	public function admin_menu() {
		// Add the sub-menu page.
		$page = add_submenu_page(
			$this->parent_slug,
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->check_allowed_capability( $this->capabilities ),
			$this->menu_slug,
			array( $this, 'render' )
		);
		$this->remove_original_page();
		// Finally return the page hook_suffix.
		return $page;
	}

	/**
	 * Reorder admin bar menu to match the initial order.
	 *
	 * @since  3.0.0
	 */
	public function reorder_admin_bar() {
		global $wp_admin_bar;

		// The desired order of identifiers (items).
		$ids = array(
			'sg-central-dashboard',
			'themes',
			'widgets',
			'menus',
		);

		// Get an array of all the toolbar items on the current page.
		$nodes = $wp_admin_bar->get_nodes();

		// Perform recognized identifiers.
		foreach ( $ids as $id ) {
			if ( ! isset( $nodes[ $id ] ) ) {
				continue;
			}

			// This will cause the identifier to act as the last menu item.
			$wp_admin_bar->remove_menu( $id );
			$wp_admin_bar->add_node( $nodes[ $id ] );

			// Remove the identifier from the list of nodes.
			unset( $nodes[ $id ] );
		}

		// Unknown identifiers will be moved to appear after known identifiers.
		foreach ( $nodes as $id => &$obj ) {
			// There is no need to organize unknown children identifiers (sub items).
			if ( ! empty( $obj->parent ) ) {
				continue;
			}

			// This will cause the identifier to act as the last menu item.
			$wp_admin_bar->remove_menu( $id );
			$wp_admin_bar->add_node( $obj );
		}

	}

	/**
	 * Remove initial dashboard item from admin bar menu
	 * and add our custom dashboard menu item.
	 *
	 * @since 3.0.0
	 */
	public function add_dashboard_admin_bar_menu_item() {

		global $wp_admin_bar;

		// Remove the initial dashboard menu item.
		$wp_admin_bar->remove_node( 'dashboard' );

		// Add our custom dashboard item.
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'sg-central-dashboard',
				'title'  => 'Dashboard',
				'href'   => get_admin_url( null, 'admin.php?page=siteground-dashboard.php' ),
				'parent' => 'appearance',
			)
		);
	}

	/**
	 * Redirect to custom dashboard after successful installation.
	 *
	 * @since  3.0.0
	 */
	public function redirect_to_dashboard() {
		global $pagenow;

		// Bail if the current user is not admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = get_option( 'siteground_wizard_installation_status' );

		// Delete plugin transients on inital dashboard rendering.
		if ( isset( $_GET['hard-redirect'] ) ) {
			$this->delete_plugins_redirect_transients();
		}

		if (
			( isset( $_GET['page'] ) && 'siteground-central' === $_GET['page'] && ! empty( $status ) && 'completed' === $status['status'] ) ||
			$this->parent_slug === $pagenow && empty( $_GET )
		) {
			! \is_multisite() ? wp_safe_redirect( admin_url( 'admin.php?page=siteground-dashboard.php' ) ) : wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Delete all plugin redirect transients,
	 * to prevent redirects to their pages.
	 *
	 * @since  3.0.0
	 */
	private function delete_plugins_redirect_transients() {
		$transients = array(
			'wpforms_activation_redirect',
			'_tribe_events_activation_redirect',
		);

		foreach ( $transients as $transient ) {
			$response = delete_transient( $transient );
		}
	}

	/**
	 * Set the parent file to index.php in order to hightlight
	 * the menu item when "Dashboard" menu item is selected.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $parent_file The parent file name.
	 *
	 * @return string $parent_file The modified parent file name.
	 */
	public function highlight_menu_item( $parent_file ) {
		// Get the current screen.
		$current_screen = get_current_screen();

		// Check whether is the custom dashboard page
		// and change the `parent_file` to siteground-dashboard.php.
		if ( 'dashboard_page_custom-dashboard' === $current_screen->base ) {
			$parent_file = $this->menu_slug;
		}

		// Return the `parent_file`.
		return $parent_file;
	}

	/**
	 * Remove the original "Home" page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function remove_original_page() {
		remove_submenu_page( $this->parent_slug, $this->parent_slug );

		// Add EDD hidden upgrade page as it is being added to the WP Dashboard menu page.
		if (
			\is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) &&
			isset( $_GET['page'] ) &&
			'edd-upgrades' === $_GET['page'] &&
			function_exists('edd_upgrades_screen')
		)
		{
			add_submenu_page( null, __( 'EDD Upgrades', 'easy-digital-downloads' ), __( 'EDD Upgrades', 'easy-digital-downloads' ), 'manage_shop_settings', 'edd-upgrades', 'edd_upgrades_screen' );
		}
	}

	/**
	 * Change the order of index.php submenu pages.
	 * Since our custom page has been added late, we need to reorder
	 * the submenu page, so that we can match the initial order.
	 *
	 * Example:
	 *          "SiteGround Wizard"
	 *          "Update core"
	 *
	 * @since  3.0.0
	 *
	 * @param  bool $menu_order Flag if the menu order is enabled.
	 *
	 * @return bool $menu_order Flag if the menu order is enabled.
	 */
	public function reorder_submenu_pages( $menu_order ) {
		// Load the global submenu.
		global $submenu;

		// Bail if for some reason the submenu is empty.
		if ( empty( $submenu ) ) {
			return;
		}

		// Try to get our custom page index.
		foreach ( $submenu['index.php'] as $key => $value ) {
			if ( 'siteground-dashboard.php' === $value[2] ) {
				$page_index = $key;
			}
		}

		// Bail if our custom page is missing in `$submenu` for some reason.
		if ( empty( $page_index ) ) {
			return $menu_order;
		}

		// Store the custom dashboard in variable.
		$dashboard_menu_item = $submenu['index.php'][ $page_index ];

		// Remove the original custom dashboard page.
		unset( $submenu['index.php'][ $page_index ] );

		// Add the custom dashboard page in the beginning.
		array_unshift( $submenu['index.php'], $dashboard_menu_item );

		// Finally return the menu order.
		return $menu_order;
	}

	/**
	 * Render the submenu page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function render() {
		wp_localize_community_events();

		// Include the partial.
		include \SiteGround_Central\DIR . '/templates/siteground-dashboard.php';
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 * @since 3.0.0
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'siteground_wizard_dashboard',
			__( 'Simplified Dashboard', 'siteground-wizard' ),
			array( $this, 'load_dashboard_widget' )
		);

		global $wp_meta_boxes;

		$wp_meta_boxes['dashboard']['side']['core'] = array_merge(
			array(
				'siteground_wizard_dashboard' => $wp_meta_boxes['dashboard']['normal']['core']['siteground_wizard_dashboard'],
			),
			$wp_meta_boxes['dashboard']['side']['core']
		);

		unset( $wp_meta_boxes['dashboard']['normal']['core']['siteground_wizard_dashboard'] );
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public function load_dashboard_widget() {
		include \SiteGround_Central\DIR . '/templates/dashboard-widget.php';
	}

	/**
	 * Checks if the user, has the necessary capability.
	 *
	 * @param $capabilities Array of predefined capabilities.
	 *
	 * @return string | boolean The capability if the user has it. False if the user does not.
	 */
	public function check_allowed_capability( $capabilities ) {
		// Loop trough the predefined capabilities and see if the user has one.
		foreach ( $capabilities as $capability ) {
			if ( current_user_can( $capability ) ) {
				return $capability;
			}
		}

		return false;
	}
}

