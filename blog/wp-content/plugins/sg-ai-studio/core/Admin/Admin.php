<?php
/**
 * Admin class for managing admin interface
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Admin;

use SG_AI_Studio;
use SG_AI_Studio\Helper\Helper;
use SG_AI_Studio\Vendor\SiteGround_i18n\i18n_Service;

/**
 * Handle all hooks for our custom admin page.
 */
class Admin {

	/**
	 * Subpages array.
	 *
	 * @var array
	 */
	public $subpages = array(
		'settings'     => 'Settings',
		'activity-log' => 'Activity Log & Usage',
	);

	/**
	 * Build the admin page slug of a subpage.
	 *
	 * The slugs are prefixed with the plugin slug, so that generic ids such as
	 * `settings` or `activity-log` do not collide with the pages of another
	 * plugin. WordPress resolves `admin.php?page=` by slug alone, so a shared
	 * slug makes one of the two pages unreachable.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $id The subpage id.
	 * @return string The admin page slug.
	 */
	public function get_subpage_slug( $id ) {
		return \SG_AI_Studio\PLUGIN_SLUG . '-' . $id;
	}

	/**
	 * Get the slugs of all pages registered by the plugin.
	 *
	 * @since  1.0.0
	 *
	 * @return array The plugin page slugs.
	 */
	public function get_plugin_page_ids() {
		$page_ids = array( \SG_AI_Studio\PLUGIN_SLUG );

		foreach ( array_keys( $this->subpages ) as $id ) {
			$page_ids[] = $this->get_subpage_slug( $id );
		}

		return $page_ids;
	}

	/**
	 * Get the slug of the page being requested.
	 *
	 * WordPress sets the `plugin_page` global from the `page` query arg on
	 * every admin.php request, so it is available before the current screen.
	 *
	 * @since  1.0.0
	 *
	 * @return string The requested page slug, empty when there is none.
	 */
	public function get_requested_page_slug() {
		return isset( $GLOBALS['plugin_page'] ) ? (string) $GLOBALS['plugin_page'] : '';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_styles() {
		// Always enqueue chat styles in admin.
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			if ( false !== $this->is_plugin_page() ) {
				wp_enqueue_style(
					'siteground-ai-studio-admin',
					\SG_AI_Studio\URL . '/assets/css/admin.css',
					array(),
					\SG_AI_Studio\VERSION,
					'all'
				);
			}
		}
	}

