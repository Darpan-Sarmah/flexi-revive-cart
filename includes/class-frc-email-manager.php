<?php
/**
 * Email sending and scheduling manager.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Email_Manager
 */
class FRC_Email_Manager {

	/**
	 * Send a reminder email for a given cart at a given stage.
	 *
	 * In the Free version only friendly reminder emails are sent (up to 3).
	 * Pro can extend reminder types and limits via hooks.
	 *
	 * @param object $cart  Database cart row.
	 * @param int    $stage Email stage (1, 2, 3, ...).
	 * @return bool True on success.
	 */
	public function send_reminder( $cart, $stage ) {
		if ( ! $cart || empty( $cart->user_email ) ) {
			return false;
		}

		if ( $cart->opted_out ) {
			return false;
		}

		// Max reminder emails per cart.
		$max_emails = get_option( 'frc_num_reminders', 3 );
		/** This filter is documented in class-frc-admin-settings.php */
		$max_emails = min( (int) $max_emails, apply_filters( 'frc_max_reminders', 3 ) );

		if ( $cart->emails_sent >= $max_emails ) {
			return false;
		}

		// Determine the template to use based on single reminder type setting.
		$reminder_type = get_option( 'frc_reminder_type', 'friendly' );

		/**
		 * Filters the reminder type for a specific cart and stage.
		 *
		 * @param string $reminder_type The reminder type (friendly, urgency, incentive).
		 * @param int    $stage         The reminder stage number.
		 * @param object $cart          The cart database row.
		 */
		$reminder_type = apply_filters( 'frc_reminder_type_for_cart', $reminder_type, $stage, $cart );

		// Map reminder type to template ID.
		$template_id = FRC_Email_Templates::get_template_id_for_type( $reminder_type );

		// Allow Pro to inject discount data via filter.
		$discount_code   = '';
		$discount_amount = '';

		/**
		 * Filters discount data before an email is sent.
		 *
		 * Pro can use this to generate discount codes for incentive reminders.
		 *
		 * @param array  $discount      Array with 'code' and 'amount' keys.
		 * @param object $cart          The cart database row.
		 * @param string $reminder_type The reminder type.
		 * @param int    $stage         The reminder stage number.
		 */
		$discount = apply_filters( 'frc_email_discount', array( 'code' => '', 'amount' => '' ), $cart, $reminder_type, $stage );
		if ( ! empty( $discount['code'] ) ) {
			$discount_code   = $discount['code'];
			$discount_amount = $discount['amount'];
		}

		/**
		 * Fires before a reminder email is sent.
		 *
		 * @param object $cart  The cart database row.
		 * @param int    $stage The reminder stage number.
		 */
		do_action( 'frc_before_reminder_sent', $cart, $stage );

		// Build template vars (log_id will be 0 until we insert the log).
		$vars = FRC_Email_Templates::build_vars( $cart, 0, $discount_code, $discount_amount );

		// Determine cart language for template selection.
		$lang = isset( $cart->language ) && $cart->language ? $cart->language : get_option( 'frc_backend_language', get_option( 'frc_default_language', 'en' ) );

		// Render body.
		$body = FRC_Email_Templates::render( $template_id, $vars, $lang );
		if ( empty( $body ) ) {
			return false;
		}

		// Validate placeholders based on template type.
		$body = FRC_Email_Templates::validate_placeholders( $body, $template_id );

		/**
		 * Filters the email body before sending.
		 *
		 * @param string $body        The rendered email body HTML.
		 * @param object $cart        The cart database row.
		 * @param int    $stage       The reminder stage number.
		 * @param string $template_id The template ID used.
		 */
		$body = apply_filters( 'frc_email_content', $body, $cart, $stage, $template_id );

		// Get subject from the unified per-template, per-language storage with placeholder support.
		$subject = FRC_Email_Templates::get_subject( $template_id, $lang, $vars );

		// Validate placeholders in subject too.
		$subject = FRC_Email_Templates::validate_placeholders( $subject, $template_id );

		// Fallback to legacy subjects if the new subject is empty.
		if ( empty( $subject ) ) {
			$legacy_subjects = get_option( 'frc_email_subjects', FRC_Email_Templates::get_legacy_default_subjects() );
			$subject_index   = $stage - 1;
			$subject         = isset( $legacy_subjects[ $subject_index ] ) ? $legacy_subjects[ $subject_index ] : $legacy_subjects[0];
			$subject         = FRC_Email_Templates::replace_vars( $subject, $vars );
		}

		/**
		 * Filters the email subject before sending.
		 *
		 * @param string $subject     The email subject line.
		 * @param object $cart        The cart database row.
		 * @param int    $stage       The reminder stage number.
		 * @param string $template_id The template ID used.
		 */
		$subject = apply_filters( 'frc_email_subject', $subject, $cart, $stage, $template_id );

		// Insert log entry first to get log ID for tracking.
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_email_logs',
			array(
				'cart_id'       => (int) $cart->id,
				'email_to'      => $cart->user_email,
				'email_subject' => $subject,
				'email_body'    => $body,
				'template_id'   => $template_id,
				'channel'       => 'email',
				'status'        => 'sent',
				'sent_at'       => current_time( 'mysql' ),
			)
		);
		$log_id = (int) $wpdb->insert_id;

		// Re-build vars now that we have the log ID for tracking pixel / link.
		$vars = FRC_Email_Templates::build_vars( $cart, $log_id, $discount_code, $discount_amount );
		$body = FRC_Email_Templates::render( $template_id, $vars, $lang );

		// Update body in log.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_email_logs',
			array( 'email_body' => $body ),
			array( 'id' => $log_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Send via wp_mail.
		$from_name  = get_option( 'frc_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'frc_from_email', get_option( 'admin_email' ) );

		// Strip newlines/carriage-returns to prevent email header injection.
		$from_name = str_replace( array( "\r", "\n" ), '', $from_name );

		// Fall back to the site admin email if the stored value is not a valid address.
		if ( ! is_email( $from_email ) ) {
			$from_email = get_option( 'admin_email' );
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);

		$sent = wp_mail( $cart->user_email, $subject, $body, $headers );

		if ( ! $sent ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array( 'status' => 'failed' ),
				array( 'id' => $log_id ),
				array( '%s' ),
				array( '%d' )
			);
			return false;
		}

		// Update the cart record.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array(
				'emails_sent'     => (int) $cart->emails_sent + 1,
				'last_reminder_at' => current_time( 'mysql' ),
				'discount_code'   => $discount_code,
			),
			array( 'id' => (int) $cart->id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		// Fire hook after cart reminder is sent (wrapped in try-catch for third-party safety).
		try {
			/**
			 * Fires after a cart reminder email is successfully sent.
			 *
			 * @param int    $cart_id Cart ID.
			 * @param int    $stage   Reminder stage (1, 2, or 3).
			 * @param string $lang    Language code used.
			 */
			do_action( 'frc_after_reminder_sent', (int) $cart->id, $stage, $lang );
		} catch ( \Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'FRC Hook Error (frc_after_reminder_sent): ' . $e->getMessage() );
		}

		return true;
	}
}
