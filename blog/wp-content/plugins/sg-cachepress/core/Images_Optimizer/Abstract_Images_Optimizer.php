<?php
namespace SiteGround_Optimizer\Images_Optimizer;

use SiteGround_Optimizer\Supercacher\Supercacher;
use SiteGround_Optimizer\Options\Options;

/**
 * SG Abstract_Images_Optimizer main plugin class.
 *
 * @since 5.9.0
 */
abstract class Abstract_Images_Optimizer {
	/**
	 * The batch limit.
	 *
	 * @since 5.0.0
	 *
	 * @var int The batch limit.
	 */
	const BATCH_LIMIT = 200;

	/**
	 * The PNG image size limit. Bigger images won't be optimized.
	 *
	 * @since 5.0.0
	 *
	 * @var int The PNG image size limit.
	 */
	const PNGS_SIZE_LIMIT = 1048576;

	/**
	 * The Database placeholder.
	 */
	public $wpdb;

	/**
	 * Start the optimization.
	 *
	 * @since  5.9.0
	 */
	public function initialize() {
		// Flush the cache, to avoid stuck optimizations.
		Supercacher::purge_cache();

		foreach ( $this->options_map as $reset_option ) {
			// Reset the status.
			update_option( $reset_option, 0, false );
		}

		update_option(
			$this->non_optimized,
			Options::check_for_unoptimized_images( $this->type ),
			false
		);

		// Generate a secure one-time token for background processing.
		$token = wp_hash( wp_generate_password( 32, true, true ) . time() );
		set_transient( 'sgo_image_optimization_token_' . $this->type, $token, 3600 );

		// Fork the process in background.
		$args = array(
			'timeout'   => 0.01,
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$args['body'] = array( 'token' => $token );

		$response = wp_remote_post(
			add_query_arg( 'action', $this->action, admin_url( 'admin-ajax.php' ) ),
			$args
		);
	}

	/**
	 * Get images batch.
	 *
	 * @since  5.9.0
	 *
	 * @return array Array containing all images ids that are not optimized.
	 */
	public function get_batch() {
		// Flush the cache before prepare a new batch.
		wp_cache_flush();
		// Get the images.
		$images = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => self::BATCH_LIMIT,
				'fields'         => 'ids',
				'meta_query'     => array(
					// Skip optimized images.
					array(
						'key'     => $this->batch_skipped,
						'compare' => 'NOT EXISTS',
					),
					// Also skip failed optimizations.
					array(
						'key'     => $this->process_map['failed'],
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return $images;
	}

	/**
	 * Optimize the images.
	 *
	 * @since  5.9.0
	 */
	public function start_optimization() {
		// Security check: Verify the request is authorized.
		if ( ! $this->verify_optimization_request() ) {
			wp_die( 'Unauthorized', 'Unauthorized', array( 'response' => 403 ) );
		}

		$started = time();
		// Get image ids.
		$ids = $this->get_batch();
		// There are no more images to process, so complete the optimization.
		if ( empty( $ids ) ) {
			// Clear the scheduled CRON and update the optimization status.
			$this->complete();
			return;
		}

		/**
		 * Allow users to change the default timeout.
		 * On SiteGround servers the default timeout is 120 seconds
		 *
		 * @since 5.0.0
		 *
		 * @param int $timeout The timeout in seconds.
		 */
		$timeout = apply_filters( $this->process_map['filter'], 120 );

		// Try to lock the process if there is a timeout.
		if ( false === $this->maybe_lock( $timeout ) ) {
			return;
		}

		// Schedule next event right after the current one is completed.
		if ( 0 !== $timeout ) {
			wp_schedule_single_event( time() + $timeout, $this->cron_type );
		}

		// Loop through all images and optimize them.
		foreach ( $ids as $id ) {
			// Keep track of the number of times we've attempted to optimize the image.
			$count = (int) get_post_meta( $id, $this->process_map['attempts'], true );

			if ( $count > 1 ) {
				update_post_meta( $id, $this->process_map['failed'], 1 );
				continue;
			}

			update_post_meta( $id, $this->process_map['attempts'], $count + 1 );

			// Get attachment metadata.
			$metadata = wp_get_attachment_metadata( $id );

			// Optimize the main image and the other image sizes.
			$status = $this->optimize( $id, $metadata );

			// Mark image if the optimization failed.
			if ( false === $status ) {
				update_post_meta( $id, $this->process_map['failed'], 1 );
			}

			// Break script execution before we hit the max execution time.
			if ( ( $started + $timeout - 5 ) < time() ) {
				break;
			}
		}
	}

	/**
	 * Delete the scheduled CRON and update the status of optimization.
	 *
	 * @since  5.9.0
	 */
	public function complete() {

		// Clear the scheduled CRON after the optimization is completed.
		wp_clear_scheduled_hook( $this->cron_type );

		// Update the status to finished.
		update_option( $this->options_map['completed'], 1, false );
		update_option( $this->options_map['status'], 1, false );
		update_option( $this->options_map['stopped'], 0, false );

		// Delete the lock.
		delete_option( $this->process_lock );
		delete_option( $this->non_optimized );

		// Finally purge the cache.
		Supercacher::purge_cache();
	}

	/**
	 * Lock the currently running process if the timeout is set.
	 *
	 * @since  5.9.0
	 *
	 * @param  int $timeout The max_execution_time value.
	 *
	 * @return bool         True if the timeout is not set or if the lock has been created.
	 */
	public function maybe_lock( $timeout ) {
		// No reason to lock if there's no timeout.
		if ( 0 === $timeout ) {
			return true;
		}

		// Try to lock.
		$lock_result = add_option( $this->process_lock, time(), '', 'no' );

		if ( ! $lock_result ) {

			$lock_result = get_option( $this->process_lock );

			// Bail if we were unable to create a lock, or if the existing lock is still valid.
			if ( ! $lock_result || ( $lock_result > ( time() - $timeout ) ) ) {
				$timestamp = wp_next_scheduled( $this->cron_type );

				if ( false === (bool) $timestamp ) {
					$response = wp_schedule_single_event( time() + $timeout, $this->cron_type );

				}
				return false;
			}
		}

		update_option( $this->process_lock, time(), false );

		return true;
	}

	/**
	 * Optimize newly uploaded images.
	 *
	 * @since  5.9.0
	 *
	 * @param  array $data          Array of updated attachment meta data.
	 * @param  int   $attachment_id Attachment post ID.
	 */
	public function optimize_new_image( $data, $attachment_id ) {
		// Optimize the image.
		$this->optimize( $attachment_id, $data );

		// Return the attachment data.
		return $data;
	}

	/**
	 * Update the total unoptimized images count.
	 *
	 * @since  5.4.0
	 *
	 * @param  array $data          Array of updated attachment meta data.
	 */
	public function maybe_update_total_unoptimized_images( $data ) {
		if ( Options::is_enabled( $this->options_map['status'] ) ) {
			return $data;
		}

		update_option(
			$this->non_optimized,
			get_option( $this->non_optimized, 0 ) + 1
		);

		// Return the attachment data.
		return $data;
	}

	/**
	 * Deletes images meta_key flag to allow re-optimization.
	 *
	 * @since  5.9.0
	 */
	public function reset_image_optimization_status() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$query = $this->wpdb->prepare(
			'
			    DELETE FROM ' . esc_sql( $this->wpdb->postmeta ) . '
			    WHERE `meta_key` = %s
			    OR `meta_key` = %s
			    OR `meta_key` = %s
			    OR `meta_key` = %s
			    ',
			esc_sql( $this->batch_skipped ),
			esc_sql( $this->process_map['attempts'] ),
			esc_sql( $this->process_map['failed'] ),
			'siteground_optimizer_original_filesize'
		);

		$result = $this->wpdb->query( $query ); //phpcs:ignore
	}

	/*
	* Verify that the optimization request is authorized.
	 *
	 * @return bool True if authorized, false otherwise.
	 */
	private function verify_optimization_request() {
		// Allow CRON jobs to run without authentication.
		if ( wp_doing_cron() ) {
			return true;
		}

		// Allow WP-CLI commands to run without authentication.
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			return true;
		}

		// For AJAX requests, verify the token.
		if ( wp_doing_ajax() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$provided_token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
			$stored_token   = get_transient( 'sgo_image_optimization_token_' . $this->type );

			// Delete the transient after first use (one-time token).
			if ( $stored_token ) {
				delete_transient( 'sgo_image_optimization_token_' . $this->type );
			}

			// Verify the token matches and is not empty.
			if ( ! empty( $provided_token ) && hash_equals( $stored_token, $provided_token ) ) {
				return true;
			}

			// Fallback: Check if user has proper capabilities (for authenticated requests).
			if ( current_user_can( 'manage_options' ) || current_user_can( 'upload_files' ) ) {
				return true;
			}

			return false;
		}

		// For any other context, require proper capabilities.
		return current_user_can( 'manage_options' );
	}
}
