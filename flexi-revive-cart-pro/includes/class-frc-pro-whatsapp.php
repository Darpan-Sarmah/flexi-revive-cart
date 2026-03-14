<?php
/**
 * WhatsApp notification manager via Twilio or Plivo. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_WhatsApp
 */
class FRC_Pro_WhatsApp {

	/**
	 * Send a WhatsApp reminder for an abandoned cart.
	 *
	 * Hooked to: frc_dispatch_reminder
	 *
	 * @param object $cart  Cart row.
	 * @param int    $stage Reminder stage.
	 * @return bool
	 */
	public function send_whatsapp( $cart, $stage ) {
		if ( ! get_option( 'frc_enable_whatsapp', '0' ) ) {
			return false;
		}

		$wa_sent = isset( $cart->whatsapp_sent ) ? (int) $cart->whatsapp_sent : 0;
		if ( $wa_sent >= 3 ) {
			return false;
		}

		$vars     = $this->build_vars( $cart );
		$template = get_option( 'frc_whatsapp_template_' . $stage, $this->default_template( $stage ) );
		$message  = $this->replace_vars( $template, $vars );

		$phone = $this->get_customer_phone( $cart );
		if ( ! $phone ) {
			return false;
		}

		$sent = $this->dispatch_message( $phone, $message );

		if ( $sent ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array(
					'cart_id'       => (int) $cart->id,
					'email_to'      => $phone,
					'email_subject' => 'WhatsApp Stage ' . $stage,
					'email_body'    => $message,
					'channel'       => 'whatsapp',
					'status'        => 'sent',
					'sent_at'       => current_time( 'mysql' ),
				)
			);
		}

		return $sent;
	}

	/**
	 * Send a bulk WhatsApp campaign.
	 *
	 * @param string $campaign_name Human-readable name.
	 * @param string $message_tpl   Message template with {placeholder} vars.
	 * @param int    $delay_hours   Only target carts abandoned at least this many hours ago.
	 * @return array { sent: int, failed: int, campaign_id: int }
	 */
	public function send_bulk_campaign( $campaign_name, $message_tpl, $delay_hours = 0 ) {
		global $wpdb;

		$delay_hours = absint( $delay_hours );
		$where = "status = 'abandoned' AND opted_out = 0";

		if ( $delay_hours > 0 ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $delay_hours . ' hours' ) );
			$where .= $wpdb->prepare( ' AND abandoned_at <= %s', $cutoff ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$carts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where}" );

		$sent_count   = 0;
		$failed_count = 0;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_whatsapp_campaigns',
			array(
				'campaign_name' => $campaign_name,
				'message_body'  => $message_tpl,
				'recipients'    => count( $carts ),
				'sent'          => 0,
				'delivered'     => 0,
				'read_count'    => 0,
				'clicks'        => 0,
				'status'        => 'sending',
				'sent_at'       => current_time( 'mysql' ),
			)
		);
		$campaign_id = (int) $wpdb->insert_id;

		foreach ( $carts as $cart ) {
			$phone = $this->get_customer_phone( $cart );
			if ( ! $phone ) {
				$failed_count++;
				continue;
			}

			$vars    = $this->build_vars( $cart );
			$message = $this->replace_vars( $message_tpl, $vars );
			$sent    = $this->dispatch_message( $phone, $message );

			if ( $sent ) {
				$sent_count++;
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prefix . 'frc_email_logs',
					array(
						'cart_id'       => (int) $cart->id,
						'email_to'      => $phone,
						'email_subject' => 'Bulk Campaign: ' . $campaign_name,
						'email_body'    => $message,
						'channel'       => 'whatsapp',
						'status'        => 'sent',
						'sent_at'       => current_time( 'mysql' ),
					)
				);
			} else {
				$failed_count++;
			}
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_whatsapp_campaigns',
			array( 'sent' => $sent_count, 'status' => 'completed' ),
			array( 'id' => $campaign_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return array(
			'sent'        => $sent_count,
			'failed'      => $failed_count,
			'campaign_id' => $campaign_id,
		);
	}

	/**
	 * Dispatch a WhatsApp message via the configured provider.
	 *
	 * @param string $to      Phone number (E.164).
	 * @param string $message Message body.
	 * @return bool
	 */
	private function dispatch_message( $to, $message ) {
		$provider = get_option( 'frc_whatsapp_provider', 'twilio' );

		if ( 'twilio' === $provider ) {
			return $this->send_via_twilio( $to, $message );
		} elseif ( 'plivo' === $provider ) {
			return $this->send_via_plivo( $to, $message );
		}

		return false;
	}

	/**
	 * Send via Twilio WhatsApp API.
	 *
	 * @param string $to      Phone number.
	 * @param string $message Body text.
	 * @return bool
	 */
	private function send_via_twilio( $to, $message ) {
		$sid   = get_option( 'frc_twilio_sid', '' );
		$token = get_option( 'frc_twilio_token', '' );
		$from  = get_option( 'frc_whatsapp_from', '' );

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
					'To'   => 'whatsapp:' . $to,
					'From' => 'whatsapp:' . $from,
					'Body' => $message,
				),
				'timeout' => 15,
			)
		);

		return ! is_wp_error( $response ) && 201 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Send via Plivo WhatsApp API.
	 *
	 * @param string $to      Phone number.
	 * @param string $message Body text.
	 * @return bool
	 */
	private function send_via_plivo( $to, $message ) {
		$auth_id    = get_option( 'frc_plivo_auth_id', '' );
		$auth_token = get_option( 'frc_plivo_auth_token', '' );
		$src        = get_option( 'frc_whatsapp_from', '' );

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
					'type' => 'whatsapp',
				) ),
				'timeout' => 15,
			)
		);

		return ! is_wp_error( $response ) && 202 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Build template variables from a cart row.
	 *
	 * @param object $cart Cart row.
	 * @return array
	 */
	private function build_vars( $cart ) {
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
			'{user_name}'       => $user_name,
			'{cart_total}'      => FRC_Helpers::format_currency( $cart->cart_total ),
			'{store_name}'      => get_bloginfo( 'name' ),
			'{recovery_link}'   => FRC_Helpers::get_recovery_url( $cart->recovery_token ),
			'{cart_link}'       => wc_get_cart_url(),
			'{discount_code}'   => ! empty( $cart->discount_code ) ? $cart->discount_code : '',
			'{discount_amount}' => '',
			'{abandoned_time}'  => FRC_Helpers::time_ago( $cart->abandoned_at ),
		);
	}

	/**
	 * Replace placeholders in a template.
	 *
	 * @param string $template Template string.
	 * @param array  $vars     Variables.
	 * @return string
	 */
	private function replace_vars( $template, $vars ) {
		return str_replace( array_keys( $vars ), array_values( $vars ), $template );
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
	 * Default WhatsApp template for a given stage.
	 *
	 * @param int $stage Stage number.
	 * @return string
	 */
	private function default_template( $stage ) {
		$templates = array(
			1 => __( 'Hi {user_name}, you left {cart_total} worth of items in your cart at {store_name}. Complete your purchase: {cart_link}', 'flexi-revive-cart-pro' ),
			2 => __( 'Hi {user_name}, your cart at {store_name} is waiting! Items may sell out soon. Buy now: {cart_link}', 'flexi-revive-cart-pro' ),
			3 => __( 'Hi {user_name}, use code {discount_code} for {discount_amount} off your order at {store_name}. Claim your discount: {cart_link}', 'flexi-revive-cart-pro' ),
		);
		return isset( $templates[ $stage ] ) ? $templates[ $stage ] : $templates[1];
	}
}
