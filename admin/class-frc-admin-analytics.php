<?php
/**
 * Advanced Recovery Analytics admin page. (Pro feature)
 *
 * Provides two analytics tables:
 * 1. Recovery Analytics by Channel (Email / SMS / WhatsApp / Push) with
 *    Sent, Opened, Clicked, Recovered, Revenue Recovered, Conversion Rate.
 * 2. WhatsApp Bulk Campaigns history table.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_Analytics
 */
class FRC_Admin_Analytics {

	/**
	 * Render the analytics page.
	 */
	public function render() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'recovery'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Recovery Analytics', 'flexi-revive-cart' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-analytics&tab=recovery' ) ); ?>" class="nav-tab <?php echo 'recovery' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Channel Analytics', 'flexi-revive-cart' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-analytics&tab=whatsapp' ) ); ?>" class="nav-tab <?php echo 'whatsapp' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'WhatsApp Campaigns', 'flexi-revive-cart' ); ?>
				</a>
			</nav>

			<div style="margin-top:20px;">
				<?php if ( 'recovery' === $active_tab ) : ?>
					<?php $this->render_channel_analytics(); ?>
				<?php else : ?>
					<?php $this->render_whatsapp_campaigns(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the channel-level recovery analytics table.
	 *
	 * Columns: Channel | Sent | Opened | Clicked | Recovered | Revenue Recovered | Conversion Rate%
	 */
	private function render_channel_analytics() {
		global $wpdb;

		// Date range filter.
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';     // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Channel filter.
		$filter_channel = isset( $_GET['channel'] ) ? sanitize_key( wp_unslash( $_GET['channel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$valid_channels = array( '', 'email', 'sms', 'whatsapp', 'push' );
		if ( ! in_array( $filter_channel, $valid_channels, true ) ) {
			$filter_channel = '';
		}

		?>
		<h2><?php esc_html_e( 'Recovery Analytics by Channel', 'flexi-revive-cart' ); ?></h2>

		<!-- Filters -->
		<form method="get" style="margin-bottom:16px;">
			<input type="hidden" name="page" value="frc-analytics" />
			<input type="hidden" name="tab" value="recovery" />
			<label>
				<?php esc_html_e( 'From:', 'flexi-revive-cart' ); ?>
				<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
			</label>
			&nbsp;
			<label>
				<?php esc_html_e( 'To:', 'flexi-revive-cart' ); ?>
				<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
			</label>
			&nbsp;
			<label>
				<?php esc_html_e( 'Channel:', 'flexi-revive-cart' ); ?>
				<select name="channel">
					<option value=""><?php esc_html_e( 'All Channels', 'flexi-revive-cart' ); ?></option>
					<option value="email" <?php selected( $filter_channel, 'email' ); ?>><?php esc_html_e( 'Email', 'flexi-revive-cart' ); ?></option>
					<option value="sms" <?php selected( $filter_channel, 'sms' ); ?>><?php esc_html_e( 'SMS', 'flexi-revive-cart' ); ?></option>
					<option value="whatsapp" <?php selected( $filter_channel, 'whatsapp' ); ?>><?php esc_html_e( 'WhatsApp', 'flexi-revive-cart' ); ?></option>
					<option value="push" <?php selected( $filter_channel, 'push' ); ?>><?php esc_html_e( 'Push', 'flexi-revive-cart' ); ?></option>
				</select>
			</label>
			&nbsp;
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'flexi-revive-cart' ); ?></button>
			<?php if ( $date_from || $date_to || $filter_channel ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-analytics&tab=recovery' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'flexi-revive-cart' ); ?></a>
			<?php endif; ?>
		</form>
		<?php

		$channels    = $filter_channel ? array( $filter_channel ) : array( 'email', 'sms', 'whatsapp', 'push' );
		$logs_date_where  = '';
		$carts_date_where = '';
		$date_params      = array();

		if ( $date_from ) {
			$logs_date_where   .= ' AND l.sent_at >= %s';
			$carts_date_where  .= ' AND c.recovered_at >= %s';
			$date_params[]      = $date_from . ' 00:00:00';
		}
		if ( $date_to ) {
			$logs_date_where   .= ' AND l.sent_at <= %s';
			$carts_date_where  .= ' AND c.recovered_at <= %s';
			$date_params[]      = $date_to . ' 23:59:59';
		}

		$rows = array();
		foreach ( $channels as $channel ) {
			$log_params = array_merge( array( $channel ), $date_params );
			$log_query  = $wpdb->prepare(
				"SELECT
					COUNT(*) AS sent,
					SUM(CASE WHEN l.status IN ('opened','clicked') THEN 1 ELSE 0 END) AS opened,
					SUM(CASE WHEN l.status = 'clicked' THEN 1 ELSE 0 END) AS clicked
				FROM {$wpdb->prefix}frc_email_logs l
				WHERE l.channel = %s" . $logs_date_where, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$log_params
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$stats = $wpdb->get_row( $log_query );

			// Count recovered carts attributed to this channel.
			$rec_params = array_merge( array( $channel ), $date_params );
			$rec_query  = $wpdb->prepare(
				"SELECT COUNT(*) AS recovered, COALESCE(SUM(c.cart_total), 0) AS revenue
				FROM {$wpdb->prefix}frc_abandoned_carts c
				WHERE c.recovery_channel = %s
				AND c.status IN ('recovered','converted')" . $carts_date_where, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$rec_params
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rec = $wpdb->get_row( $rec_query );

			$sent            = (int) $stats->sent;
			$conversion_rate = $sent > 0 ? round( ( (int) $rec->recovered / $sent ) * 100, 1 ) : 0;

			$rows[] = array(
				'channel'   => ucfirst( $channel ),
				'sent'      => $sent,
				'opened'    => (int) $stats->opened,
				'clicked'   => (int) $stats->clicked,
				'recovered' => (int) $rec->recovered,
				'revenue'   => (float) $rec->revenue,
				'rate'      => $conversion_rate,
			);
		}

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Channel', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Opened', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Clicked', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Recovered', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Revenue Recovered', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Conversion Rate', 'flexi-revive-cart' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $row['channel'] ) . '</strong></td>';
			echo '<td>' . esc_html( number_format( $row['sent'] ) ) . '</td>';
			echo '<td>' . esc_html( number_format( $row['opened'] ) ) . '</td>';
			echo '<td>' . esc_html( number_format( $row['clicked'] ) ) . '</td>';
			echo '<td>' . esc_html( number_format( $row['recovered'] ) ) . '</td>';
			echo '<td>' . wp_kses_post( FRC_Helpers::format_currency( $row['revenue'] ) ) . '</td>';
			echo '<td>' . esc_html( $row['rate'] ) . '%</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the WhatsApp bulk campaigns analytics table.
	 *
	 * Columns: Campaign Name | Sent At | Recipients | Delivered | Read | Clicks | Status
	 */
	private function render_whatsapp_campaigns() {
		global $wpdb;

		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';     // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<h2><?php esc_html_e( 'WhatsApp Bulk Campaigns', 'flexi-revive-cart' ); ?></h2>

		<form method="get" style="margin-bottom:16px;">
			<input type="hidden" name="page" value="frc-analytics" />
			<input type="hidden" name="tab" value="whatsapp" />
			<label>
				<?php esc_html_e( 'From:', 'flexi-revive-cart' ); ?>
				<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
			</label>
			&nbsp;
			<label>
				<?php esc_html_e( 'To:', 'flexi-revive-cart' ); ?>
				<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
			</label>
			&nbsp;
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'flexi-revive-cart' ); ?></button>
			<?php if ( $date_from || $date_to ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-analytics&tab=whatsapp' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'flexi-revive-cart' ); ?></a>
			<?php endif; ?>
		</form>
		<?php

		$where  = '1=1';
		$params = array();

		if ( $date_from ) {
			$where   .= ' AND sent_at >= %s';
			$params[] = $date_from . ' 00:00:00';
		}
		if ( $date_to ) {
			$where   .= ' AND sent_at <= %s';
			$params[] = $date_to . ' 23:59:59';
		}

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$campaigns = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}frc_whatsapp_campaigns WHERE {$where} ORDER BY sent_at DESC LIMIT 100", $params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$campaigns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_whatsapp_campaigns ORDER BY sent_at DESC LIMIT 100" );
		}

		if ( empty( $campaigns ) ) {
			echo '<p>' . esc_html__( 'No WhatsApp campaigns found.', 'flexi-revive-cart' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=frc-whatsapp' ) ) . '" class="button button-primary">' . esc_html__( 'Create Your First Campaign →', 'flexi-revive-cart' ) . '</a></p>';
			return;
		}

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Campaign Name', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent At', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Recipients', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Delivered', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Read', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Clicks', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'flexi-revive-cart' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $campaigns as $c ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $c->campaign_name ) . '</strong></td>';
			echo '<td>' . esc_html( $c->sent_at ) . '</td>';
			echo '<td>' . esc_html( number_format( (int) $c->recipients ) ) . '</td>';
			echo '<td>' . esc_html( number_format( (int) $c->delivered ) ) . '</td>';
			echo '<td>' . esc_html( number_format( (int) $c->read_count ) ) . '</td>';
			echo '<td>' . esc_html( number_format( (int) $c->clicks ) ) . '</td>';
			echo '<td><span class="frc-badge ' . ( 'completed' === $c->status ? 'frc-badge-recovered' : 'frc-badge-abandoned' ) . '">' . esc_html( ucfirst( $c->status ) ) . '</span></td>';
			echo '<td>';
			echo '<a href="' . esc_url( add_query_arg( array( 'frc_export_wa_csv' => $c->id ), wp_nonce_url( admin_url( 'admin.php?page=frc-analytics&tab=whatsapp' ), 'frc_export_wa_csv' ) ) ) . '" class="button button-small">' . esc_html__( 'Export CSV', 'flexi-revive-cart' ) . '</a>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
