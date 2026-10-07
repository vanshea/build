<?php

namespace SiteGround_Central\Wizard;

defined( 'ABSPATH' ) || exit;

return new Wizard(
	array(
		array(
			'type'             => 'intro',
			'title'            => __( 'Welcome to Your WordPress Site!', 'siteground-wizard' ),
			'button_next_text' => __( 'Start Now', 'siteground-wizard' ),
			'button_prev_text' => '',
			'subtitle'         => __( 'We know that learning WordPress takes time. To give you a jump start, we have prepared a quick customization wizard that will assist you in adding functionality to your site in a few easy steps.', 'siteground-wizard' ),
			'completed'        => false,
			'non_ai_flow_skip' => false,
			'items'            => array(),
			'do_install'       => false,
		),
		array(
			'type'             => 'plugins',
			'category'         => 'functionality',
			'items_per_page'   => 6,
			'excluded'         => array(7),
			'button_next_text' => __( 'Continue', 'siteground-wizard' ),
			'button_prev_text' => __( 'Previous Step', 'siteground-wizard' ),
			'title'            => __( 'Recommended useful functionality for your site!', 'siteground-wizard' ),
			'subtitle'         => __( 'Choose plugins for your WordPress site that enable the functionality you wish to have', 'siteground-wizard' ),
			'preselected'      => array( 2,8,10,31 ),
			'completed'        => false,
			'items'            => array(),
			'non_ai_flow_skip' => false,
			'do_install'       => false,
		),
		array(
			'type'             => 'plugins',
			'category'         => 'marketing',
			'items_per_page'   => 3,
			'excluded'         => array(),
			'button_next_text' => __( 'Complete', 'siteground-wizard' ),
			'button_prev_text' => __( 'Previous Step', 'siteground-wizard' ),
			'title'            => __( 'Let\'s talk about marketing!', 'siteground-wizard' ),
			'subtitle'         => __( 'Good thing about WordPress is that you can automate part of your marketing via plugins.', 'siteground-wizard' ),
			'preselected'      => array( 2,8,10,31 ),
			'do_install'       => true,
			'completed'        => false,
			'items'            => array(),
			'non_ai_flow_skip' => false,
		),

		array(
			'type'             => 'success',
			'title'            => __( 'Congrats! Your site is ready!', 'siteground-wizard' ),
			'subtitle'         => __( 'We have successfully completed the installation of the items you selected. You may now proceed to your WordPress dashboard and start managing your site.', 'siteground-wizard' ),
			'button_next_text' => '',
			'button_prev_text' => '',
			'items'            => array(
				array(
					'title'    => __( 'View Site', 'siteground-wizard' ),
					'subtitle' => __( 'Check your website on its domain.', 'siteground-wizard' ),
					'url'      => \get_home_url(),
				),
				array(
					'title'    => __( 'Manage site', 'siteground-wizard' ),
					'subtitle' => __( 'Go to WordPress admin to manage your content and more.', 'siteground-wizard' ),
					'url'      => \get_admin_url(),
				),
			),
			'completed'        => false,
			'non_ai_flow_skip' => false,
			'do_install'       => false,
		),
		array(
			'type'             => 'failure',
			'title'            => __( 'The installation is not completed', 'siteground-wizard' ) ,
			'subtitle'         => \is_multisite() ? __( 'We could not complete the installations.', 'siteground-wizard' ) : __( 'We could not complete the installations. Try again later.' ,'siteground-wizard' ),
			'button_next_text' => \is_multisite() ? '' : __( 'Try again', 'siteground-wizard' ),
			'button_prev_text' => __( 'Close', 'siteground-wizard' ),
			'items'            => array(
				array(
					'title'    => __( 'View Site', 'siteground-wizard' ),
					'subtitle' => __( 'Check your website on its domain.', 'siteground-wizard' ),
					'url'      => \get_home_url(),
				),
				array(
					'title'    => __( 'Manage site', 'siteground-wizard' ),
					'subtitle' => __( 'Go to WordPress admin to manage your content and more.', 'siteground-wizard' ),
					'url'      => \get_admin_url(),
				),
			),
			'completed'        => false,
			'non_ai_flow_skip' => false,
			'do_install'       => false,
		),
		array(
			'type'             => 'failure',
			'title'            => __( 'Oops! Something went wrong!', 'siteground-wizard' ) ,
			'subtitle'         => __( 'The installation of the selected items could not be completed. Please restart the wizard or try again later', 'siteground-wizard' ),
			'button_next_text' => __( 'Restart Installation', 'siteground-wizard' ),
			'button_prev_text' => __( 'Go To Dashboard', 'siteground-wizard' ),
			'items'            => array(),
			'completed'        => false,
			'non_ai_flow_skip' => false,
			'do_install'       => false,
		),
	)
);
