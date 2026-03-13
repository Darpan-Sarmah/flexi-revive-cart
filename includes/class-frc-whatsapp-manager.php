<?php
/**
 * WhatsApp notification manager via Twilio or Plivo. (Pro feature)
 *
 * Supports:
 * - Single WhatsApp reminder per abandoned cart.
 * - Bulk WhatsApp campaigns to all abandoned-cart users in one click.
 * - Campaign logging in {prefix}frc_whatsapp_campaigns and per-message logging
 *   in {prefix}frc_email_logs with channel='whatsapp'.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_WhatsApp_Manager
 */
class FRC_WhatsApp_Manager {

	/**
	 * Send a WhatsApp reminder for an abandoned cart.
	 *
	 * @param object $cart  Cart row from frc_abandoned_carts.
	 * @param int    $stage Reminder stage (1–3).
	 * @return bool True on success.
	 */
	public function send_whatsapp( $cart, $stage ) {
		if ( ! FRC_PRO_ACTIVE || ! get_option( 'frc_enable_whatsapp', '0' ) ) {
			return false;
		}

		// Max 3 WhatsApp messages per cart.
		if ( isset( $cart->whatsapp_sent ) && (int) $cart->whatsapp_sent >= 3 ) {
			return false;
		}

		$vars     = FRC_Email_Templates::build_vars( $cart );
		$template = get_option( 'frc_whatsapp_template_' . $stage, $this->default_whatsapp_template( $stage ) );
		$message  = FRC_Email_Templates::replace_vars( $template, $vars );

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
	 * Send a bulk WhatsApp campaign to all currently abandoned carts.
	 *
	 * Creates a campaign record in frc_whatsapp_campaigns and sends messages
	 * to all abandoned (not opted-out, phone available) cart users.
	 *
	 * @param string $campaign_name Human-readable campaign name.
	 * @param string $message_tpl   Message template with {placeholder} vars.
	 * @param int    $delay_hours   Only target carts abandoned at least this many hours ago (0 = all).
	 * @return array { sent: int, failed: int, campaign_id: int }
	 */
	public function send_bulk_campaign( $campaign_name, $message_tpl, $delay_hours = 0 ) {
		global $wpdb;

		$where  = "status = 'abandoned' AND opted_out = 0";
		$params = array();

		if ( $delay_hours > 0 ) {
			$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $delay_hours . ' hours' ) );
			$where   .= $wpdb->prepare( ' AND abandoned_at <= %s', $cutoff ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$carts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where}" );

		$sent_count   = 0;
		$failed_count = 0;

		// Insert campaign record first.
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

			$vars    = FRC_Email_Templates::build_vars( $cart );
			$message = FRC_Email_Templates::replace_vars( $message_tpl, $vars );
			$sent    = $this->dispatch_message( $phone, $message );

			if ( $sent ) {
				$sent_count++;
				// Log individual message.
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

		// Update campaign with final sent count.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_whatsapp_campaigns',
			array(
				'sent'   => $sent_count,
				'status' => 'completed',
			),
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
	 * Dispatch a WhatsApp message via the configured provider (Twilio or Plivo).
	 *
	 * @param string $to      Destination phone number (E.164 format, e.g. +14155551234).
	 * @param string $message Message body text.
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
	 * Send a WhatsApp message via Twilio WhatsApp API.
	 *
	 * Twilio WhatsApp requires the "whatsapp:" prefix on both From and To.
	 *
	 * @param string $to      Destination phone number (E.164).
	 * @param string $message Message body.
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
	 * Send a WhatsApp message via Plivo WhatsApp API.
	 *
	 * @param string $to      Destination phone number (E.164).
	 * @param string $message Message body.
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
	 * Retrieve the customer's phone number from user meta or WooCommerce data.
	 *
	 * @param object $cart Cart row.
	 * @return string|false Phone in E.164 format, or false if unavailable.
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
	 * Return the default WhatsApp message template for a given reminder stage.
	 *
	 * @param int $stage Stage number (1–3).
	 * @return string
	 */
	private function default_whatsapp_template( $stage ) {
		$templates = array(
			1 => __( 'Hi {user_name}, you left {cart_total} worth of items in your cart at {store_name}. Complete your purchase: {cart_link}', 'flexi-revive-cart' ),
			2 => __( 'Hi {user_name}, your cart at {store_name} is waiting! Items may sell out soon. Buy now: {cart_link}', 'flexi-revive-cart' ),
			3 => __( 'Hi {user_name}, use code {discount_code} for {discount_amount} off your order at {store_name}. Claim your discount: {cart_link}', 'flexi-revive-cart' ),
		);
		return isset( $templates[ $stage ] ) ? $templates[ $stage ] : $templates[1];
	}
}
