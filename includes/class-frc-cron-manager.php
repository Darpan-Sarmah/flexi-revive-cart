<?php
/**
 * Cron / Action Scheduler management.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Cron_Manager
 */
class FRC_Cron_Manager {

	/**
	 * Constructor – register cron hooks.
	 */
	public function __construct() {
		// Register custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );

		// Handle the recurring check.
		add_action( 'frc_check_abandoned_carts', array( $this, 'process_abandoned_carts' ) );
		add_action( 'frc_cleanup_old_carts', array( $this, 'cleanup_old_carts' ) );
		add_action( 'frc_send_reminder', array( $this, 'send_scheduled_reminder' ), 10, 2 );
	}

	/**
	 * Register the 15-minute cron interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_intervals( $schedules ) {
		if ( ! isset( $schedules['frc_15_minutes'] ) ) {
			$schedules['frc_15_minutes'] = array(
				'interval' => 900,
				'display'  => __( 'Every 15 Minutes (FRC)', 'flexi-revive-cart' ),
			);
		}
		return $schedules;
	}

	/**
	 * Check for carts that are due for a reminder email.
	 * Processes in batches of 20 to avoid timeouts.
	 */
	public function process_abandoned_carts() {
		if ( ! get_option( 'frc_enable_email_reminders', '1' ) ) {
			return;
		}

		global $wpdb;

		$timeout   = (int) get_option( 'frc_abandonment_timeout', 60 );
		$interval  = (int) get_option( 'frc_reminder_interval', 1 );

		// Fetch abandoned carts not yet expired or recovered, in batches of 20.
		$carts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}frc_abandoned_carts
				 WHERE status = 'abandoned'
				   AND opted_out = 0
				   AND abandoned_at <= %s
				 ORDER BY abandoned_at ASC
				 LIMIT 20",
				gmdate( 'Y-m-d H:i:s', time() - ( $timeout * MINUTE_IN_SECONDS ) )
			)
		);

		if ( empty( $carts ) ) {
			return;
		}

		require_once FRC_PLUGIN_DIR . 'includes/class-frc-email-manager.php';
		$email_manager = new FRC_Email_Manager();

		foreach ( $carts as $cart ) {
			$stage = $this->get_due_stage( $cart, $interval );
			if ( $stage ) {
				// Fire hook before processing (wrapped in try-catch for third-party safety).
				try {
					/**
					 * Fires after a cart is identified as abandoned and is about to receive a reminder.
					 *
					 * @param int $cart_id Cart ID.
					 * @param int $user_id User ID (0 for guests).
					 */
					do_action( 'frc_after_cart_tracked', (int) $cart->id, (int) $cart->user_id );
				} catch ( \Exception $e ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'FRC Hook Error (frc_after_cart_tracked): ' . $e->getMessage() );
				}

				$this->dispatch_reminder( $cart, $stage, $email_manager );
			}

			// Expire carts after the last reminder + 24h buffer.
			$max_reminders_count = FRC_PRO_ACTIVE ? (int) get_option( 'frc_num_reminders', 3 ) : min( (int) get_option( 'frc_num_reminders', 3 ), 3 );
			$expire_after        = ( $interval * $max_reminders_count + 24 ) * HOUR_IN_SECONDS;
			if ( ( time() - strtotime( $cart->abandoned_at ) ) > $expire_after ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->prefix . 'frc_abandoned_carts',
					array( 'status' => 'expired' ),
					array( 'id' => (int) $cart->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Determine which reminder stage is due for a cart, if any.
	 *
	 * @param object $cart     Cart row.
	 * @param int    $interval Interval in hours between each reminder.
	 * @return int|false Stage number or false.
	 */
	private function get_due_stage( $cart, $interval ) {
		$emails_sent   = (int) $cart->emails_sent;
		$next_stage    = $emails_sent + 1;
		$max_reminders = FRC_PRO_ACTIVE ? (int) get_option( 'frc_num_reminders', 3 ) : min( (int) get_option( 'frc_num_reminders', 3 ), 3 );

		if ( $next_stage > $max_reminders ) {
			return false;
		}

		$due_after = strtotime( $cart->abandoned_at ) + ( $interval * $next_stage * HOUR_IN_SECONDS );

		if ( time() >= $due_after ) {
			return $next_stage;
		}

		return false;
	}

	/**
	 * Dispatch a reminder via Action Scheduler (preferred) or wp_mail directly.
	 *
	 * @param object            $cart          Cart row.
	 * @param int               $stage         Reminder stage.
	 * @param FRC_Email_Manager $email_manager Email manager instance.
	 */
	private function dispatch_reminder( $cart, $stage, $email_manager ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				'frc_send_reminder',
				array(
					'cart_id' => (int) $cart->id,
					'stage'   => $stage,
				),
				'flexi-revive-cart'
			);
		} else {
			$email_manager->send_reminder( $cart, $stage );
		}

		// Dispatch SMS when enabled (Pro).
		if ( FRC_PRO_ACTIVE && get_option( 'frc_enable_sms', '0' ) && class_exists( 'FRC_SMS_Manager' ) ) {
			$sms = new FRC_SMS_Manager();
			$sms->send_sms( $cart, $stage );
		}

		// Dispatch Push notifications when enabled (Pro).
		if ( FRC_PRO_ACTIVE && get_option( 'frc_enable_push', '0' ) && class_exists( 'FRC_Push_Manager' ) ) {
			$push = new FRC_Push_Manager();
			$push->send_push( $cart, $stage );
		}
	}

	/**
	 * Action Scheduler callback – send a scheduled reminder.
	 *
	 * @param int $cart_id Cart ID.
	 * @param int $stage   Email stage.
	 */
	public function send_scheduled_reminder( $cart_id, $stage ) {
		$cart = FRC_Helpers::get_cart_by_id( $cart_id );
		if ( ! $cart ) {
			return;
		}
		$email_manager = new FRC_Email_Manager();
		$email_manager->send_reminder( $cart, $stage );
	}

	/**
	 * Delete carts older than the configured retention period.
	 */
	public function cleanup_old_carts() {
		global $wpdb;

		$days = (int) get_option( 'frc_data_retention_days', 90 );
		if ( $days < 1 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}frc_abandoned_carts WHERE created_at < %s",
				$cutoff
			)
		);

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}frc_email_logs WHERE sent_at < %s",
				$cutoff
			)
		);
	}
}
