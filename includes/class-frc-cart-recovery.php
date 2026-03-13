<?php
/**
 * Cart recovery / one-click restore logic.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Cart_Recovery
 */
class FRC_Cart_Recovery {

	/**
	 * Constructor – register hooks.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_recovery_request' ) );
	}

	/**
	 * Intercept recovery requests and restore the cart.
	 */
	public function handle_recovery_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['frc_recover'] ) ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['frc_recover'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $token ) ) {
			return;
		}

		$cart = FRC_Helpers::get_cart_by_token( $token );

		if ( ! $cart ) {
			wc_add_notice( __( 'This recovery link is invalid or has expired.', 'flexi-revive-cart' ), 'error' );
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( in_array( $cart->status, array( 'converted', 'expired' ), true ) ) {
			wc_add_notice( __( 'This cart has already been recovered or has expired.', 'flexi-revive-cart' ), 'notice' );
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}

		if ( $cart->opted_out ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		// Restore cart.
		$this->restore_cart( $cart );
	}

	/**
	 * Restore the cart session from saved data and redirect to checkout.
	 *
	 * @param object $cart Database cart row.
	 */
	private function restore_cart( $cart ) {
		global $wpdb;

		// Ensure WooCommerce session is started.
		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		// Clear current cart.
		WC()->cart->empty_cart();

		$items = FRC_Helpers::unserialize_cart( $cart->cart_contents );

		foreach ( $items as $item ) {
			if ( empty( $item['product_id'] ) ) {
				continue;
			}

			$product_id   = (int) $item['product_id'];
			$variation_id = ! empty( $item['variation_id'] ) ? (int) $item['variation_id'] : 0;
			$quantity     = ! empty( $item['quantity'] ) ? (int) $item['quantity'] : 1;
			$variation    = ! empty( $item['variation'] ) ? (array) $item['variation'] : array();

			// Skip products that no longer exist.
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_purchasable() ) {
				continue;
			}

			WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
		}

		// Apply discount coupon (Pro feature).
		if ( FRC_PRO_ACTIVE && ! empty( $cart->discount_code ) ) {
			WC()->cart->apply_coupon( sanitize_text_field( $cart->discount_code ) );
		}

		// Determine recovery channel from query string.
		$channel = isset( $_GET['frc_channel'] ) ? sanitize_key( wp_unslash( $_GET['frc_channel'] ) ) : 'email'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Mark as recovered.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array(
				'status'           => 'recovered',
				'recovered_at'     => current_time( 'mysql' ),
				'recovery_channel' => sanitize_text_field( $channel ),
			),
			array( 'id' => (int) $cart->id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		// Log the click in email logs if a log ID is provided.
		$log_id = isset( $_GET['frc_lid'] ) ? absint( wp_unslash( $_GET['frc_lid'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $log_id ) {
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

		wc_add_notice( __( 'Your cart has been restored! Complete your purchase below.', 'flexi-revive-cart' ), 'success' );

		// Redirect to checkout.
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
}