	/**
	 * Register the settings for the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'sg_ai_studio_settings',
			'sg_ai_studio_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'sg_ai_studio_settings',
			'sg_ai_studio_disable_gutenberg_actions',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		global $wp_version;

		// Bail if we are on different page.
		if ( false !== $this->is_plugin_page() ) {
			// Enqueue the chat script.
			wp_enqueue_script(
				'siteground-ai-studio-admin',
				\SG_AI_Studio\URL . '/assets/js/admin.js',
				array( 'jquery' ),
				\SG_AI_Studio\VERSION,
				true
			);

			// Get user ID for transient.
			$user_id = get_current_user_id();

			// Get thread_id from request or from user-specific transient.
			$thread_id = get_transient( 'sg_ai_studio_thread_id_' . $user_id );

			// Create i18n service instance.
			$i18n_service = new i18n_Service( 'sg-ai-studio' );

			// Determine current page.
			$current_page = $this->get_current_page();

			wp_localize_script(
				'siteground-ai-studio-admin',
				'WPAIStudioAdminConfig',
				array(
					'config'       => array(
						'home_url'      => get_home_url(),
						'rest_base'     => rtrim( esc_url_raw( rest_url() ), '/' ),
						'localeSlug'    => join( '-', explode( '_', \get_user_locale() ) ),
						'locale'        => $i18n_service->get_i18n_data_json(),
						'wp_nonce'      => wp_create_nonce( 'wp_rest' ),
						'assetsPath'    => SG_AI_Studio\URL . '/assets/',
						'is_siteground' => \SG_AI_Studio\Helper\Helper::is_siteground(),
						'wp_version'    => $wp_version,
					),
					'page'         => $current_page,
					'domElementId' => 'wp-ai-studio-admin-container',
				)
			);
		}
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			// Enqueue the chat script.
			wp_enqueue_script(
				'siteground-ai-studio-chat',
				\SG_AI_Studio\URL . '/assets/js/chat.js',
				array( 'jquery' ),
				\SG_AI_Studio\VERSION,
				true
			);

			// Get user ID for transient.
			$user_id = get_current_user_id();

			// Get thread_id from request or from user-specific transient.
			$thread_id = get_transient( 'sg_ai_studio_thread_id_' . $user_id );

			// This function is required to check for active plugins.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Check if WooCommerce is active to provide contextual suggestions.
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				// --- WOOCOMMERCE-SPECIFIC WELCOME MESSAGE ---
				$welcome_message_string = __(
					"**Hi! I am your WordPress AI Assistant. How can I help you manage your store today?**",
					'sg-ai-studio'
				);

			} else {
				// --- STANDARD WORDPRESS WELCOME MESSAGE ---
				$welcome_message_string = __(
					"**Hi! I am your WordPress AI Assistant. How can I help you manage your site today?**",
					'sg-ai-studio'
				);
			}

			// Check if we're on Gutenberg or Elementor editor.
			$current_screen = get_current_screen();
			$is_editor      = false;

			// Check for Gutenberg editor.
			if ( $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
				$is_editor = true;
			}

			// Check for Elementor editor.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $_GET['action'] ) && 'elementor' === $_GET['action'] ) {
				$is_editor = true;
			}

			// Create i18n service instance.
			$i18n_service = new i18n_Service( 'sg-ai-studio' );

			// Derive the current page context (re-derived on every load, never cached).
			$page_context = Helper::get_page_context();

			// One-shot server flag to force-open the chat bubble on this load, overriding the
			// client-side localStorage minimized state. Armed on-demand during a fleet rollout.
			// Skip in the editor, where the bubble is intentionally kept minimized, so the arming
			// is not consumed on a load that would not open. Cleared once read so it fires exactly
			// once per arming (see WPAITOOLS-73).
			$force_open_chat = ! $is_editor && (bool) get_option( 'sg_ai_studio_force_open_chat', false );
			if ( $force_open_chat ) {
				delete_option( 'sg_ai_studio_force_open_chat' );
			}

			// Localize the script with necessary data.
			$localized_data = array(
				'config'       => array(
					'home_url'         => get_home_url(),
					'rest_base'        => rtrim( esc_url_raw( rest_url() ), '/' ),
					'threadId'         => $thread_id,
					'localeSlug'       => join( '-', explode( '_', \get_user_locale() ) ),
					'locale'           => $i18n_service->get_i18n_data_json(),
					'wp_nonce'         => wp_create_nonce( 'wp_rest' ),
					'assetsPath'       => \SG_AI_Studio\URL . '/assets/',
					'is_staging'       => Helper::is_staging_environment(),
					'welcome_msg'      => $welcome_message_string,
					'minimizeOverride' => $is_editor,
					'forceOpenChat'    => $force_open_chat,
					'plugin_version'   => \SG_AI_Studio\VERSION,
					'wp_version'    => $wp_version,
					'chat_bubble_admin_hidden' => (bool) get_option( 'sg_ai_studio_chat_bubble_admin_hidden', false ),
					'defaultDisplayMode'      => get_option( 'sg_ai_studio_chat_display_mode_admin', 'popover' ),
					'chatSource'       => 'wp_admin_chatbox',
					'quickActions'     => array(
						'categories'   => array(
							array(
								'type'  => 'most-popular',
								'title' => __( 'Most Popular', 'sg-ai-studio' ),
								'icon'  => 'star',
							),
							array(
								'type'  => 'create-and-manage-content',
								'title' => __( 'Create & Manage Content', 'sg-ai-studio' ),
								'icon'  => 'edit_square',
							),
							array(
								'type'  => 'optimize-and-protect',
								'title' => __( 'Optimize & Protect', 'sg-ai-studio' ),
								'icon'  => 'trending_up',
							),
							array(
								'type'  => 'store',
								'title' => __( 'Store', 'sg-ai-studio' ),
								'icon'  => 'shopping_cart',
							),
						),
						'actions'      => array(
							'most-popular'        => array(
								__( 'Write an SEO-friendly blog post with AI images and headings', 'sg-ai-studio' ),
								__( 'Run a full SEO audit of my site', 'sg-ai-studio' ),
								__( 'Speed up my site automatically (with SiteGround Speed Optimizer)', 'sg-ai-studio' ),
							),
							'create-and-manage-content' => array(
								__( 'Create a new page from scratch (with Gutenberg building blocks)', 'sg-ai-studio' ),
								__( 'Improve an existing post - rewrite for SEO and readability', 'sg-ai-studio' ),
								__( 'Create 10 blog post title ideas', 'sg-ai-studio' ),
								__( 'Clean up my site - remove sample pages and spam comments', 'sg-ai-studio' ),
							),
							'optimize-and-protect'   => array(
								__( 'Speed - Optimize site performance (caching, images, CSS via SiteGround Speed Optimizer)', 'sg-ai-studio' ),
								__( 'Secure my site - Apply recommended security hardening (via Security Optimizer)', 'sg-ai-studio' ),
								__( 'Update my site, plugins and themes', 'sg-ai-studio' ),
								__( 'Run a full SEO audit of my site', 'sg-ai-studio' ),
							),
							'store'        => array(
								__( 'Generate sales report for last week including best selling products', 'sg-ai-studio' ),
								__( 'Show pending orders and help me process them', 'sg-ai-studio' ),
								__( 'Create a discount coupon', 'sg-ai-studio' ),
								__( 'Generate product descriptions (for WooCommerce)', 'sg-ai-studio' ),
							),
						),
						'actionsTitle' => __( 'Suggested actions', 'sg-ai-studio' ),
					),
				),
				'page'         => 'chat',
				'domElementId' => 'wp-ai-studio-container',
			);

			// Only expose page_context when the current page could be determined.
			if ( null !== $page_context ) {
				$localized_data['config']['page_context'] = $page_context;
			}

			wp_localize_script(
				'siteground-ai-studio-chat',
				'WPAIStudioConfig',
				$localized_data
			);
		}
		wp_enqueue_media();

	}

	/**
	 * Register the top level page into the WordPress admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_plugin_pages() {
		add_menu_page(
			esc_html__( 'AI Agent', 'sg-ai-studio' ), // Page title.
			esc_html__( 'AI Agent', 'sg-ai-studio' ), // Menu item title.
			'manage_options',
			\SG_AI_Studio\PLUGIN_SLUG,                   // Page slug.
			array( $this, 'render' ),
			\SG_AI_Studio\URL . '/assets/images/icon-20x20.svg'
		);

		// Show Settings and Activity Log subpages once the site is connected.
		if ( ! (bool) get_option( 'sg_ai_studio_connected', false ) ) {
			return;
		}

		foreach ( $this->subpages as $id => $title ) {
			add_submenu_page(
				\SG_AI_Studio\PLUGIN_SLUG,   // Parent slug.
				__($title, 'sg-ai-studio'),
				__($title, 'sg-ai-studio'),
				'manage_options',
				$this->get_subpage_slug( $id ),
				array( $this, 'render' )
			);
		}
	}

	/**
	 * Add styles to WordPress admin head.
	 *
	 * @since  5.2.0
	 * @return void
	 */
	public function admin_print_styles() {
		// This method is intentionally left empty.
	}

