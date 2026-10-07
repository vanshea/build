<?php

namespace SiteGround_Central\Steps;

/**
 * Step class.
 */
class Step {

	/**
	 * Step type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Step title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Step subtitle.
	 *
	 * @var string
	 */
	public $subtitle;

	/**
	 * Text for the next button of the step.
	 *
	 * @var string
	 */
	public $button_next_text;

	/**
	 * Text for the previous button of the step.
	 *
	 * @var string
	 */
	public $button_prev_text;

	/**
	 * Flag if the step is completed or not.
	 *
	 * @var bool
	 */
	public $completed;

	/**
	 * URL that the step will use to retrieve items.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Items that will be prepared by this step.
	 *
	 * @var array
	 */
	public $items;

	/**
	 *  Flag for skipping this step if not AI.
	 *
	 * @var bool
	 */
	public $non_ai_flow_skip;

	/**
	 *  Flag for proceeding to install step.
	 *
	 * @var bool
	 */
	public $do_install;

	/**
	 * Construct method for Step class.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type             Type of the step.
	 * @param string $title            Title of the step.
	 * @param string $subtitle         Subtitle of the step.
	 * @param string $button_next_text Text for the next button.
	 * @param string $button_prev_text Text for the preview button.
	 * @param bool   $completed        Flag if the step is completed.
	 * @param array  $items            The items that will be shown for the step.
	 * @param bool   $non_ai_flow_skip Skip this step if the flow is not AI.
	 * @param bool   $do_install       Proceed to install step.
	 */
	public function __construct(
		$type,
		$title = '',
		$subtitle = '',
		$button_next_text = '',
		$button_prev_text = '',
		$completed = false,
		$items = array(),
		$non_ai_flow_skip = false,
		$do_install = false
	) {
		$this->set_type( $type );
		$this->set_title( $title );
		$this->set_subtitle( $subtitle );
		$this->set_button_next_text( $button_next_text );
		$this->set_button_prev_text( $button_prev_text );
		$this->set_completed( $completed );
		$this->set_items( $items );
		$this->set_non_ai_flow_skip ( $non_ai_flow_skip );
		$this->set_do_install ( $do_install );
	}

	/**
	 * Returns the type for the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string The type that has been selected.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Sets the type for the step.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $type The type of the step.
	 *
	 * @return object       The Step object.
	 */
	public function set_type( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * Returns the title of the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string The title that has been selected.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Sets the title for the Step.
	 *
	 * @since 3.0.0
	 *
	 * @param string $title The title of the step.
	 *
	 * @return object       The Step object.
	 */
	public function set_title( $title ) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Returns the subtitle of the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string The subtitle of the step.
	 */
	public function get_subtitle() {
		return $this->subtitle;
	}

	/**
	 * Sets the subtitle for the Step.
	 *
	 * @since 3.0.0
	 *
	 * @param string $subtitle The subtitle of the step.
	 *
	 * @return object          The Step object.
	 */
	public function set_subtitle( $subtitle ) {
		$this->subtitle = $subtitle;

		return $this;
	}

	/**
	 * Returns the text of the next button for the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string The text of the next button for the step.
	 */
	public function get_button_next_text() {
		return $this->button_next_text;
	}

	/**
	 * Sets the text for the next button for the Step.
	 *
	 * @since 3.0.0
	 *
	 * @param string $button_next_text The text for the next button of the step.
	 *
	 * @return object                  The Step object.
	 */
	public function set_button_next_text( $button_next_text ) {
		$this->button_next_text = $button_next_text;

		return $this;
	}

	/**
	 * Returns the text of the previous button for the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string The text of the next button for the step.
	 */
	public function get_button_prev_text() {
		return $this->button_prev_text;
	}

	/**
	 * Sets the text for the previous button for the Step.
	 *
	 * @since 3.0.0
	 *
	 * @param string $button_prev_text The text for the previous button of the step.
	 *
	 * @return object                  The Step object.
	 */
	public function set_button_prev_text( $button_prev_text ) {
		$this->button_prev_text = $button_prev_text;

		return $this;
	}

	/**
	 * Returns the completed flag of the step
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if completed, otherwise false.
	 */
	public function get_completed() {
		return $this->completed;
	}

	/**
	 * Sets if the step is completed.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $completed True/false if the step is completed or not.
	 *
	 * @return object         The Step object.
	 */
	public function set_completed( $completed ) {
		$this->completed = $completed;

		return $this;
	}

	/**
	 * Sets the items for the step.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $items Array of items for the speicifc step.
	 *
	 * @return object       The Step object.
	 */
	public function set_items( $items ) {
		$this->items = $items;

		return $this;
	}

	/**
	 * Retrieves all items, based on the step.
	 *
	 * @since 3.0.0
	 *
	 * @return false|array Array with all the items that should be displayed, false if error.
	 */
	public function get_items_list() {
		return $this->items;
	}

	/**
	 * Returns the URL of the step.
	 *
	 * @since 3.0.0
	 *
	 * @return string URL used by the step.
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Sets the URL for the step.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $url  The URL used by the step.
	 *
	 * @return object       The Step object.
	 */
	public function set_url( $url ) {
		$this->url = $url;

		return $this;
	}

	/**
	 * Sets the flag for skipping if not AI flow.
	 *
	 * @since 3.0.0
	 *
	 * @param  bool|null $non_ai_flow_skip  The true/false value.
	 *
	 * @return object                    The Step object.
	 */
	public function set_non_ai_flow_skip( $non_ai_flow_skip ) {
		$this->non_ai_flow_skip = $non_ai_flow_skip;

		return $this;
	}

	/**
	 * Gets the flag for skipping if not AI flow.
	 *
	 * @since 3.0.0
	 *
	 * @return object                    The Step object.
	 */
	public function get_non_ai_flow_skip() {
		return $this->non_ai_flow_skip;
	}

	/**
	 * Sets the flag for proceeding to installation.
	 *
	 * @since 3.0.0
	 *
	 * @param  bool|null $do_install  The true/false value.
	 *
	 * @return object                 The Step object.
	 */
	public function set_do_install( $do_install ) {
		$this->do_install = $do_install;

		return $this;
	}

	/**
	 * Gets the flag for proceeding to installation.
	 *
	 * @since 3.0.0
	 *
	 * @param  bool|null $do_install  The true/false value.
	 *
	 * @return object                 The Step object.
	 */
	public function get_do_install() {
		return $this->do_install;
	}
}
