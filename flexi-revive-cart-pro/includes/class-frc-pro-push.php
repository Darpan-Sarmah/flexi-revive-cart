<?php
/**
 * Push notification manager via OneSignal. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Push
 */
class FRC_Pro_Push {

	/**
	 * OneSignal REST API URL.
	 *
	 * @var string
	 */
	const ONESIGNAL_API = 'https://onesignal.com/api/v1/notifications';

	/**
	 * Send a push notification for an abandoned cart.
	 *
	 * Hooked to: frc_dispatch_reminder
	 *
	 * @param object $cart  Cart row.
	 * @param int    $stage Reminder stage.
	 * @return bool
	 */
	public function send_push( $cart, $stage ) {
		if ( ! get_option( 'frc_enable_push', '0' ) ) {
			return false;
		}

		$push_sent = isset( $cart->push_sent ) ? (int) $cart->push_sent : 0;
		if ( $push_sent >= 3 ) {
			return false;
		}

		$app_id  = get_option( 'frc_onesignal_app_id', '' );
		$api_key = get_option( 'frc_onesignal_api_key', '' );

		if ( ! $app_id || ! $api_key ) {
			return false;
		}

		$vars = $this->build_vars( $cart );

		$title = $this->replace_vars(
			get_option( 'frc_push_title_' . $stage, __( 'Your cart is waiting!', 'flexi-revive-cart-pro' ) ),
			$vars
		);
		$message = $this->replace_vars(
			get_option( 'frc_push_message_' . $stage, __( 'Complete your purchase at {store_name}.', 'flexi-revive-cart-pro' ) ),
			$vars
		);

		$external_id = $cart->user_id ? 'user_' . $cart->user_id : 'session_' . $cart->session_key;

		$body = array(
			'app_id'                    => $app_id,
			'include_external_user_ids' => array( $external_id ),
			'headings'                  => array( 'en' => $title ),
			'contents'                  => array( 'en' => $message ),
			'url'                       => FRC_Helpers::get_recovery_url( $cart->recovery_token ),
		);

		$response = wp_remote_post(
			self::ONESIGNAL_API,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $api_key,
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		$success = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );

		if ( $success ) {
			global $wpdb;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_email_logs',
				array(
					'cart_id'       => (int) $cart->id,
					'email_to'      => $external_id,
					'email_subject' => $title,
					'email_body'    => $message,
					'channel'       => 'push',
					'status'        => 'sent',
					'sent_at'       => current_time( 'mysql' ),
				)
			);
		}

		return $success;
	}

	/**
	 * Build template variables.
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

		return array(
			'{user_name}'   => $user_name,
			'{cart_total}'  => FRC_Helpers::format_currency( $cart->cart_total ),
			'{store_name}'  => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Replace placeholders.
	 *
	 * @param string $template Template.
	 * @param array  $vars     Variables.
	 * @return string
	 */
	private function replace_vars( $template, $vars ) {
		return str_replace( array_keys( $vars ), array_values( $vars ), $template );
	}
}
