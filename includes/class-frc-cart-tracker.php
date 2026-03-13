<?php
/**
 * Cart tracking logic.
 *
 * Hooks into WooCommerce to detect and record abandoned carts.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Cart_Tracker
 */
class FRC_Cart_Tracker {

	/**
	 * Constructor – register WooCommerce hooks.
	 */
	public function __construct() {
		if ( ! get_option( 'frc_enable_tracking', '1' ) ) {
			return;
		}

		// Cart activity hooks.
		add_action( 'woocommerce_add_to_cart', array( $this, 'on_cart_updated' ), 10, 0 );
		add_action( 'woocommerce_cart_updated', array( $this, 'on_cart_updated' ) );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'on_cart_updated' ), 10, 0 );

		// Conversion hooks.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_placed' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'on_thankyou' ), 10, 1 );

		// AJAX handlers (logged-in and guest).
		add_action( 'wp_ajax_frc_track_cart', array( $this, 'ajax_track_cart' ) );
		add_action( 'wp_ajax_nopriv_frc_track_cart', array( $this, 'ajax_track_cart' ) );

		// Email open / click tracking.
		add_action( 'wp_ajax_nopriv_frc_track_open', array( $this, 'ajax_track_open' ) );
		add_action( 'wp_ajax_frc_track_open', array( $this, 'ajax_track_open' ) );
		add_action( 'template_redirect', array( $this, 'handle_click_tracking' ) );
	}

	/**
	 * Called when the WooCommerce cart is updated (server-side).
	 */
	public function on_cart_updated() {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}
		$this->save_cart_to_db();
	}

	/**
	 * Called when an order is placed – mark the related cart as converted.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function on_order_placed( $order_id ) {
		// Prefer billing email from order (covers guests who didn't go through popup).
		$order = wc_get_order( $order_id );
		$email = $order ? $order->get_billing_email() : $this->get_current_user_email();

		if ( ! $email ) {
			$email = $this->get_current_user_email();
		}

		if ( ! $email ) {
			return;
		}
		$this->mark_cart_converted( $email, $order_id );
	}

	/**
	 * Called on thank-you page – final conversion confirmation.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function on_thankyou( $order_id ) {
		$this->on_order_placed( $order_id );
	}

	/**
	 * AJAX handler: receive cart heartbeat from frontend JS.
	 */
	public function ajax_track_cart() {
		check_ajax_referer( 'frc_track_cart_nonce', 'nonce' );

		$cart_total    = isset( $_POST['cart_total'] ) ? floatval( wp_unslash( $_POST['cart_total'] ) ) : 0;
		$cart_contents = isset( $_POST['cart_contents'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_contents'] ) ) : '';

		if ( $cart_total <= 0 ) {
			wp_send_json_error( array( 'message' => 'Empty cart' ) );
			return;
		}

		$this->save_cart_to_db( $cart_total, $cart_contents );
		wp_send_json_success( array( 'message' => 'Cart tracked' ) );
	}

	/**
	 * AJAX handler: record email open (1×1 tracking pixel).
	 */
	public function ajax_track_open() {
		$log_id = isset( $_GET['lid'] ) ? absint( wp_unslash( $_GET['lid'] ) ) : 0;
		if ( $log_id ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array(
					'status'    => 'opened',
					'opened_at' => current_time( 'mysql' ),
				),
				array( 'id' => $log_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		// Output a 1×1 transparent GIF.
		header( 'Content-Type: image/gif' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}

	/**
	 * Handle click tracking redirect.
	 */
	public function handle_click_tracking() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['frc_click'] ) ) {
			return;
		}

		$log_id      = absint( wp_unslash( $_GET['frc_click'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirect_to = isset( $_GET['frc_dest'] ) ? esc_url_raw( wp_unslash( $_GET['frc_dest'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $log_id ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array(
					'status'     => 'clicked',
					'clicked_at' => current_time( 'mysql' ),
				),
				array( 'id' => $log_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Save or update the current cart in the abandoned carts table.
	 *
	 * @param float  $cart_total    Cart total (optional – reads from WC session if blank).
	 * @param string $cart_contents Serialised cart contents (optional).
	 */
	public function save_cart_to_db( $cart_total = 0, $cart_contents = '' ) {
		global $wpdb;

		$user_id    = get_current_user_id();
		$user_email = $this->get_current_user_email();
		$session_key = WC()->session ? WC()->session->get_customer_id() : '';

		if ( ! $user_email && ! $session_key ) {
			return;
		}

		// Build cart data from WooCommerce session when not supplied.
		if ( ! $cart_contents && WC()->cart ) {
			$raw_cart = array();
			foreach ( WC()->cart->get_cart() as $key => $item ) {
				$raw_cart[ $key ] = array(
					'product_id'   => $item['product_id'],
					'product_name' => $item['data'] ? $item['data']->get_name() : '',
					'variation_id' => isset( $item['variation_id'] ) ? $item['variation_id'] : 0,
					'quantity'     => $item['quantity'],
					'line_total'   => $item['line_total'],
				);
			}
			$cart_contents = FRC_Helpers::serialize_cart( $raw_cart );
		}

		if ( ! $cart_total && WC()->cart ) {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		$currency = get_woocommerce_currency();

		// Check for existing record.
		$existing = null;
		if ( $user_email ) {
			$existing = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT id, recovery_token FROM {$wpdb->prefix}frc_abandoned_carts
					 WHERE user_email = %s AND status = 'abandoned' ORDER BY id DESC LIMIT 1",
					$user_email
				)
			);
		} elseif ( $session_key ) {
			$existing = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT id, recovery_token FROM {$wpdb->prefix}frc_abandoned_carts
					 WHERE session_key = %s AND status = 'abandoned' ORDER BY id DESC LIMIT 1",
					$session_key
				)
			);
		}

		$data = array(
			'user_id'         => $user_id,
			'user_email'      => $user_email,
			'session_key'     => $session_key,
			'cart_contents'   => $cart_contents,
			'cart_total'      => $cart_total,
			'currency'        => $currency,
			'ip_address'      => FRC_Helpers::get_ip_address(),
			'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'language'        => substr( get_locale(), 0, 5 ),
			'abandoned_at'    => current_time( 'mysql' ),
		);

		if ( $existing ) {
			$data['updated_at'] = current_time( 'mysql' );
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_abandoned_carts',
				$data,
				array( 'id' => (int) $existing->id ),
				array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$data['recovery_token'] = FRC_Helpers::generate_recovery_token();
			$data['status']         = 'abandoned';
			$data['created_at']     = current_time( 'mysql' );
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_abandoned_carts',
				$data
			);
		}
	}

	/**
	 * Mark a cart as converted when an order is placed.
	 *
	 * @param string $email    Customer email.
	 * @param int    $order_id WooCommerce order ID.
	 */
	private function mark_cart_converted( $email, $order_id ) {
		global $wpdb;

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array(
				'status'             => 'converted',
				'recovered_order_id' => (int) $order_id,
				'recovered_at'       => current_time( 'mysql' ),
			),
			array(
				'user_email' => $email,
				'status'     => 'abandoned',
			),
			array( '%s', '%d', '%s' ),
			array( '%s', '%s' )
		);

		// For guests whose email wasn't captured before checkout, also try session key.
		if ( ! $updated && WC()->session ) {
			$session_key = WC()->session->get_customer_id();
			if ( $session_key ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prefix . 'frc_abandoned_carts',
					array(
						'status'             => 'converted',
						'user_email'         => $email,
						'recovered_order_id' => (int) $order_id,
						'recovered_at'       => current_time( 'mysql' ),
					),
					array(
						'session_key' => $session_key,
						'status'      => 'abandoned',
					),
					array( '%s', '%s', '%d', '%s' ),
					array( '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Get the current logged-in user's email, or empty string for guests.
	 *
	 * @return string
	 */
	private function get_current_user_email() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->user_email;
		}

		// Guest – try WooCommerce session.
		if ( WC()->session ) {
			$email = WC()->session->get( 'frc_guest_email' );
			if ( $email ) {
				return sanitize_email( $email );
			}
		}

		return '';
	}
}
