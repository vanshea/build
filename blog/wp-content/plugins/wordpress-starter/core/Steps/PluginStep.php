<?php

namespace SiteGround_Central\Steps;

use SiteGround_Helper\Helper_Service;
use SiteGround_Central\Traits\Sco_Exclude_Trait;
/**
 * PluginStep class.
 */
class PluginStep extends Step {
	use Sco_Exclude_Trait;

	/**
	 * Category of the step.
	 *
	 * @var string
	 */
	public $category;

	/**
	 * Items per page.
	 *
	 * @var array
	 */
	public $items_per_page;

	/**
	 * List of excluded items of the step.
	 *
	 * @var array
	 */
	public $excluded_items;

	/**
	 * List of items that should be preselected for the user.
	 *
	 * @var array
	 */
	public $preselected;

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
	 * Plugins data filename.
	 *
	 * @since 3.0.0
	 *
	 * @var string Path to the plugins data filename.
	 */
	const PLUGINS_DATA = WP_CONTENT_DIR . '/sg_wizard_plugins_data.json';

	/**
	 * Plugins data updated time.
	 *
	 * @since 3.0.0
	 *
	 * @var int Time when the plugins data was updated.
	 */
	const PLUGINS_DATA_OPTION = 'sg_wizard_plugins_data_updated';

	/**
	 * Construct method for Step class.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type             Type of the step.
	 * @param string $title            Title of the step.
	 * @param string $subtitle         Subtitle of the step.
	 * @param string $button_next_text Text for the next button.
	 * @param string $button_prev_text Text for the prev button.
	 * @param int    $items_per_page   Items per page.
	 * @param string $category         Category of the step.
	 * @param array  $excluded_items   List of ids of excluded items from the step.
	 * @param bool   $completed        Flag if the step is completed.
	 * @param array  $preselected      List of ids for items that should be pre-selected.
	 * @param array  $items            The items that will be shown for the step.
	 * @param bool   $non_ai_flow_skip Skip this step if the flow is not AI.
	 * @param bool   $do_install       Flag if we need to proceed with install.
	 */
	public function __construct(
		$type,
		$title,
		$subtitle,
		$button_next_text,
		$button_prev_text,
		$items_per_page,
		$category,
		$excluded_items = array(),
		$completed = false,
		$preselected = array(),
		$items,
		$non_ai_flow_skip,
		$do_install
	) {
		parent::__construct( $type, $title, $subtitle, $button_next_text, $button_prev_text, $completed, $items, $non_ai_flow_skip, $do_install );
		$this->set_items_per_page( $items_per_page );
		$this->set_category( $category );
		$this->set_excluded_items( $excluded_items );
		$this->set_url( \SiteGround_Central\SG_WPAPI_URL . '/plugins' );
		$this->set_preselected( $preselected );

		// Setup wp_filesystem.
		if ( null === $this->wp_filesystem ) {
			$this->wp_filesystem = Helper_Service::setup_wp_filesystem();
		}
	}

	/**
	 * Returns the category for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @return string Category that has been selected.
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Sets the category for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $category The category of the step.
	 *
	 * @return object           The PluginStep object.
	 */
	public function set_category( $category ) {
		$this->category = $category;
		return $this;
	}

	/**
	 * Returns the items_per_page for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @return int Items Per page that has been set.
	 */
	public function get_items_per_page() {
		return $this->items_per_page;
	}

	/**
	 * Sets the items_per_page value for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $items_per_page The items per page for this step.
	 *
	 * @return object                The PluginStep object.
	 */
	public function set_items_per_page( $items_per_page ) {
		$this->items_per_page = $items_per_page;
		return $this;
	}

	/**
	 * Returns the excluded ids for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @return array Excluded ids.
	 */
	public function get_excluded_items() {
		return $this->excluded_items;
	}

	/**
	 * Sets the array of excluded ids from the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $excluded_items List of excluded ids.
	 * @return object                The PluginStep object.
	 */
	public function set_excluded_items( $excluded_items = array() ) {
		$this->excluded_items = $excluded_items;
		return $this;
	}

	/**
	 * Returns the pre-selected ids for the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @return array Pre-selected ids.
	 */
	public function get_preselected() {
		return $this->preselected;
	}

	/**
	 * Sets the array of pre-selected ids from the PluginStep.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $preselected    List of pre-selected ids.
	 * @return object                The PluginStep object.
	 */
	public function set_preselected( $preselected = array() ) {
		$this->preselected = $preselected;
		return $this;
	}

	/**
	 * Gets the plugins data file.
	 *
	 * @return string Path to the plugins data file.
	 */
	public static function get_plugins_data_file() {
		return self::PLUGINS_DATA;
	}

	/**
	 * Gets the plugins data option.
	 *
	 * @return string Path to the plugins data option.
	 */
	public static function get_plugins_data_option() {
		return self::PLUGINS_DATA_OPTION;
	}

	/**
	 * Retrieves all plugin items, filtered based on the step.
	 *
	 * @since 3.0.0
	 *
	 * @return false|array Array with all the items that should be displayed, false if error.
	 */
	public function get_items_list() {
		// Check if there is a file with the plugins data.
		if (
			! $this->wp_filesystem->exists( self::PLUGINS_DATA ) ||
			time() > ( get_option( self::PLUGINS_DATA_OPTION, false ) + DAY_IN_SECONDS )
		) {
			$this->get_wpwizard_api_plugins_list();
		}

		$data = json_decode( $this->wp_filesystem->get_contents( self::PLUGINS_DATA ), true );

		// Filter the entries to fit the category we have in the step.
		$filtered_array = array_filter(
			$data,
			function( $entry ) {
				return $entry['category'] === $this->get_category();
			}
		);

		return $filtered_array;
	}

	/**
	 * Gets the wpwizard api plugins list.
	 *
	 * @since 3.0.0
	 *
	 * @return false|array Array with all the items that should be displayed, false if error.
	 */
	public function get_wpwizard_api_plugins_list() {
		$response = wp_remote_get( $this->url, array( 'sslverify' => false ) );

		// Bail early.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Bail if we are unable to create the file.
		if ( false === Helper_Service::create_file( self::PLUGINS_DATA ) ) {
			return false;
		}

		$plugins = json_decode( wp_remote_retrieve_body( $response ), true );

		// Exclude items for SCO.
		$plugins = $this->maybe_exclude_items(
			$this->sco_plugin_excludes,
			$plugins
		);

		// Exclude items based on language.
		$plugins = $this->maybe_exclude_items_for_lang(
			$this->lang_plugin_excludes,
			$plugins
		);

		// Add the new content into the file.
		$this->wp_filesystem->put_contents( self::PLUGINS_DATA, json_encode( $plugins ) );

		update_option( self::PLUGINS_DATA_OPTION, time() );
	}
}
