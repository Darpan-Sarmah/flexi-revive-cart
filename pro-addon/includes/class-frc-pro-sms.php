<?php
/**
 * SMS notification manager via Twilio or Plivo. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_SMS
 */
class FRC_Pro_SMS {

	/**
	 * Send an SMS reminder for an abandoned cart.
	 *
	 * Hooked to: frc_dispatch_reminder
	 *
	 * @param object $cart  Cart row.
	 * @param int    $stage Reminder stage.
	 * @return bool
	 */
	public function send_sms( $cart, $stage ) {
		if ( ! get_option( 'frc_enable_sms', '0' ) ) {
			return false;
		}

		$sms_sent = isset( $cart->sms_sent ) ? (int) $cart->sms_sent : 0;
		if ( $sms_sent >= 3 ) {
			return false;
		}

		$provider = get_option( 'frc_sms_provider', 'twilio' );
		$vars     = $this->build_sms_vars( $cart );
		$template = get_option( 'frc_sms_template_' . $stage, $this->default_sms_template( $stage ) );
		$message  = $this->replace_vars( $template, $vars );

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
		}

		return $sent;
	}

	/**
	 * Build template variables from a cart row.
	 *
	 * @param object $cart Cart row.
	 * @return array
	 */
	private function build_sms_vars( $cart ) {
		$user_name = '';
		if ( $cart->user_id ) {
			$user = get_user_by( 'id', $cart->user_id );
			if ( $user ) {
				$user_name = $user->display_name;
			}
		}
		if ( empty( $user_name ) && ! empty( $cart->user_email ) ) {
			$user_name = explode( '@', $cart->user_email )[0];
		}

		return array(
			'{user_name}'      => $user_name,
			'{cart_total}'     => FRC_Helpers::format_currency( $cart->cart_total ),
			'{store_name}'     => get_bloginfo( 'name' ),
			'{recovery_link}'  => FRC_Helpers::get_recovery_url( $cart->recovery_token ),
			'{cart_link}'      => wc_get_cart_url(),
			'{discount_code}'  => ! empty( $cart->discount_code ) ? $cart->discount_code : '',
			'{discount_amount}' => '',
			'{abandoned_time}' => FRC_Helpers::time_ago( $cart->abandoned_at ),
		);
	}

	/**
	 * Replace {placeholder} variables in a template string.
	 *
	 * @param string $template Template with placeholders.
	 * @param array  $vars     Key-value pairs.
	 * @return string
	 */
	private function replace_vars( $template, $vars ) {
		return str_replace( array_keys( $vars ), array_values( $vars ), $template );
	}

	/**
	 * Send message via Twilio API.
	 *
	 * @param string $to      Destination phone number.
	 * @param string $message SMS body.
	 * @return bool
	 */
	private function send_via_twilio( $to, $message ) {
		$sid   = get_option( 'frc_twilio_sid', '' );
		$token = get_option( 'frc_twilio_token', '' );
		$from  = get_option( 'frc_twilio_from', '' );

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
	 * Get customer phone number.
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
	 * Default SMS template for a given stage.
	 *
	 * @param int $stage Stage number.
	 * @return string
	 */
	private function default_sms_template( $stage ) {
		$templates = array(
			1 => __( 'Hi {user_name}, you left {cart_total} worth of items in your cart at {store_name}. Complete your purchase: {recovery_link}', 'flexi-revive-cart-pro' ),
			2 => __( 'Hi {user_name}, your cart at {store_name} is waiting! Items may sell out soon. Buy now: {recovery_link}', 'flexi-revive-cart-pro' ),
			3 => __( 'Hi {user_name}, use code {discount_code} for {discount_amount} off your order at {store_name}. Claim your discount: {recovery_link}', 'flexi-revive-cart-pro' ),
		);
		return isset( $templates[ $stage ] ) ? $templates[ $stage ] : $templates[1];
	}
}
