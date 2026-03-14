<?php
/**
 * Guest email capture logic. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Guest_Capture
 */
class FRC_Pro_Guest_Capture {

	/**
	 * Constructor – register AJAX and frontend hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_frc_capture_guest_email', array( $this, 'ajax_capture_email' ) );
		add_action( 'wp_ajax_frc_capture_guest_email', array( $this, 'ajax_capture_email' ) );
		add_action( 'frc_public_enqueue_scripts', array( $this, 'enqueue_popup_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_popup' ) );
	}

	/**
	 * AJAX handler – save guest email from popup.
	 */
	public function ajax_capture_email() {
		check_ajax_referer( 'frc_guest_capture_nonce', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'flexi-revive-cart-pro' ) ) );
			return;
		}

		// Store in WooCommerce session.
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'frc_guest_email', $email );
		}

		// Trigger cart save with the guest email.
		if ( class_exists( 'FRC_Cart_Tracker' ) ) {
			$tracker = new FRC_Cart_Tracker();
			$tracker->save_cart_to_db();
		}

		wp_send_json_success( array( 'message' => __( 'Email saved. Thank you!', 'flexi-revive-cart-pro' ) ) );
	}

	/**
	 * Enqueue popup scripts and styles on the frontend.
	 */
	public function enqueue_popup_scripts() {
		if ( ! get_option( 'frc_enable_guest_capture', '0' ) && ! get_option( 'frc_enable_exit_intent', '0' ) ) {
			return;
		}

		// Don't show to logged-in users who already have email.
		if ( is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style(
			'frc-pro-popups',
			FRC_PRO_PLUGIN_URL . 'public/css/frc-pro-popups.css',
			array(),
			FRC_PRO_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'frc-pro-popups',
			FRC_PRO_PLUGIN_URL . 'public/js/frc-pro-popups.js',
			array( 'jquery' ),
			FRC_PRO_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'frc-pro-popups', 'frcProPopups', array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'frc_guest_capture_nonce' ),
			'enableGuestCapture' => (bool) get_option( 'frc_enable_guest_capture', '0' ),
			'enableExitIntent'  => (bool) get_option( 'frc_enable_exit_intent', '0' ),
			'guestCaptureDelay' => (int) get_option( 'frc_guest_capture_delay', 5 ),
		) );
	}

	/**
	 * Render popup HTML in the footer.
	 */
	public function render_popup() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( get_option( 'frc_enable_guest_capture', '0' ) ) {
			if ( file_exists( FRC_PRO_PLUGIN_DIR . 'templates/popups/guest-capture.php' ) ) {
				include FRC_PRO_PLUGIN_DIR . 'templates/popups/guest-capture.php';
			}
		}

		if ( get_option( 'frc_enable_exit_intent', '0' ) ) {
			if ( file_exists( FRC_PRO_PLUGIN_DIR . 'templates/popups/exit-intent.php' ) ) {
				include FRC_PRO_PLUGIN_DIR . 'templates/popups/exit-intent.php';
			}
		}
	}
}
