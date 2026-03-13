<?php
/**
 * GDPR / CCPA compliance helpers.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Compliance
 */
class FRC_Compliance {

	/**
	 * Constructor – register WordPress privacy hooks and opt-out handler.
	 */
	public function __construct() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		add_action( 'template_redirect', array( $this, 'handle_optout_request' ) );
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @param array $exporters Existing exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['flexi-revive-cart'] = array(
			'exporter_friendly_name' => __( 'Flexi Revive Cart', 'flexi-revive-cart' ),
			'callback'               => array( $this, 'export_user_data' ),
		);
		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array $erasers Existing erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['flexi-revive-cart'] = array(
			'eraser_friendly_name' => __( 'Flexi Revive Cart', 'flexi-revive-cart' ),
			'callback'             => array( $this, 'erase_user_data' ),
		);
		return $erasers;
	}

	/**
	 * Export personal data for a given email.
	 *
	 * @param string $email_address User email.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public function export_user_data( $email_address, $page = 1 ) {
		global $wpdb;

		$carts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, cart_total, currency, status, abandoned_at, recovered_at FROM {$wpdb->prefix}frc_abandoned_carts WHERE user_email = %s",
				$email_address
			)
		);

		$data = array();
		foreach ( $carts as $cart ) {
			$data[] = array(
				'group_id'    => 'frc_carts',
				'group_label' => __( 'Abandoned Carts (Flexi Revive Cart)', 'flexi-revive-cart' ),
				'item_id'     => 'cart-' . $cart->id,
				'data'        => array(
					array( 'name' => __( 'Cart Total', 'flexi-revive-cart' ), 'value' => $cart->cart_total . ' ' . $cart->currency ),
					array( 'name' => __( 'Status', 'flexi-revive-cart' ), 'value' => $cart->status ),
					array( 'name' => __( 'Abandoned At', 'flexi-revive-cart' ), 'value' => $cart->abandoned_at ),
					array( 'name' => __( 'Recovered At', 'flexi-revive-cart' ), 'value' => $cart->recovered_at ),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Erase personal data for a given email.
	 *
	 * @param string $email_address User email.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public function erase_user_data( $email_address, $page = 1 ) {
		global $wpdb;

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array( 'user_email' => $email_address ),
			array( '%s' )
		);

		return array(
			'items_removed'  => $deleted > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Handle opt-out query string requests.
	 */
	public function handle_optout_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['frc_optout'] ) ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['frc_optout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $token ) ) {
			return;
		}

		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array( 'opted_out' => 1 ),
			array( 'recovery_token' => $token ),
			array( '%d' ),
			array( '%s' )
		);

		wc_add_notice( __( 'You have been successfully unsubscribed from cart reminder emails.', 'flexi-revive-cart' ), 'success' );
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
