<?php
namespace SiteGround_Central\Rest\Controllers\V1;

use SiteGround_Central\Wizard\Wizard;
use SiteGround_Central\Traits\Statistic_Trait;
use SiteGround_Central\Importer\Importer;
use SiteGround_Central\Installer\Installer;

/**
 * Class responsible for Installing plugins and themes.
 */
class Rest_Install extends Rest {
	use Statistic_Trait;

	/**
	 * Register the routes for install service.
	 *
	 * @since  3.0.0
	 */
	public function register_routes() {
		// Wizard Install endpoint.
		register_rest_route(
			'siteground-central/v1',
			'/install/',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'install' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		// Theme page install recommended theme popups endpoint.
		register_rest_route(
			'siteground-central/v1',
			'/theme-pre-install/',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'theme_pre_install' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
		// Theme page install recommended theme endpoint.
		register_rest_route(
			'siteground-central/v1',
			'/theme-install/',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'theme_install' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			)
		);
	}

	/**
	 * The install method.
	 *
	 * @since 3.0.0
	 *
	 * @param  \WP_REST_Request  $request The rest request.
	 * @return \WP_REST_Response          The response.
	 */
	public function install( $request ) {
		// Get the request params.
		$body = json_decode( $request->get_body(), true );

		// Validate the wizard type requested.
		if ( empty( $body['step_index'] ) ) {
			return self::send_response( 'Invalid step index', 0 );
		}

		// Prepare the default response.
		$response = array(
			'next_step_type' => 'success',
			'next_step_id'   => Wizard::get_step_index_by_type( 'success' ),
		);

		// Get the install queue.
		$install_queue = $this->prepare_install_queue();

		// Skip install attempts if nothing to install.
		if ( empty( $install_queue ) ) {
			// Complete the installation.
			Installer::complete();

			return self::send_response( $response );
		}

		// Add the woo-commerce default pages filter so we do not break the creations of system pages.
		add_filter( 'woocommerce_create_pages', function() { return array(); } );

		// Retrieve all installation data from /installation/ endpoint.
		$installation_data = json_decode( wp_remote_retrieve_body( $this->send_installation_statistics( $install_queue ) ) );

		// Get sample data for the theme.
		$sample_data = $this->fetch_sample_data( $installation_data );

		if ( isset( $body['woo'] ) && 0 === $body['woo'] ) {
			foreach ( $install_queue['plugins'] as $key => $plugin ) {
				if ( 'woocommerce' === $plugin['slug'] ) {
					unset( $install_queue['plugins'][ $key ] );
					break;
				}
			}
		}

		// Loop the install queue.
		foreach ( $install_queue as $type => $items ) {
			// Continue if type is empty.
			if ( empty( $items ) ) {
				continue;
			}

			// Loop the items from specific type.
			foreach ( $items as $item ) {
				if ( Installer::execute_installation_command( $type, $item ) ) {
					if (
						'themes' === $type &&
						! empty( $sample_data )
					) {
						// Reset the site.
						exec( 'wp site empty --yes' );
						// Import the sample data.
						$importer = new Importer();
						$importer->pre_import( $sample_data );
					}
					continue;
				} else {
					// Prepare the default response.
					$steps_index = Wizard::get_all_step_index_by_type( 'failure' );

					$response = array(
						'next_step_type' => 'failure',
						'next_step_id'   => end( $steps_index ),
					);

					// Wizard failed.
					$wizard_failed = 1;
				}
			}
		}

		// Complete the wizard only if not failed.
		if ( empty( $wizard_failed ) ) {
			Installer::complete();
		}

		// Send the response.
		return self::send_response( $response );
	}

	/**
	 * Send the installation request to the SG API and retrieve its response.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $install_queue Queue with items to be installed.
	 *
	 * @return array|\WP_Error      Response object.
	 */
	public function send_installation_statistics( $install_queue ) {
		$statistics = array(
			'theme'       => array(),
			'plugins'     => array(),
			'ip'          => self::get_user_ip(),
			'theme_tags'  => '',
			'is_reseller' => 0,
		);

		// Iterate all items and get their ids.
		foreach ( $install_queue as $type => $items ) {
			if ( 'themes' === $type ) {
				$statistics['theme'] = array_column( $items, 'id' )[0];
				// Default, if no tags.
				$statistics['theme_tags'] = array( 'all' );

				// If there are tags, iterate all of them and add them to the list of tags.
				if ( ! empty( $items[0]['tags'] ) ) {
					$statistics['theme_tags'] = array();
					foreach ( $items[0]['tags'] as $tag ) {
						$statistics['theme_tags'][] = $tag['tag'];
					}
				}
				continue;
			}

			$statistics[ $type ] = array_column( $items, 'id' );
		}

		return self::call_sg_api( \SiteGround_Central\SG_WPAPI_URL . '/installation', json_encode( $statistics ) );
	}

	/**
	 * Prepare the install queue based on steps selections.
	 *
	 * @since 3.0.0
	 *
	 * @return array Containing data ready for install.
	 */
	public function prepare_install_queue() {
		$wizard = Wizard::get_wizard();

		$selections = get_option( 'siteground_wizard_progress', array() );

		$install_map = array(
			'plugins' => array(),
			'themes'  => array(),
		);

		// Loop the steps and gather the selections.
		foreach ( $selections as $step => $selection ) {
			// This variable is added for readability,
			$type = $wizard->steps[ $step ]->type;

			if ( ! in_array( $type, array( 'themes', 'plugins' ) ) ) {
				continue;
			}

			// Prepare the type array.
			$install_map[ $type ] = array_merge(
				$install_map[ $type ],
				$selection['selected']
			);
		}

		return $this->finalize_install_map( $install_map );
	}

	/**
	 * Populate theme and plugins data while removing plugin duplicates.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $install_map Array containing all items selected for installation.
	 * @return array $install_map The populated and modified array of selections.
	 */
	public function finalize_install_map( $install_map ) {
		// Check if theme is selected.
		if ( ! empty( $install_map['themes'] ) ) {
			// Prepare the data necessary for the installer.
			$install_map['themes'] = $this->get_selected_items_data( $install_map['themes'], \SiteGround_Central\SG_WPAPI_URL . '/themes' );

			// Check for required plugins.
			$required_plugins = $this->check_for_required_plugins( $install_map['themes'][0] );

			// If there are required plugins, add them to the plugins map.
			if ( ! empty( $required_plugins ) ) {
				$install_map['plugins'] = array_unique( array_merge( $install_map['plugins'], $required_plugins ) );
			}
		}

		// Add the plugin data to the map if we have selected plugins.
		if ( ! empty( $install_map['plugins'] ) ) {
			$install_map['plugins'] = $this->get_selected_items_data( $install_map['plugins'], \SiteGround_Central\SG_WPAPI_URL . '/plugins' );

			// Get the slugs of the plugins.
			$slugs = array_column( array(), 'slug' );

			// Check for duplicates if required plugins are added.
			$duplicates = array_keys( array_diff_key( $slugs, array_unique( $slugs ) ) );

			// Unset duplicates.
			if ( ! empty( $duplicates ) ) {
				foreach ( $duplicates as $duplicate_key ) {
					unset( $install_map['plugins'][ $duplicate_key ] );
				}
			}
		}

		return $install_map;
	}

	/**
	 * Check if the theme requires specific plugin and prepare them for the queue.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $theme            The theme data.
	 * @return array $required_plugins Plugins required by the theme.
	 */
	public function check_for_required_plugins( $theme ) {
		$required_plugins = array();

		// Add the plugins required by the theme.
		if ( ! empty( $theme['required_plugins'] ) ) {
			$required_plugins = array_merge(
				$required_plugins,
				array_map( 'intval', preg_split( '/[\s,]+/', $theme['required_plugins'] ) )
			);
		}

		// Add the builders required by the theme.
		if ( ! empty( $theme['builders'] ) ) {
			foreach ( json_decode( $theme['builders'], JSON_OBJECT_AS_ARRAY ) as $builder ) {
				$required_plugins[] = $builder['id'];
			}
		}

		return $required_plugins;
	}

	/**
	 * Get items data based on selection.
	 *
	 * @since 3.0.0
	 *
	 * @param  array  $items The selected items from every step.
	 * @param  string $url   The wpwizardapi endpoint.
	 *
	 * @return array  $items The populated array with data about the selections.
	 */
	public function get_selected_items_data( $selected_items, $url ) {
		// Get the items data from the remote server.
		$remote_items = $this->get_remote_items( $url );

		// Loop the items.
		foreach ( $remote_items as $item ) {
			// Search for a match between the selected items and the remote items.
			$match = array_search( $item['id'], $selected_items, true );

			// Add the data to the specific selection if we have a match.
			if ( false !== $match ) {
				$selected_items[ $match ] = $item;
			}
		}

		return $selected_items;
	}

	/**
	 * TO-DO This should be moved to a trait since its used in multiple classes.
	 *
	 * @param  string $url The API url.
	 *
	 * @return array      The data.
	 */
	public function get_remote_items( $url ) {
		$request = wp_remote_get( $url, array( 'sslverify' => false ) );

		// Bail early.
		if ( is_wp_error( $request ) ) {
			return false;
		}

		// Retrieve the body from the request.
		$body = wp_remote_retrieve_body( $request );
		// Decode the body to an assoc. array.
		$data = json_decode( $body, true );

		return $data;
	}

	/**
	 * Pre-install theme checks for popups.
	 *
	 * @param      <type>  $request  The request
	 */
	public function theme_pre_install( $request ) {
		// Get the request params.
		$body = json_decode( $request->get_body(), true );

		// Bail if no theme ID is provided.
		if ( empty( $body['theme_id'] ) ) {
			return self::send_response( 'Theme ID required', 0 );
		}

		// Prepare install queue.
		$theme_map = array(
			'theme' => $body['theme_id'],
			'ip'    => '127.0.0.1'
		);

		// Retrieve theme data from /sg-installation/ endpoint.
		$theme_data = json_decode( wp_remote_retrieve_body( self::call_sg_api( \SiteGround_Central\SG_WPAPI_URL . '/sg-installation', json_encode( $theme_map ) ) ) );

		// Prepare the data array.
		$data = array(
			'sample_data_popup' => array(
				'title'              => __( 'Action Required', 'siteground-wizard' ),
				'description'        => __( 'Do you want to import the sample data when installing the theme?', 'siteground-wizard' ),
				'notice'             => __( 'Note, that this will delete your existing content!', 'siteground-wizard' ),
				'yes_button_text'    => __( 'Yes', 'siteground-wizard' ),
				'no_button_text'     => __( 'No', 'siteground-wizard' ),
				'cancel_button_text' => __( 'Cancel', 'siteground-wizard' ),
			),
			'woo_popup'         => (object) array(),
			'next_step'         => 'theme-install',
		);

		// Bail if we have Woo already installed.
		if ( function_exists( '\is_plugin_active' ) && \is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return self::send_response( $data );
		}

		// Check if there is WooCommerce Builder with the theme.
		foreach( $theme_data as $theme ) {
			// Bail if there are no builders.
			if ( empty( $theme->builders ) ) {
				continue;
			}

			// Get the theme builders.
			$builders = array_column( json_decode( $theme->builders, true ), 'id' );

			if (
				$theme->id === $body['theme_id'] &&
				18 === $builders[0]
			) {
				// Verify if theme requires WooCommerce and it is not already installed.
				$data['woo_popup'] = array(
					'title'              => __( 'Action Required', 'siteground-wizard' ),
					'description'        => __( 'The design you have chosen comes with sample data that is enabled by WooCommerce plugin. Do you want to install WooCommerce on your site?', 'siteground-wizard' ),
					'yes_button_text'    => __( 'Yes', 'siteground-wizard' ),
					'no_button_text'     => __( 'No', 'siteground-wizard' ),
					'cancel_button_text' => __( 'Cancel', 'siteground-wizard' ),
				);
			}
		}

		// Return the installation requirenments.
		return self::send_response( $data );
	}

	/**
	 * Install theme and sample data if selected.
	 *
	 * @param      <type>  $request  The request
	 */
	public function theme_install( $request ) {
		// Get the request params.
		$body = json_decode( $request->get_body(), true );

		// Prepare install queue.
		$theme_map = array(
			'theme' => $body['theme_id'],
			'ip'    => '127.0.0.1'
		);

		// Retrieve theme data from the API endpoint.
		$installation_data = json_decode( wp_remote_retrieve_body( self::call_sg_api( \SiteGround_Central\SG_WPAPI_URL . '/sg-installation', json_encode( $theme_map ) ) ) );

		// Get sample data for the theme.
		$sample_data = $this->fetch_sample_data( $installation_data );

		// Prepare the install queue.
		$install_queue = $this->finalize_install_map( array(
			'plugins' => array(),
			'themes'  => array( $body['theme_id'] ),
		) );

		if ( 0 === $body['woo'] ) {
			foreach( $install_queue['plugins'] as $key => $plugin ) {
				if ( 'woocommerce' === $plugin['slug'] ) {
					unset( $install_queue['plugins'][ $key ] );
					break;
				}
			}
		}

		// Loop the install queue.
		foreach ( $install_queue as $type => $items ) {
			// Continue if type is empty.
			if ( empty( $items ) ) {
				continue;
			}

			// Loop the items from specific type.
			foreach ( $items as $item ) {
				if ( Installer::execute_installation_command( $type, $item ) ) {
					if (
						'themes' === $type &&
						1 === $body['sample_data']
					) {
						// Reset the site.
						$this->reset();
						// Import the sample data.
						$importer = new Importer();
						$importer->pre_import( $sample_data );
					}
					continue;
				}
			}
		}

		// Return the response.
		return self::send_response( 'success' );
	}

	/**
	 * Fetches the sample data.
	 *
	 * @param      array  $installation_data  The installation data
	 *
	 * @return     array   The sample data.
	 */
	public function fetch_sample_data( $installation_data ) {
		// Prepare sample data array.
		$sample_data = array();

		// Iterate all data and gather only the sample-data.
		foreach ( $installation_data as $item ) {
			if ( 'sample-data' !== $item->type ) {
				continue;
			}

			$sample_data[] = $item;
		}

		// Return the sample data.
		return $sample_data;
	}

	/**
	 * Empty the site.
	 *
	 * @since  1.0.0
	 */
	public function reset() {
		// Disable the Astra Starter Templates, since issues were found when changing from Astra to Neve.
		exec( 'wp plugin deactivate astra-sites' );
		// Disable the Kubio plugin due to issues with sample data ids.
		exec( 'wp plugin deactivate kubio' );
		// Reset the site.
		exec( 'wp site empty --yes' );
	}
}