	/**
	 * Add floating chat widget to admin footer
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_floating_chat() {
		// Only show for users who can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key = get_option( 'sg_ai_studio_api_key', '' );

		wp_add_inline_script(
			'siteground-ai-studio-chat',
			'jQuery( document ).ready(function() {WPAIStudioChat.init(WPAIStudioConfig);});',
			'after'
		);

		?>
		<div id="wp-ai-studio-container" class="sg-ai-floating-chat <?php echo empty( $api_key ) ? 'no-api-key' : ''; ?>"></div>
		<?php
	}

	/**
	 * Display the admin page.
	 *
	 * @since  5.0.0
	 * @return void
	 */
	public function render() {
		$api_key = get_option( 'sg_ai_studio_api_key', '' );
		wp_add_inline_script(
			'siteground-ai-studio-admin',
			'jQuery( document ).ready(function() {WPAIStudioAdmin.init(WPAIStudioAdminConfig);});',
			'after'
		);
		?>
		<div id="wp-ai-studio-admin-container" class="sg-ai-admin <?php echo empty( $api_key ) ? 'no-api-key' : ''; ?>"></div>
		<?php
	}

	/**
	 * Check if this is the plugin page.
	 *
	 * @since  1.0.0
	 * @return bool True/False
	 */
	public function is_plugin_page() {
		// Bail if the page is not an admin screen.
		if ( ! is_admin() ) {
			return false;
		}

		return in_array( $this->get_requested_page_slug(), $this->get_plugin_page_ids(), true );
	}

