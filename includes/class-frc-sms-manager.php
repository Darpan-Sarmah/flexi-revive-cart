<?php
/**
 * SMS / WhatsApp notification manager via Twilio or Plivo. (Pro feature)
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_SMS_Manager
 */
class FRC_SMS_Manager {

	/**
	 * Send an SMS reminder for an abandoned cart.
	 *
	 * @param object $cart  Cart row.
	 * @param int    $stage Reminder stage.
	 * @return bool
	 */
	public function send_sms( $cart, $stage ) {
		if ( ! FRC_PRO_ACTIVE || ! get_option( 'frc_enable_sms', '0' ) ) {
			return false;
		}

		// Max 3 SMS per cart.
		if ( (int) $cart->sms_sent >= 3 ) {
			return false;
		}

		$provider = get_option( 'frc_sms_provider', 'twilio' );
		$vars     = FRC_Email_Templates::build_vars( $cart );
		$template = get_option( 'frc_sms_template_' . $stage, $this->default_sms_template( $stage ) );
		$message  = FRC_Email_Templates::replace_vars( $template, $vars );

		$phone = $this->get_customer_phone( $cart );
		if ( ! $phone ) {
			return false;
		}

		$sent = false;
		if ( 'twilio' === $provider ) {
			$sent = $this->send_via_twilio( $phone, $message );
		} elseif ( 'plivo' === $provider ) {
			$sent = $this->send_via_plivo( $phone, $message );
		}

		if ( $sent ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array(
					'cart_id'       => (int) $cart->id,
					'email_to'      => $phone,
					'email_subject' => 'SMS Stage ' . $stage,
					'email_body'    => $message,
					'channel'       => 'sms',
					'status'        => 'sent',
					'sent_at'       => current_time( 'mysql' ),
				)
			);
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_abandoned_carts',
				array( 'sms_sent' => (int) $cart->sms_sent + 1 ),
				array( 'id' => (int) $cart->id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		return $sent;
	}

	/**
	 * Send message via Twilio API.
	 *
	 * @param string $to      Destination phone number.
	 * @param string $message SMS body.
	 * @return bool
	 */
	private function send_via_twilio( $to, $message ) {
		$sid       = get_option( 'frc_twilio_sid', '' );
		$token     = get_option( 'frc_twilio_token', '' );
		$from      = get_option( 'frc_twilio_from', '' );

		if ( ! $sid || ! $token || ! $from ) {
			return false;
		}

		$response = wp_remote_post(
			'https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json',
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				),
				'body'    => array(
					'To'   => $to,
					'From' => $from,
					'Body' => $message,
				),
				'timeout' => 15,
			)
		);

		return ! is_wp_error( $response ) && 201 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Send message via Plivo API.
	 *
	 * @param string $to      Destination phone number.
	 * @param string $message SMS body.
	 * @return bool
	 */
	private function send_via_plivo( $to, $message ) {
		$auth_id    = get_option( 'frc_plivo_auth_id', '' );
		$auth_token = get_option( 'frc_plivo_auth_token', '' );
		$src        = get_option( 'frc_plivo_from', '' );

		if ( ! $auth_id || ! $auth_token || ! $src ) {
			return false;
		}

		$response = wp_remote_post(
			'https://api.plivo.com/v1/Account/' . $auth_id . '/Message/',
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $auth_id . ':' . $auth_token ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'src'  => $src,
					'dst'  => $to,
					'text' => $message,
				) ),
				'timeout' => 15,
			)
		);

		return ! is_wp_error( $response ) && 202 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Get the customer phone number from a cart row or WooCommerce order history.
	 *
	 * @param object $cart Cart row.
	 * @return string|false
	 */
	private function get_customer_phone( $cart ) {
		if ( $cart->user_id ) {
			$phone = get_user_meta( (int) $cart->user_id, 'billing_phone', true );
			if ( $phone ) {
				return $phone;
			}
		}
		return false;
	}

	/**
	 * Return a default SMS template for a given stage.
	 *
	 * @param int $stage Stage number.
	 * @return string
	 */
	private function default_sms_template( $stage ) {
		$templates = array(
			1 => __( 'Hi {user_name}, you left {cart_total} worth of items in your cart at {store_name}. Complete your purchase: {recovery_link}', 'flexi-revive-cart' ),
			2 => __( 'Hi {user_name}, your cart at {store_name} is waiting! Items may sell out soon. Buy now: {recovery_link}', 'flexi-revive-cart' ),
			3 => __( 'Hi {user_name}, use code {discount_code} for {discount_amount} off your order at {store_name}. Claim your discount: {recovery_link}', 'flexi-revive-cart' ),
		);
		return isset( $templates[ $stage ] ) ? $templates[ $stage ] : $templates[1];
	}
}
