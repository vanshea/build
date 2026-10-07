<?php
/**
 * SiteGround Email Service
 */

namespace SiteGround_Emails;

/**
 * SiteGround_Email_Service class.
 */
class Email_Service {

	/**
	 * Cron Name.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_cron_name;

	/**
	 * Cron Interval.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_cron_interval;

	/**
	 * Cron Next Run.
	 *
	 * @var    int timestamp
	 * @access protected
	 */
	protected $sg_cron_next_run;

	/**
	 * Mail Headers.
	 *
	 * @since  1.0.0
	 *
	 * @var    string[]
	 * @access protected
	 */
	protected $sg_mail_headers;

	/**
	 * Recipients list.
	 *
	 * @since  1.0.0
	 *
	 * @var    string[]
	 * @access protected
	 */
	protected $sg_mail_recipients;

	/**
	 * Mail Subject.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_mail_subject;

	/**
	 * Mail Body.
	 *
	 * @since  1.0.0
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_mail_body;

	/**
	 * Mail From Name.
	 *
	 * @since  1.0.1
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_mail_from_name;

	/**
	 * Mail From Email Address.
	 *
	 * @since  1.6.1
	 *
	 * @var    string
	 * @access protected
	 */
	protected $sg_mail_from_address = false;

	/**
	 * Initiate the email service.
	 *
	 * @since 1.0.0
	 *
	 * @param string $sg_cron_name     Name of the Cron Event.
	 * @param string $sg_cron_interval The Cron Event interval.
	 * @param int    $sg_cron_next_run Timestamp for cron schedule next run.
	 * @param array  $mail_args        Message Arguments.
	 */
	public function __construct( $sg_cron_name, $sg_cron_interval, $sg_cron_next_run, $mail_args ) {
		$this->sg_cron_name     = $sg_cron_name;
		$this->sg_cron_interval = $sg_cron_interval;
		$this->sg_cron_next_run = $sg_cron_next_run;

		$this->sg_mail_headers    = array_key_exists( 'headers', $mail_args ) ? $mail_args['headers'] : array( 'Content-Type: text/html; charset=UTF-8' );
		$this->sg_mail_recipients = $mail_args['recipients_option'];
		$this->sg_mail_subject    = $mail_args['subject'];
		$this->sg_mail_body       = $mail_args['body_method'];
		$this->sg_mail_from_name  = array_key_exists( 'from_name', $mail_args ) ? $mail_args['from_name'] : false;
	}

	/**
	 * Handle email.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True on successfull message sent, False on failure.
	 */
	public function sg_handle_email() {
		$receipients = get_option( $this->sg_mail_recipients, array() );

		// Make sure the mail recipients are passed as an array.
		$receipients = is_array( $receipients ) ? $receipients : array( $receipients );

		// Remove any invalid email addresses.
		foreach ( $receipients as $key => $recipient ) {
			if ( false === filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
				unset( $receipients[ $key ] );
			};
		}

		// Generate the message body from the callable method.
		$body = call_user_func( $this->sg_mail_body );

		// Get the specific subject for the SGO email.
		if ( 'sgo_campaign_cron' === $this->sg_cron_name ) {
			$this->sg_mail_subject = call_user_func( $this->sg_mail_subject );
		}

		// Bail if we fail to build the body of the message.
		if ( false === $body ) {
			// Unschedule the event, so we don't make additional actions if the body is empty.
			$this->unschedule_event();

			return false;
		}

		// Apply the from name if it is set.
		if ( false !== $this->sg_mail_from_name ) {
			add_filter( 'wp_mail_from_name', array( $this, 'set_mail_from_name' ) );
		}

		// Set a same-domain email address as the From address.
		$this->set_default_from_mail_address();

		// Sent the email.
		$result = wp_mail(
			$receipients,
			$this->sg_mail_subject,
			$body,
			$this->sg_mail_headers
		);

		// Remove the default From mail address.
		$this->remove_default_from_mail_address();

		// Remove the from name if it is set.
		if ( false !== $this->sg_mail_from_name ) {
			remove_filter( 'wp_mail_from_name', array( $this, 'set_mail_from_name' ) );
		}

		return $result;
	}

	/**
	 * Set "Mail From" name.
	 *
	 * @since 1.0.1
	 *
	 * @return string The Mail From Name.
	 */
	public function set_mail_from_name( $from_name ) {
		return $this->sg_mail_from_name;
	}

	/**
	 * Schedule event.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if event successfully/already scheduled. False or WP_Error on failure.
	 */
	public function schedule_event() {
		if ( ! wp_next_scheduled( $this->sg_cron_name ) ) {
			return wp_schedule_event( $this->sg_cron_next_run, $this->sg_cron_interval, $this->sg_cron_name );
		}

		return true;
	}

	/**
	 * Unschedule event.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if event successfully/already unscheduled. False or WP_Error on failure.
	 */
	public function unschedule_event() {
		// Retrieve the next timestamp for the cron event.
		$timestamp = wp_next_scheduled( $this->sg_cron_name );

		// Return true if there is no such event scheduled.
		if ( false === $timestamp ) {
			return true;
		}

		// Unschedule the event.
		return wp_unschedule_event( $timestamp, $this->sg_cron_name );
	}

	/**
	 * Sets a default From email address.
	 *
	 * @since 1.6.1
	 */
	public function set_default_from_mail_address() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$host = preg_replace( '/^www\./', '', $host );

		if ( empty( $host ) ) {
			return;
		}

		// Set the default wp_mail() from address.
		$this->sg_mail_from_address = 'wordpress@' . $host;
		add_filter( 'wp_mail_from', array( $this, 'set_mail_from_address' ), PHP_INT_MAX );

		// Also set the PHP Mailer from address.
		add_action( 'phpmailer_init', array( $this, 'set_phpmailer_from_address' ), PHP_INT_MAX );
	}

	/**
	 * Removes the default From email address.
	 *
	 * @since 1.6.1
	 */
	public function remove_default_from_mail_address() {
		// Remove the PHP Mailer from address first.
		if ( false !== $this->sg_mail_from_address ) {
			remove_action( 'phpmailer_init', array( $this, 'set_phpmailer_from_address' ), PHP_INT_MAX );
		}

		// Remove the default wp_mail() filter.
		remove_filter( 'wp_mail_from', array( $this, 'set_mail_from_address' ), PHP_INT_MAX );

		$this->sg_mail_from_address = false;
	}

	/**
	 * Returns the default email address.
	 *
	 * @since 1.6.1
	 *
	 * @param string $from_email The original From email address.
	 *
	 * @return string The default From email address.
	 */
	public function set_mail_from_address( $from_email ) {
		return $this->sg_mail_from_address;
	}

	/**
	 * Sets PHP Mailer from address and force server mail transport instead of configured SMTP.
	 *
	 * @since 1.6.1
	 *
	 * @param object The PHPMailer object.
	 */
	public function set_phpmailer_from_address( $phpmailer ) {
		if ( ! is_object( $phpmailer ) || ! method_exists( $phpmailer, 'setFrom' ) ) {
			return;
		}

		// Force local/server mail transport instead of configured SMTP.
		if ( method_exists( $phpmailer, 'isMail' ) ) {
			$phpmailer->isMail();
		}

		$phpmailer->setFrom(
			$this->sg_mail_from_address,
			$this->sg_mail_from_name,
			true
		);

		// Force the envelope sender / Return-Path too.
		$phpmailer->Sender = $this->sg_mail_from_address;
	}
}