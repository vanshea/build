<?php
namespace SiteGround_Central\Rest\Controllers\V1;

use SiteGround_Central\Wizard\Wizard;

/**
 * Class responsible for getting Steps data through a REST API.
 */
class Rest_Steps extends Rest {
	/**
	 * Register the routes for steps.
	 *
	 * @since  3.0.0
	 */
	public function register_routes() {
		// Add the get-step GET request.
		register_rest_route(
			'siteground-central/v1',
			'/get-step(?:/(?P<id>\d+))?',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_step' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id' => array(),
				),
			)
		);
		// Add the save-step POST request.
		register_rest_route(
			'siteground-central/v1',
			'/save-step/',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'save_step' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		// Add the save-step POST request.
		register_rest_route(
			'siteground-central/v1',
			'/restart/',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'restart' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * Get the step by providing an ID.
	 *
	 * @since 3.0.0
	 *
	 * @param  \WP_REST_Request $request                     Full details about the request.
	 * @param  boolean          $get_items                   True if steop's items are to be retrieved, false otherwise.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response Response object.
	 */
	public function get_step( $request, $get_items = true ) {
		// Get the request params.
		$params = $request->get_params( $request );

		$id = isset( $params['id'] ) ? $params['id'] : get_option( 'siteground_wizard_next_step', 0 );

		// Get wizard name and wizard steps.
		$wizard_name = Wizard::get_wizard_name();
		$steps = Wizard::get_wizard()->get_steps();

		// Validate the step ID requested.
		if ( ! array_key_exists( intval( $id ), $steps ) ) {
			return self::send_response( 'Invalid step ID', 0 );
		}

		// Set default responses, if steps is not found or index is not present.
		$response_step = array();
		$step_index = 0;
		$prev_step_id = 0;
		$next_step_id = "complete";

		// Iterate all steps and find the step we are looking for.
		foreach( $steps as $index => $step ) {
			if ( $index === intval( $id ) ) {
				$response_step = $step;
				$step_index    = $index;

				// Check if the selected step is the first one.
				if ( 0 !== $step_index ) {
					$prev_step_id = $index - 1;
				}

				// Check if the selected step is the last one.
				if ( count( $steps ) - 1 !== $index ) {
					$next_step_id = $index + 1;
				}

				// Check if the step is the ai_flow_skip one, if so, check params and edit next step, if needed.
				if ( $steps[ $next_step_id ]->non_ai_flow_skip === true && empty( $params[ 'ai_flow' ] ) ) {
					$next_step_id = $next_step_id + 1;
				}

				// Check if the previous step is the ai_flow_skip one, if so, edit the previous step id.
				if ( $steps[ $prev_step_id ]->non_ai_flow_skip === true ) {
					$prev_step_id = $prev_step_id - 1;
				}

				$next_step_type = $steps[ $next_step_id ]->type;
				$prev_step_type = $steps[ $prev_step_id ]->type;

				$recommended_plugins_popup = (object) array();

				// Add recommended plugins' info.
				if ( 'plugins' === $step->type ) {
					$recommended_plugins_popup = $this->check_for_recommended_plugins();

					// Go through all recommended plugins and add them to the preselected plugins.
					foreach( $recommended_plugins_popup as $recommended_plugin ) {
						$step->preselected[] = $recommended_plugin['recommended_plugin_id'];
					}
				}

				// Check if next step is install step.
				if ( $steps[ $step_index ]->do_install === true ) {
					$next_step_id = 'install';
					$next_step_type = '';
				}
			}
		}

		if ( $get_items === true ) {
			$response_step->items = array_values( $response_step->get_items_list() );
		}

		$response = array(
			'wizard_type'               => $wizard_name,
			'step_index'                => $step_index,
			'step'                      => $response_step,
			'next_step_id'              => $next_step_id,
			'next_step_type'            => $next_step_type,
			'prev_step_id'              => $prev_step_id,
			'prev_step_type'            => $prev_step_type,
			'recommended_plugins_popup' => $recommended_plugins_popup,
		);

		// Send the response.
		return self::send_response( $response );
	}

	/**
	 * Save the current progress for a step by providing an id parameter.
	 *
	 * @since 3.0.0
	 *
	 * @param  \WP_REST_Request $request                     Full details about the request.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response Response object.
	 */
	public function save_step( $request ) {
		// Get the request params.
		$body = json_decode( $request->get_body(), true );
		// Get the wizard steps.
		$steps = Wizard::get_wizard()->get_steps();

		// Validate the step ID requested.
		if ( ! isset( $body['id'] ) || ! array_key_exists( intval( $body['id'] ), $steps ) ) {
			return self::send_response( 'Invalid step ID', 0 );
		}

		// Check and validate wizard type.
		if (
			empty( $body['wizard_type'] ) ||
			strtolower( Wizard::get_wizard_name() ) !== strtolower( $body['wizard_type'] )
		) {
			return self::send_response( 'Invalid Wizard Type', 0 );
		}

		// Get all items for the step.
		$items = $steps[ $body['id'] ]->get_items_list();

		$type = $steps[ $body['id'] ]->type;

		// Check if the items are called via API and get the correct column
		if ( "plugins" === $type || "themes" === $type ) {
			$items = array_column( $items, "id" );
		}

		// Get the correct column for the items, if necesarry.
		if ( 'topic' === $type ) {
			$items = array_column( $items, 'topic' );
		}

		// Check if selected is set, but no items available.
		if ( empty( $items ) && ! empty( $body['selected'] ) ) {
			return self::send_response( 'There are no items available in this step', 0 );
		}

		// Check if step has items, but none are selected.
		if (
			! empty( $items ) &&
			empty( $body['selected'] ) &&
			'topic' === $type
		) {
			return self::send_response( 'No selected value', 0 );
		}

		// Check if the step has items and check it's selected value if so.
		if( ! empty( $items ) ) {
			// Itearate all selected values and check them for incosistencies.
			foreach( $body['selected'] as $selected ) {
				if ( ! in_array( $selected, $items ) ) {
					return self::send_response( 'Invalid selected value', 0 );
				}
			}
		}

		// Get currently saved progress.
		$saved_progress = get_option( 'siteground_wizard_progress', array() );

		// Save the new progress.
		$saved_progress[ $body['id'] ]['selected'] = $body['selected'];

		// Remove the next items in array after our new item and reset their progress.
		$count = array_search( intval( $body['id'] ), array_keys( $saved_progress ) );
		$saved_progress = array_slice( $saved_progress, 0, $count+1, true );

		// Update the option with the new progress in the database.
		update_option( 'siteground_wizard_progress', $saved_progress );

		// Add property to flag for AI flow change.
		if ( 'topic' === $steps[ $body['id'] ]->type ) {
			// Iterate all items to find the one matching to our selected value.
			foreach( $steps[ $body['id'] ]->items as $category ) {
				// Check if the selected topic is the one we are iterating now, if so, check if it supports AI.
				if ( in_array( $category['topic'], $body[ 'selected' ], true ) && 1 === intval( $category['ai_support'] ) ) {
					$request['ai_flow'] = true;
					break;
				}
			}
		}

		// Change the flow to AI if needed.
		if ( 'flow' === $steps[ $body['id'] ]->type && in_array( 'ai_flow', $body['selected'] ) ) {
			update_option( 'siteground_wizard_ai_flow', true );
		}

		$get_step = $this->get_step( $request, false );

		$next_step_id = ! empty( $get_step->data['data']['next_step_id'] ) ? $get_step->data['data']['next_step_id'] : 0;

		// Ignore cases where the next_step_id is not an integer.
		if ( is_int( $next_step_id ) ) {
			update_option( 'siteground_wizard_next_step', $next_step_id );
		}

		// Return the next page.
		return $get_step;
	}


	/**
	 * Deltes the current progress and restarts the wizard.
	 *
	 * @since 3.0.0
	 *
	 * @param  \WP_REST_Request $request                     Full details about the request.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response Response object.
	 */
	public function restart( $request ) {
		// Deletes the current progress.
		delete_option( 'siteground_wizard_progress' );
		delete_option( 'siteground_wizard_next_step' );

		// Returns the first step.
		return $this->get_step( $request );
	}

	/**
	 * Retrieves the recommended plugins and the data for the popup that should show up.
	 *
	 * @since 3.0.0
	 *
	 * @return array|bool    Array of items for the popup, false if not applicable.
	 */
	public function check_for_recommended_plugins() {
		// Config for all duplicate plugins.
		$recommended_plugins_info = array(
			18 => array(
				'recommended_plugin_id' => 7,
				'title'            => __( 'Attention required', 'siteground-wizard' ),
				'subtitle'         => __( 'The design you have chosen comes with sample data that is enabled by WooCommerce. In order to keep the sample data, including all the pages and overall look and structure of the selected design, you need to install WooCommerce on your site.', 'siteground-wizard' ),
				'plugin_logo'      => 'woo.png',
				'button_prev_text' => __( 'Close', 'siteground-wizard' ),
				'button_next_text' => __( 'Remove WooCommerce', 'siteground-wizard' ),
			),
		);

		// Get current progress and the index for the theme step.
		$current_progress = get_option( 'siteground_wizard_progress', array() );
		$theme_step_index = Wizard::get_step_index_by_type( 'themes' );

		// Bail if we haven't saved a theme in the progress.
		if ( empty( $current_progress[ $theme_step_index ] ) ) {
			return array();
		}

		$builders = array();

		// Retrieve the theme step and get all themes.
		$theme_id   = $current_progress[ $theme_step_index ]['selected'][0];
		$theme_step = Wizard::get_wizard()->get_steps()[ $theme_step_index ];
		$themes     = $theme_step->get_items_list();

		// Iterate all themes and get the builders for the selected one.
		foreach( $themes as $theme ) {
			if ( $theme['id'] === $theme_id && ! empty( $theme["builders"] ) ) {
				$builders = array_column( json_decode( $theme["builders"], true ), 'id' );
			}
		}

		// Iterate the config and remove unneeded plugins.
		foreach( $recommended_plugins_info as $id => $plugin ) {
			if ( ! in_array( $id, $builders ) ) {
				unset( $recommended_plugins_info[ $id ] );
			}
		}

		return $recommended_plugins_info;
	}
}
