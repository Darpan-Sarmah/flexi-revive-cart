<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package FlexiReviveCart
 */

// Security check.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'frc_abandoned_carts',
	$wpdb->prefix . 'frc_email_logs',
	$wpdb->prefix . 'frc_ab_tests',
	$wpdb->prefix . 'frc_browse_events',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Delete all plugin options with frc_ prefix.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'frc_' ) . '%' )
);

// Delete scheduled cron jobs.
$cron_hooks = array(
	'frc_check_abandoned_carts',
	'frc_cleanup_old_carts',
	'frc_process_browse_abandonment',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
	wp_clear_scheduled_hook( $hook );
}

// Cancel all Action Scheduler actions if available.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	foreach ( $cron_hooks as $hook ) {
		as_unschedule_all_actions( $hook );
	}
}

// Delete all FRC-generated coupons.
$coupon_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_coupon' AND post_title LIKE %s",
		$wpdb->esc_like( 'frc_' ) . '%'
	)
);

if ( ! empty( $coupon_ids ) ) {
	foreach ( $coupon_ids as $coupon_id ) {
		wp_delete_post( (int) $coupon_id, true );
	}
}
