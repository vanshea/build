<?php

namespace SiteGround_Optimizer\Performance_Reports;

use SiteGround_Helper\Helper_Service;
use SiteGround_Emails\Email_Service;
use SiteGround_Optimizer\Heartbeat_Control\Heartbeat_Control;
use SiteGround_Optimizer\Analysis\Analysis;

/**
 * Performance Reports class.
 */
class Performance_Reports {


	/**
	 * The Performance Reports email.
	 *
	 * @var Email_Service
	 */
	public $performance_reports_email;

	/**
	 * The Constructor.
	 *
	 * @since 7.4.0
	 */
	public function __construct() {
		$current_date    = time();
		$cron_first_time = ( gmdate( 'j', $current_date ) <= 20 ) ? strtotime( '20 ' . gmdate( 'M Y', $current_date ) ) : strtotime( '20 ' . gmdate( 'M Y', strtotime( '+1 month', $current_date ) ) );

		// Initiate the Email Service Class.
		$this->performance_reports_email = new Email_Service(
			'siteground_optimizer_performance_report_cron',
			'sg_once_a_month',
			$cron_first_time,
			array(
				'recipients_option' => 'siteground_optimizer_performace_receipient',
				'subject'           => 'Optimization Status Report for ' . Helper_Service::get_site_url(),
				'body_method'       => array( '\SiteGround_Optimizer\Performance_Reports\Performance_Reports', 'generate_message_body' ),
				'from_name'         => 'Speed Optimizer by SiteGround Plugin',
			)
		);
	}

	/**
	 * Generate the message body and return it to the constructor.
	 *
	 * @since 7.4.0
	 *
	 * @return string $message_body HTML of the message body.
	 */
	static function generate_message_body() {
		$performance_reports = new Performance_Reports();

		// Get assets from remote server.
		$assets = $performance_reports->get_remote_assets();

		if ( false === $assets ) {
			return false;
		}

		$features_scores = $performance_reports->generate_features_report( $assets['features'] );
		$final_score = $performance_reports->get_performance_score( $features_scores );

		// Mail template arguments.
		$args = array(
			'image'              => $assets['image'],
			'domain'             => Helper_Service::get_site_url(),
			'summary_1'          => $assets['email_body']['summary_part_1'],
			'summary_2'          => $assets['email_body']['summary_part_2'],
			'percentage'         => ( $final_score / $assets['max_score'] ) * 100,
			'score'              => $final_score,
			'total_score'        => $assets['max_score'],
			'features'           => $features_scores,
			'unsubscribe_text'   => $assets['unsubscribe']['text'],
			'unsubscribe_button' => $assets['unsubscribe']['button'],
			'unsubscribe_link'   => admin_url( '/admin.php?page=sgo_analysis' ),
		);

		// Turn on output buffering.
		ob_start();

		// Include the template file.
		include \SiteGround_Optimizer\DIR . '/templates/performance_reports/performance_reports.php';

		// Pass the contents of the output buffer to the variable.
		$message_body = ob_get_contents();

		// Clean the output buffer and turn off output buffering.
		ob_end_clean();

		// Return the message body content as a string.
		return $message_body;
	}

	/**
	 * Get assets from remote JSON.
	 *
	 * @since 7.4.0
	 *
	 * @return bool/array false if we fail the request/Array with data.
	 */
	private function get_remote_assets() {
		// Get the banner content.
		$response = wp_remote_get( 'https://sgwpdemo.com/jsons/sg-cachepress-performance-reports.json' );

		// Bail if the request fails.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// Get the locale.
		$locale = get_locale();

		// Get the body of the response.
		$body = wp_remote_retrieve_body( $response );

		// Decode the json response.
		$assets = json_decode( $body, true );

		// Check if we need to return a specific locale assets.
		if ( array_key_exists( $locale, $assets ) ) {
			// Add the locale name so we skip re-use of get_locale in message builder.
			$assets[ $locale ]['lang'] = $locale;

			// Return the locale specific assets.
			return $assets[ $locale ];
		}

		// Set the default locale.
		$assets['default']['lang'] = 'default';

		// Return the correct assets, title and marketing URLs.
		return $assets['default'];
	}

	/**
	 * Gets the performance score.
	 *
	 * @since 7.4.0
	 *
	 * @param      array $features  The features fetched from the config.
	 * @return     int  The performance score.
	 */
	public function get_performance_score( $features ) {
		$score = 0;

		foreach ( $features as $feature ) {
			if ( empty( $feature['count'] ) ) {
				continue;
			}

			$score += $feature['score'];
		}

		return $score;
	}

	/**
	 * Generate features report
	 *
	 * @since 7.4.0
	 *
	 * @param      array $features  The features fetched from the config.
	 */
	public function generate_features_report( $features ) {
		$report_array = array();

		foreach ( $features as $key => $feature ) {
			// Score - 0 out of 3/Disabled/90 out of 100.
			$score = $this->detect_feature_score( $feature );

			// Remove the feature from the report in case the feature report generation fails.
			if ( false === $score ) {
				continue;
			}

			// Status - success/warning/error.
			$status = $this->detect_feature_status( $key, $score );

			$report_array[ $key ] = array(
				'status'      => $status,
				'title'       => $feature['title'],
				'score'       => $score['score'],
				'max_score'   => $score['total'],
				'score_text'  => $score['text'],
				'count'       => $feature['config']['count'],
				'text'        => $feature[ $status ]['text'],
				'button_text' => $feature[ $status ]['button_text'],
				'button_link' => Helper_Service::get_home_url() . '' . $feature[ $status ]['button_link'],
			);

			if (
				'caching' === $key &&
				0 !== $score['score']
			) {
				$report_array['caching']['button_link'] = $feature[ $status ]['button_link'];
			}
		}

		return $report_array;
	}

