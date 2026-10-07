<?php

namespace SiteGround_Central\Steps;
use SiteGround_Helper\Helper_Service;

/**
 * Theme step class.
 */
class ThemeStep extends Step {
	/**
	 * Items Per Page.
	 *
	 * @var array
	 */
	public $items_per_page;

	/**
	 * Excluded Items.
	 *
	 * @var array
	 */
	public $excluded_items;

	/**
	 * WordPress filesystem.
	 *
	 * @since 3.0.0
	 *
	 * @access private
	 *
	 * @var The WP Filesystem.
	 */
	protected $wp_filesystem = null;

	/**
	 * Themes data filename.
	 *
	 * @since 3.0.0
	 *
	 * @var string Path to the themes data filename.
	 */
	const THEMES_DATA = WP_CONTENT_DIR . '/sg_wizard_themes_data.json';

	/**
	 * Themes data updated time.
	 *
	 * @since 3.0.0
	 *
	 * @var int Time when the themes data was updated.
	 */
	const THEMES_DATA_OPTION = 'sg_wizard_themes_data_updated';

	/**
	 * Construct method for ThemeStep class.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type             Type of the step.
	 * @param string $title            Title of the step.
	 * @param string $subtitle         Subtitle of the step.
	 * @param string $button_next_text Text for the next button.
	 * @param int    $items_per_page   Items per page.
	 * @param array  $excluded_items   List of ids of excluded items from the step.
	 * @param bool   $completed        Flag if the step is completed.
	 */
	public function __construct(
		$type,
		$title,
		$subtitle,
		$button_next_text,
		$button_prev_text,
		$items_per_page,
		$excluded_items = array(),
		$completed = false,
		$items,
		$non_ai_flow_skip,
		$do_install
	) {
		parent::__construct( $type, $title, $subtitle, $button_next_text, $button_prev_text, $completed, $items, $non_ai_flow_skip, $do_install );
		$this->set_items_per_page( $items_per_page );
		$this->set_excluded_items( $excluded_items );
		$this->set_url(  \SiteGround_Central\SG_WPAPI_URL . '/themes' );

		// Setup wp_filesystem.
		if ( null === $this->wp_filesystem ) {
			$this->wp_filesystem = Helper_Service::setup_wp_filesystem();
		}
	}

	/**
	 * Returns the items_per_page for the ThemeStep.
	 *
	 * @since 3.0.0
	 *
	 * @return int Items Per page that has been set.
	 */
	public function get_items_per_page() {
		return $this->items_per_page;
	}

	/**
	 * Sets the items_per_page value for the ThemeStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  int $items_per_page Number of items per page.
	 * @return object              The ThemeStep object.
	 */
	public function set_items_per_page( $items_per_page ) {
		$this->items_per_page = $items_per_page;
		return $this;
	}

	/**
	 * Returns the excluded ids for the ThemeStep.
	 *
	 * @since 3.0.0
	 *
	 * @return array Excluded ids.
	 */
	public function get_excluded_items() {
		return $this->excluded_items;
	}

	/**
	 * Sets the array of excluded ids from the ThemeStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $excluded_items List of excluded ids.
	 * @return object                The ThemeStep object.
	 */
	public function set_excluded_items( $excluded_items = array() ) {
		$this->excluded_items = $excluded_items;
		return $this;
	}

	/**
	 * Gets the themes data file.
	 *
	 * @return string Path to the themes data file.
	 */
	public static function get_themes_data_file() {
		return self::THEMES_DATA;
	}

	/**
	 * Gets the themes data option.
	 *
	 * @return string Path to the themes data option.
	 */
	public static function get_themes_data_option() {
		return self::THEMES_DATA_OPTION;
	}

	/**
	 * Retrieves all theme items, based on the step.
	 *
	 * @since 3.0.0
	 *
	 * @return false|array Array with all the items that should be displayed, false if error.
	 */
	public function get_items_list() {
		// Check if there is a file with the themes data.
		if (
			! $this->wp_filesystem->exists( self::THEMES_DATA ) ||
			time() > ( get_option( self::THEMES_DATA_OPTION, false ) + DAY_IN_SECONDS )
		) {
			$this->get_wpwizard_api_themes_list();
		}

		$data = json_decode( $this->wp_filesystem->get_contents( self::THEMES_DATA ), true );

		return $data;
	}

	/**
	 * Gets the wpwizard api themes list.
	 *
	 * @since 3.0.0
	 *
	 * @return false|array Array with all the items that should be displayed, false if error.
	 */
	public function get_wpwizard_api_themes_list() {
		$request = wp_remote_get( $this->url, array( 'sslverify' => false ) );

		// Bail early.
		if ( is_wp_error( $request ) ) {
			return false;
		}

		// Bail if we are unable to create the file.
		if ( false === Helper_Service::create_file( self::THEMES_DATA ) ) {
			return;
		}

		// Add the new content into the file.
		$this->wp_filesystem->put_contents( self::THEMES_DATA, wp_remote_retrieve_body( $request ) );

		update_option( self::THEMES_DATA_OPTION, time() );
	}
}
