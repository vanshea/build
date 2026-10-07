<?php
/**
 * Admin class for managing admin interface
 *
 * @package SG_AI_Studio
 */

namespace SG_AI_Studio\Admin;

use SG_AI_Studio;
use SG_AI_Studio\Helper\Helper;

/**
 * Handle all hooks for our custom admin page.
 */
class Admin {

	/**
	 * Get the subpages id.
	 *
	 * @since  1.0.0
	 *
	 * @return array The subpages id's array.
	 */
	public function get_plugin_page_ids() {
		return array(
			'toplevel_page_sg-ai-studio',
			'toplevel_page_sg-ai-studio-network',
		);
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
					'siteground-ai-studio-settings',
					\SG_AI_Studio\URL . '/assets/css/settings.css',
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
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function enqueue_scripts() {

		// Bail if we are on different page.
		if ( false !== $this->is_plugin_page() ) {
			// Enqueue the chat script.
			wp_enqueue_script(
				'siteground-ai-studio-settings',
				\SG_AI_Studio\URL . '/assets/js/settings.js',
				array( 'jquery' ),
				\SG_AI_Studio\VERSION,
				true
			);

			// Get user ID for transient.
			$user_id = get_current_user_id();

			// Get thread_id from request or from user-specific transient.
			$thread_id = get_transient( 'sg_ai_studio_thread_id_' . $user_id );

			// Localize the script with necessary data for settings page.
			wp_localize_script(
				'siteground-ai-studio-settings',
				'WPAIStudioSettingsConfig',
				array(
					'config'       => array(
						'home_url'   => get_home_url(),
						'rest_base'  => rtrim( esc_url_raw( rest_url() ), '/' ),
						'localeSlug' => join( '-', explode( '_', \get_user_locale() ) ),
						'locale'     => self::get_i18n_data_json(),
						'wp_nonce'   => wp_create_nonce( 'wp_rest' ),
						'assetsPath' => SG_AI_Studio\URL . '/assets/',
					),
					'page'         => 'settings',
					'domElementId' => 'wp-ai-studio-settings-container',
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

			// Localize the script with necessary data.
			wp_localize_script(
				'siteground-ai-studio-chat',
				'WPAIStudioConfig',
				array(
					'config'       => array(
						'home_url'         => get_home_url(),
						'rest_base'        => rtrim( esc_url_raw( rest_url() ), '/' ),
						'threadId'         => $thread_id,
						'localeSlug'       => join( '-', explode( '_', \get_user_locale() ) ),
						'locale'           => self::get_i18n_data_json(),
						'wp_nonce'         => wp_create_nonce( 'wp_rest' ),
						'assetsPath'       => \SG_AI_Studio\URL . '/assets/',
						'is_staging'       => Helper::is_staging_environment(),
						'welcome_msg'      => $welcome_message_string,
						'minimizeOverride' => $is_editor,
						'plugin_version'   => \SG_AI_Studio\VERSION,
						'quickActions'     => array(
							'categories'   => array(
								array(
									'type'  => 'most-popular',
									'title' => __( 'Most Popular', 'sg-ai-studio' ),
									'icon'  => 'star',
								),
								array(
									'type'  => 'create-and-generate',
									'title' => __( 'Create & Generate', 'sg-ai-studio' ),
									'icon'  => 'edit_square',
								),
								array(
									'type'  => 'audit-and-ptimize',
									'title' => __( 'Audit & Optimize', 'sg-ai-studio' ),
									'icon'  => 'trending_up',
								),
								array(
									'type'  => 'bulk-actions',
									'title' => __( 'Bulk Actions', 'sg-ai-studio' ),
									'icon'  => 'check_box',
								),
							),
							'actions'      => array(
								'most-popular'        => array(
									__( 'Write a SEO-friendly blog post with AI images and headings', 'sg-ai-studio' ),
									__( 'Speed up my site automatically (with SiteGround Speed Optimizer)', 'sg-ai-studio' ),
									__( 'Generate sales report for last week including best selling products', 'sg-ai-studio' ),
								),
								'create-and-generate' => array(
									__( 'Write a blog post with images and SEO', 'sg-ai-studio' ),
									__( 'Create a new page from scratch (with Gutenberg building blocks)', 'sg-ai-studio' ),
									__( 'Generate product descriptions (for WooCommerce)', 'sg-ai-studio' ),
									__( 'Create 10 blog post title ideas', 'sg-ai-studio' ),
								),
								'audit-and-ptimize'   => array(
									__( 'Speed - Optimize site performance (caching, images, CSS via SiteGround Speed Optimizer)', 'sg-ai-studio' ),
									__( 'Security - Check site security status (via Security Optimizer)', 'sg-ai-studio' ),
									__( 'Run full SEO audit of my site', 'sg-ai-studio' ),
									__( 'Check if my site, plugins and themes are up-to-date', 'sg-ai-studio' ),
								),
								'bulk-actions'        => array(
									__( 'Create 5 blog post drafts at once', 'sg-ai-studio' ),
									__( 'Apply a 20% discount to all products in category (keeping Regular price unchanged)', 'sg-ai-studio' ),
									__( 'Delete all spam comments', 'sg-ai-studio' ),
									__( 'Create 3 new parent post categories with 5 sub-categories for each', 'sg-ai-studio' ),
								),
							),
							'actionsTitle' => __( 'Suggested actions', 'sg-ai-studio' ),
						),
					),
					'page'         => 'chat',
					'domElementId' => 'wp-ai-studio-container',
				)
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
			esc_html__( 'AI Studio Agent', 'sg-ai-studio' ), // Page title.
			esc_html__( 'AI Studio Agent', 'sg-ai-studio' ), // Menu item title.
			'manage_options',
			\SG_AI_Studio\PLUGIN_SLUG,                   // Page slug.
			array( $this, 'render' ),
			\SG_AI_Studio\URL . '/assets/images/icon-20x20.svg'
		);
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
			'siteground-ai-studio-settings',
			'jQuery( document ).ready(function() {WPAIStudioSettings.init(WPAIStudioSettingsConfig);});',
			'after'
		);
		?>
		<div id="wp-ai-studio-settings-container" class="sg-ai-settings <?php echo empty( $api_key ) ? 'no-api-key' : ''; ?>"></div>
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

		$current_screen = get_current_screen();

		if ( in_array( $current_screen->id, $this->get_plugin_page_ids(), true ) ) {
			return true;
		}

		return false;
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
		$i18n_json = \SG_AI_Studio\DIR . '/languages/sg-ai-studio' . '-' . $locale . '.json';

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

	/**
	 * Loads the textdomain for the plugin.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Get the user locale.
		$locale = \get_user_locale();
	}

}
