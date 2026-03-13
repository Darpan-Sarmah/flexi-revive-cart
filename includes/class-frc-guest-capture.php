<?php
/**
 * Guest email capture logic. (Pro feature)
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Guest_Capture
 */
class FRC_Guest_Capture {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! FRC_PRO_ACTIVE ) {
			return;
		}
		add_action( 'wp_ajax_nopriv_frc_capture_guest_email', array( $this, 'ajax_capture_email' ) );
		add_action( 'wp_ajax_frc_capture_guest_email', array( $this, 'ajax_capture_email' ) );
		add_action( 'frc_optout', array( $this, 'handle_optout' ) );
		add_action( 'template_redirect', array( $this, 'handle_optout_request' ) );
	}

	/**
	 * AJAX handler – save guest email from popup.
	 */
	public function ajax_capture_email() {
		check_ajax_referer( 'frc_guest_capture_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'flexi-revive-cart' ) ) );
			return;
		}

		// Store in WooCommerce session.
		if ( WC()->session ) {
			WC()->session->set( 'frc_guest_email', $email );
		}

		// Trigger cart save with email.
		if ( class_exists( 'FRC_Cart_Tracker' ) ) {
			$tracker = new FRC_Cart_Tracker();
			$tracker->save_cart_to_db();
		}

		wp_send_json_success( array( 'message' => __( 'Email saved. Thank you!', 'flexi-revive-cart' ) ) );
	}

	/**
	 * Handle opt-out link click from query string.
	 */
	public function handle_optout_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['frc_optout'] ) ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['frc_optout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->handle_optout( $token );
	}

	/**
	 * Mark a cart's email as opted out.
	 *
	 * @param string $token Recovery token.
	 */
	public function handle_optout( $token ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array( 'opted_out' => 1 ),
			array( 'recovery_token' => sanitize_text_field( $token ) ),
			array( '%d' ),
			array( '%s' )
		);
		wc_add_notice( __( 'You have been successfully unsubscribed from cart reminder emails.', 'flexi-revive-cart' ), 'success' );
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
