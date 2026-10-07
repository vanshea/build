<?php
namespace SiteGround_Central\Wizard;

use SiteGround_Central\Steps\Step;
use SiteGround_Central\Steps\ThemeStep;
use SiteGround_Central\Steps\PluginStep;

/**
 * Wizard functions.
 */
class Wizard {

	/**
	 * List of steps that will be done throughout the wizard.
	 *
	 * @var array
	 */
	public $steps;

	/**
	 * Retrieve the Wizard's steps.
	 *
	 * @since 3.0.0
	 *
	 * @return array The steps of the wizard.
	 */
	public function get_steps() {
		return $this->steps;
	}

	/**
	 * Construct of Wizard class.
	 *
	 * @since 3.0.0
	 *
	 * @param array $steps Wizard Steps.
	 */
	public function __construct( $steps ) {
		foreach ( $steps as $step ) {
			switch ( $step['type'] ) {
				case 'plugins':
					$this->steps[] =
						new PluginStep(
							$step['type'],
							$step['title'],
							$step['subtitle'],
							$step['button_next_text'],
							$step['button_prev_text'],
							$step['items_per_page'],
							$step['category'],
							$step['excluded'],
							$step['completed'],
							$step['preselected'],
							$step['items'],
							$step['non_ai_flow_skip'],
							$step['do_install']
						);
					break;
				case 'themes':
					$this->steps[] =
						new ThemeStep(
							$step['type'],
							$step['title'],
							$step['subtitle'],
							$step['button_next_text'],
							$step['button_prev_text'],
							$step['items_per_page'],
							$step['excluded'],
							$step['completed'],
							$step['items'],
							$step['non_ai_flow_skip'],
							$step['do_install']
						);
					break;
				default:
					$this->steps[] =
						new Step(
							$step['type'],
							$step['title'],
							$step['subtitle'],
							$step['button_next_text'],
							$step['button_prev_text'],
							$step['completed'],
							$step['items'],
							$step['non_ai_flow_skip'],
							$step['do_install']
						);
					break;
			}
		}
	}

	/**
	 * Get correct site wizard, based on options or pre-defined plugins.
	 *
	 * @since  3.0.0
	 *
	 * @return object Wizard object based on the site setup.
	 */
	public static function get_wizard() {
		return include \SiteGround_Central\DIR . '/core/Wizard/config/' . self::get_wizard_name() . 'Wizard.php';
	}

	/**
	 * Get correct site wizard name, based on options or pre-defined plugins.
	 *
	 * @since  3.0.0
	 *
	 * @return string Wizard object based on the site setup.
	 */
	public static function get_wizard_name() {
		if ( 1 === intval( get_option( 'sg_wp_starter_edd', 0 ) ) ) {
			return 'Edd';
		}

		if ( 1 === intval( get_option( 'sg_wp_starter_woo', 0 ) ) ) {
			return 'Woo';
		}

		if ( get_option( 'siteground_wizard_ai_flow', false ) ) {
			return 'AI';
		}

		return 'Default';
	}

	/**
	 * Retrieves the first occurrence of a type in the steps array.
	 *
	 * @since 3.0.0
	 *
	 * @param  $type    The step type.
	 *
	 * @return int|bool The step index, false if not found.
	 */
	public static function get_step_index_by_type( $type ) {
		$wizard = Wizard::get_wizard();

		foreach( $wizard->get_steps() as $index => $step ) {
			if ( $step->type === $type ) {
				return $index;
			}
		}
		return false;
	}

	/**
	 * Retrieves all occurrences of a type in the steps array.
	 *
	 * @since 3.0.0
	 *
	 * @param  $type    The step type.
	 *
	 * @return array    The step index, false if not found.
	 */
	public static function get_all_step_index_by_type( $type ) {
		$wizard = Wizard::get_wizard();
		$steps  = array();
		foreach( $wizard->get_steps() as $index => $step ) {
			if ( $step->type === $type ) {
				$steps[] = $index;
			}
		}

		return $steps;
	}

	/**
	 * Determines if wizard is completed for the site.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if completed, False if not.
	 */
	public static function is_wizard_completed() {
		// Check Wizard Status.
		$status = ! \is_multisite() ? get_option( 'siteground_wizard_installation_status' ) : get_site_option( 'siteground_wizard_installation_status' );

		if (
			! empty( $status ) &&
			'completed' === $status['status']
		) {
			return true;
		}

		return false;
	}
}
