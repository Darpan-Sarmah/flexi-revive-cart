<?php
/**
 * Fired during plugin deactivation.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Deactivator
 *
 * Cleans up scheduled events on plugin deactivation.
 */
class FRC_Deactivator {

	/**
	 * Run deactivation routines.
	 */
	public static function deactivate() {
		// Remove scheduled cron events.
		$hooks = array(
			'frc_check_abandoned_carts',
			'frc_cleanup_old_carts',
			'frc_process_browse_abandonment',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
			wp_clear_scheduled_hook( $hook );
		}

		// Cancel Action Scheduler actions if available.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			foreach ( $hooks as $hook ) {
				as_unschedule_all_actions( $hook );
			}
		}

		flush_rewrite_rules();
	}
}
