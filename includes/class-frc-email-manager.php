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
	 * @param object $cart  Database cart row.
	 * @param int    $stage Email stage (1, 2, or 3).
	 * @return bool True on success.
	 */
	public function send_reminder( $cart, $stage ) {
		if ( ! $cart || empty( $cart->user_email ) ) {
			return false;
		}

		if ( $cart->opted_out ) {
			return false;
		}

		// Max 3 reminder emails per cart.
		$max_emails = get_option( 'frc_num_reminders', 3 );
		if ( $cart->emails_sent >= $max_emails ) {
			return false;
		}

		// In Free version, cap at 1 reminder.
		if ( ! FRC_PRO_ACTIVE && $cart->emails_sent >= 1 ) {
			return false;
		}

		$template_id = 'reminder-' . $stage;
		$subjects    = get_option( 'frc_email_subjects', array(
			__( "You left something behind!", 'flexi-revive-cart' ),
			__( "Your cart is waiting – items may sell out!", 'flexi-revive-cart' ),
			__( "Here's a special offer to complete your purchase!", 'flexi-revive-cart' ),
		) );

		$subject_index = $stage - 1;
		$subject       = isset( $subjects[ $subject_index ] ) ? $subjects[ $subject_index ] : $subjects[0];

		// Generate discount code for stage 3 (Pro).
		$discount_code = '';
		$discount_pct  = 0;
		if ( FRC_PRO_ACTIVE && 3 === $stage && get_option( 'frc_enable_auto_discounts', '0' ) ) {
			$discount_pct  = (int) get_option( 'frc_discount_percentage', 10 );
			$discount_code = $this->get_or_create_discount( $cart, $discount_pct );
		}

		// Build template vars (log_id will be 0 until we insert the log).
		$vars = FRC_Email_Templates::build_vars( $cart, 0, $discount_code, $discount_pct );

		// Render body.
		$body = FRC_Email_Templates::render( $template_id, $vars );
		if ( empty( $body ) ) {
			return false;
		}

		// Apply A/B test subject if Pro.
		if ( FRC_PRO_ACTIVE && class_exists( 'FRC_AB_Testing' ) ) {
			$ab      = new FRC_AB_Testing();
			$subject = $ab->get_subject_for_cart( $cart->id, $subject, $stage );
		}

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
		$vars = FRC_Email_Templates::build_vars( $cart, $log_id, $discount_code, $discount_pct );
		$body = FRC_Email_Templates::render( $template_id, $vars );

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

		return true;
	}

	/**
	 * Get or create a discount coupon for a cart.
	 *
	 * @param object $cart         Cart row.
	 * @param int    $discount_pct Discount percentage.
	 * @return string Coupon code.
	 */
	private function get_or_create_discount( $cart, $discount_pct ) {
		if ( ! empty( $cart->discount_code ) ) {
			return $cart->discount_code;
		}
		if ( class_exists( 'FRC_Discount_Manager' ) ) {
			$dm = new FRC_Discount_Manager();
			return $dm->create_coupon( $cart, $discount_pct );
		}
		return '';
	}
}
