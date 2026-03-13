<?php
/**
 * Push notification manager via OneSignal. (Pro feature)
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Push_Manager
 */
class FRC_Push_Manager {

	/**
	 * OneSignal REST API base URL.
	 *
	 * @var string
	 */
	const ONESIGNAL_API = 'https://onesignal.com/api/v1/notifications';

	/**
	 * Send a push notification for an abandoned cart.
	 *
	 * @param object $cart  Cart row.
	 * @param int    $stage Reminder stage.
	 * @return bool
	 */
	public function send_push( $cart, $stage ) {
		if ( ! FRC_PRO_ACTIVE || ! get_option( 'frc_enable_push', '0' ) ) {
			return false;
		}

		if ( (int) $cart->push_sent >= 3 ) {
			return false;
		}

		$app_id  = get_option( 'frc_onesignal_app_id', '' );
		$api_key = get_option( 'frc_onesignal_api_key', '' );

		if ( ! $app_id || ! $api_key ) {
			return false;
		}

		$vars    = FRC_Email_Templates::build_vars( $cart );
		$title   = FRC_Email_Templates::replace_vars(
			get_option( 'frc_push_title_' . $stage, __( 'Your cart is waiting!', 'flexi-revive-cart' ) ),
			$vars
		);
		$message = FRC_Email_Templates::replace_vars(
			get_option( 'frc_push_message_' . $stage, __( 'Complete your purchase at {store_name}.', 'flexi-revive-cart' ) ),
			$vars
		);

		// Target by external user ID (requires OneSignal External User ID to be set).
		$external_id = $cart->user_id ? 'user_' . $cart->user_id : 'session_' . $cart->session_key;

		$body = array(
			'app_id'            => $app_id,
			'include_external_user_ids' => array( $external_id ),
			'headings'          => array( 'en' => $title ),
			'contents'          => array( 'en' => $message ),
			'url'               => FRC_Helpers::get_recovery_url( $cart->recovery_token ),
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
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_abandoned_carts',
				array( 'push_sent' => (int) $cart->push_sent + 1 ),
				array( 'id' => (int) $cart->id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		return $success;
	}
}