	/**
	 * Detect feature score
	 *
	 * @since 7.4.0
	 *
	 * @param     array $feature  The feature config
	 * @return    string  Feature score. Can be 0 out of 3/90 out of 100/Disabled.
	 */
	public function detect_feature_score( $feature ) {
		if ( $feature['config']['custom'] === false ) {
			return $this->calculate_default_score( $feature['config'] );
		}

		// Custom features calculations.
		switch ( $feature['config']['key'] ) {
			case 'environment':
				$heartbeat_control = new Heartbeat_Control();

				$ssl = (int) get_option( 'siteground_optimizer_ssl_enabled', 0 );
				$heartbeat = (int) $heartbeat_control->is_enabled();
				$db_maintanance = empty( array_values( get_option( 'siteground_optimizer_database_optimization', array() ) ) ) ? 0 : 1;

				$score = $ssl + $heartbeat + $db_maintanance;

				return array(
					'score' => $score,
					'total' => $feature['config']['max_score'],
					'text'  => $score . __( ' out of ', 'sg-cachepress' ) . $feature['config']['max_score'],
				);

			break;

			case 'speed':
				// Run speed test for the website.
				$analysis = new Analysis();
				$result = $analysis->run_analysis( '' );

				if (
					empty( $result ) ||
					empty( $result['scores']['score']['score'] )
				) {
					return false;
				}

				$score = (int) $result['scores']['score']['score'];

				return array(
					'score' => $score,
					'total' => $feature['config']['max_score'],
					'text'  => $score . __( ' out of ', 'sg-cachepress' ) . $feature['config']['max_score'],
				);

			break;

			case 'updates':
				$plugins_with_auto_updates_enabled = get_option( 'auto_update_plugins', array() );

				if (
					empty( $plugins_with_auto_updates_enabled ) ||
					! in_array( 'sg-cachepress/sg-cachepress.php', $plugins_with_auto_updates_enabled )
				) {
					return array(
						'score' => 0,
						'total' => $feature['config']['max_score'],
						'text'  => __( 'Disabled', 'sg-cachepress' ),
					);
				}

				return false;
			break;
		}

		return false;
	}

	/**
	 * Calculates the default score.
	 *
	 * @since 7.4.0
	 *
	 * @param      string $feature_config  The feature config
	 */
	public function calculate_default_score( $feature_config ) {
		$score = 0;

		// Get the feature options.
		foreach ( $feature_config['options'] as $key => $option ) {
			if ( 1 === (int) get_option( $option, 0 ) ) {
				$score += 1;
			}
		}

		return array(
			'score' => $score,
			'total' => $feature_config['max_score'],
			'text'  => $score . __( ' out of ', 'sg-cachepress' ) . $feature_config['max_score'],
		);
	}

	/**
	 * Detect feature status
	 *
	 * @since 7.4.0
	 *
	 * @param     array $feature_key  The feature key
	 * @param     array $score        The feature score
	 *
	 * @return    string  Feature status. Can be success/warning/error.
	 */
	public function detect_feature_status( $feature_key, $score ) {
		if ( 'speed' === $feature_key ) {
			$label_map = array(
				array(
					'min' => 90,
					'max' => 100,
					'label' => 'success',
				),
				array(
					'min' => 50,
					'max' => 89,
					'label' => 'warning',
				),
				array(
					'min' => 0,
					'max' => 49,
					'label' => 'error',
				),
			);

			foreach ( $label_map as $map ) {
				if ( $score['score'] >= $map['min'] && $score['score'] <= $map['max'] ) {
					return $map['label'];
				}
			}
		}

		if (
			$score['score'] === 0 ||
			$score['score'] === 'Disabled'
		) {
			return 'error';
		}

		if ( $score['score'] === $score['total'] ) {
			return 'success';
		}

		return 'warning';
	}

	/**
	 * Update the performance report recipient when admin email is updated.
	 *
	 * @since 7.4.0
	 *
	 * @param      mixed  $old_value  The old value
	 * @param      mixed  $new_value  The new value
	 * @param      string $option     The option
	 */
	public function update_receipient( $old_value, $new_value, $option ) {
		// Get the current receipient for the performance report.
		$current_receipient = get_option( 'siteground_optimizer_performace_receipient', false );

		// Bail if there is no receipients set.
		if ( empty( $current_receipient ) ) {
			return;
		}

		// Bail if the recipient does not match the previous admin email address set.
		if ( $old_value !== $current_receipient ) {
			return;
		}

		// Update the recipient to the new admin email set.
		update_option( 'siteground_optimizer_performace_receipient', $new_value );
	}

	/**
	 * This function registers monthly interval for the CRON.
	 *
	 * @since 7.4.0
	 *
	 * @param  array $schedules An array with the already defined schedules.
	 *
	 * @return array            An array with the modified schedules.
	 */
	public function sg_add_cron_interval( $schedules ) {
		// Add the custom interval.
		$schedules['sg_once_a_month'] = array(
			'interval' => MONTH_IN_SECONDS,
			'display'  => esc_html__( 'Once Monthly' ),
		);

		return $schedules;
	}

}
