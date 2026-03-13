<?php
/**
 * Browse abandonment tracking. (Pro feature)
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Browse_Abandonment
 */
class FRC_Browse_Abandonment {

	/**
	 * Constructor – register AJAX handlers and cron.
	 */
	public function __construct() {
		if ( ! FRC_PRO_ACTIVE ) {
			return;
		}

		add_action( 'wp_ajax_frc_track_browse', array( $this, 'ajax_track_browse' ) );
		add_action( 'wp_ajax_nopriv_frc_track_browse', array( $this, 'ajax_track_browse' ) );
		add_action( 'frc_process_browse_abandonment', array( $this, 'process_browse_followups' ) );

		if ( ! wp_next_scheduled( 'frc_process_browse_abandonment' ) ) {
			wp_schedule_event( time(), 'hourly', 'frc_process_browse_abandonment' );
		}
	}

	/**
	 * AJAX handler: record a product page view.
	 */
	public function ajax_track_browse() {
		check_ajax_referer( 'frc_browse_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error();
			return;
		}

		global $wpdb;

		$product      = wc_get_product( $product_id );
		$product_name = $product ? $product->get_name() : '';
		$product_url  = $product ? get_permalink( $product_id ) : '';
		$user_id      = get_current_user_id();
		$user_email   = is_user_logged_in() ? wp_get_current_user()->user_email : '';
		$session_key  = WC()->session ? WC()->session->get_customer_id() : '';

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_browse_events',
			array(
				'user_id'      => $user_id,
				'user_email'   => $user_email,
				'session_key'  => $session_key,
				'product_id'   => $product_id,
				'product_name' => $product_name,
				'product_url'  => $product_url,
				'viewed_at'    => current_time( 'mysql' ),
			)
		);

		wp_send_json_success();
	}

	/**
	 * Process browse abandonment follow-ups (cron job).
	 */
	public function process_browse_followups() {
		global $wpdb;

		$delay_hours = (int) get_option( 'frc_browse_followup_hours', 2 );
		$cutoff      = gmdate( 'Y-m-d H:i:s', time() - ( $delay_hours * HOUR_IN_SECONDS ) );

		$events = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}frc_browse_events
				 WHERE followup_sent = 0
				   AND viewed_at <= %s
				   AND user_email != ''
				 LIMIT 20",
				$cutoff
			)
		);

		if ( empty( $events ) ) {
			return;
		}

		foreach ( $events as $event ) {
			// Skip if user has an active cart with this product.
			$has_cart = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts
					 WHERE user_email = %s AND status = 'abandoned' AND cart_contents LIKE %s",
					$event->user_email,
					'%"product_id":' . $event->product_id . '%'
				)
			);

			if ( ! $has_cart ) {
				$this->send_browse_followup( $event );
			}

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_browse_events',
				array( 'followup_sent' => 1 ),
				array( 'id' => (int) $event->id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Send a browse abandonment follow-up email.
	 *
	 * @param object $event Browse event row.
	 */
	private function send_browse_followup( $event ) {
		$subject = sprintf(
			/* translators: %s: product name */
			__( 'You were interested in %s – here\'s 5%% off!', 'flexi-revive-cart' ),
			$event->product_name
		);

		$body = sprintf(
			'<p>' . esc_html__( 'Hi there,', 'flexi-revive-cart' ) . '</p>' .
			'<p>' . esc_html__( 'You recently viewed %1$s. We noticed you didn\'t add it to your cart – here\'s 5%% off to help you decide!', 'flexi-revive-cart' ) . '</p>' .
			'<p><a href="%2$s" style="background:#7f54b3;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;">' . esc_html__( 'Shop Now', 'flexi-revive-cart' ) . '</a></p>',
			esc_html( $event->product_name ),
			esc_url( $event->product_url )
		);

		$from_name  = get_option( 'frc_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'frc_from_email', get_option( 'admin_email' ) );

		wp_mail(
			$event->user_email,
			$subject,
			$body,
			array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $from_name . ' <' . $from_email . '>',
			)
		);
	}
}
