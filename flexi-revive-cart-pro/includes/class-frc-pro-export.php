<?php
/**
 * CSV export for abandoned/recovered carts. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Export
 */
class FRC_Pro_Export {

	/**
	 * Handle an export download request.
	 */
	public function handle_export_request() {
		if ( ! isset( $_GET['frc_export_csv'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export cart data.', 'flexi-revive-cart-pro' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'frc_export_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'flexi-revive-cart-pro' ) );
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$this->stream_csv( $status );
		exit;
	}

	/**
	 * Stream CSV output to the browser.
	 *
	 * @param string $status Optional status filter.
	 */
	private function stream_csv( $status = '' ) {
		global $wpdb;

		$allowed_statuses = array( 'abandoned', 'recovered', 'converted', 'expired' );
		$where  = '1=1';
		$params = array();

		if ( $status && in_array( $status, $allowed_statuses, true ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY id DESC", $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		} else {
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$filename = 'frc-carts-' . gmdate( 'Y-m-d' ) . '.csv';

		if ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// UTF-8 BOM.
		fwrite( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv( $output, array(
			'ID', 'Email', 'Cart Total', 'Currency', 'Items', 'Status',
			'Emails Sent', 'Discount Code', 'Recovery Channel',
			'Abandoned At', 'Recovered At', 'Recovered Order ID',
		) );

		foreach ( $rows as $row ) {
			$items = FRC_Helpers::unserialize_cart( $row->cart_contents );
			$items_summary = implode( ', ', array_map( function ( $item ) {
				$name = ! empty( $item['product_name'] ) ? $item['product_name'] : ( 'Product #' . $item['product_id'] );
				return $name . ' x' . (int) $item['quantity'];
			}, $items ) );

			fputcsv( $output, array(
				$row->id,
				$row->user_email,
				$row->cart_total,
				$row->currency,
				$items_summary,
				$row->status,
				$row->emails_sent,
				$row->discount_code,
				$row->recovery_channel,
				$row->abandoned_at,
				$row->recovered_at,
				$row->recovered_order_id,
			) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
}