	/**
	 * Get the current page slug.
	 *
	 * @since  1.0.0
	 * @return string The current page slug.
	 */
	public function get_current_page() {
		$page_slug = $this->get_requested_page_slug();

		// Check if this is a subpage, and map the slug back to its id.
		foreach ( array_keys( $this->subpages ) as $id ) {
			if ( $this->get_subpage_slug( $id ) === $page_slug ) {
				return $id;
			}
		}

		// Default to 'dashboard' for the main page.
		return 'sg-ai-studio';
	}

	/**
	 * Reorder the submenu pages.
	 *
	 * @since  1.0.0
	 *
	 * @param   array $menu_order The WP menu order.
	 * @return  array The menu order.
	 */
	public function reorder_submenu_pages( $menu_order ) {
		// Load the global submenu.
		global $submenu;
		if ( empty( $submenu['sg-ai-studio'] ) ) {
			return $menu_order;
		}

		$submenu['sg-ai-studio'][0][0] = __( 'Dashboard', 'sg-ai-studio' );
		$submenu['sg-ai-studio'][1][0] = __( 'Settings', 'sg-ai-studio' );
		$submenu['sg-ai-studio'][2][0] = __( 'Activity & Usage', 'sg-ai-studio' );

		return $menu_order;
	}

	/**
	 * Get i18n strings as a JSON-encoded string
	 *
	 * @since 1.0.2
	 *
	 * @return string The locale as JSON
	 */
	public static function get_i18n_data_json() {
		global $wp_filesystem;

		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		// Get the user locale.
		$locale = \get_user_locale();

		// Build the full path to the file.
		$i18n_json = \SG_AI_Studio\DIR . '/languages/json/sg-ai-studio' . '-' . $locale . '.json';

		// Check if the files exists and it's readable.
		if ( $wp_filesystem->is_file( $i18n_json ) && $wp_filesystem->is_readable( $i18n_json ) ) {
			// Get the locale data.
			$locale_data = $wp_filesystem->get_contents( $i18n_json );
			if ( $locale_data ) {
				return $locale_data;
			}
		}

		// Return valid empty Jed locale.
		return json_encode(
			array(
				'' => array(
					'domain' => 'sg-ai-studio',
					'lang'   => is_admin() ? \get_user_locale() : \get_locale(),
				),
			)
		);
	}
}
