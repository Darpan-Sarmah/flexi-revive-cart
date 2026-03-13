<?php
/**
 * Admin dashboard / analytics page.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_Dashboard
 */
class FRC_Admin_Dashboard {

	/**
	 * Render the dashboard page.
	 */
	public function render() {
		$stats = $this->get_stats();
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Flexi Revive Cart – Dashboard', 'flexi-revive-cart' ); ?></h1>

			<?php if ( ! FRC_PRO_ACTIVE ) : ?>
			<div class="frc-pro-notice notice notice-info inline">
				<p>
					<?php esc_html_e( 'Upgrade to Pro for SMS, push notifications, A/B testing, exit-intent popups, and more!', 'flexi-revive-cart' ); ?>
					<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" class="button button-primary" style="margin-left:10px;"><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></a>
				</p>
			</div>
			<?php endif; ?>

			<!-- Stats Cards -->
			<div class="frc-stats-grid">
				<div class="frc-stat-card">
					<span class="frc-stat-icon dashicons dashicons-cart"></span>
					<div class="frc-stat-value"><?php echo esc_html( $stats['total_abandoned'] ); ?></div>
					<div class="frc-stat-label"><?php esc_html_e( 'Abandoned Carts', 'flexi-revive-cart' ); ?></div>
				</div>
				<div class="frc-stat-card frc-stat-recovered">
					<span class="frc-stat-icon dashicons dashicons-yes-alt"></span>
					<div class="frc-stat-value"><?php echo esc_html( $stats['total_recovered'] ); ?></div>
					<div class="frc-stat-label"><?php esc_html_e( 'Recovered Carts', 'flexi-revive-cart' ); ?></div>
				</div>
				<div class="frc-stat-card frc-stat-rate">
					<span class="frc-stat-icon dashicons dashicons-chart-line"></span>
					<div class="frc-stat-value"><?php echo esc_html( $stats['recovery_rate'] ); ?>%</div>
					<div class="frc-stat-label"><?php esc_html_e( 'Recovery Rate', 'flexi-revive-cart' ); ?></div>
				</div>
				<div class="frc-stat-card frc-stat-revenue">
					<span class="frc-stat-icon dashicons dashicons-money-alt"></span>
					<div class="frc-stat-value"><?php echo wp_kses_post( FRC_Helpers::format_currency( $stats['revenue_recovered'] ) ); ?></div>
					<div class="frc-stat-label"><?php esc_html_e( 'Revenue Recovered', 'flexi-revive-cart' ); ?></div>
				</div>
				<div class="frc-stat-card frc-stat-lost">
					<span class="frc-stat-icon dashicons dashicons-dismiss"></span>
					<div class="frc-stat-value"><?php echo wp_kses_post( FRC_Helpers::format_currency( $stats['revenue_lost'] ) ); ?></div>
					<div class="frc-stat-label"><?php esc_html_e( 'Revenue Lost', 'flexi-revive-cart' ); ?></div>
				</div>
			</div>

			<!-- Charts -->
			<div class="frc-charts-grid">
				<div class="frc-chart-card">
					<h3><?php esc_html_e( 'Abandoned vs Recovered (Last 30 Days)', 'flexi-revive-cart' ); ?></h3>
					<canvas id="frc-line-chart" height="300"></canvas>
				</div>
				<div class="frc-chart-card">
					<h3><?php esc_html_e( 'Recovery by Channel', 'flexi-revive-cart' ); ?></h3>
					<canvas id="frc-pie-chart" height="300"></canvas>
				</div>
				<div class="frc-chart-card">
					<h3><?php esc_html_e( 'Recovery Rate by Email Stage', 'flexi-revive-cart' ); ?></h3>
					<canvas id="frc-bar-chart" height="300"></canvas>
				</div>
			</div>

			<?php if ( FRC_PRO_ACTIVE ) : ?>
			<div class="frc-pro-section">
				<h2><?php esc_html_e( 'A/B Test Summary', 'flexi-revive-cart' ); ?></h2>
				<?php $this->render_ab_summary(); ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render A/B test winner summary.
	 */
	private function render_ab_summary() {
		if ( ! class_exists( 'FRC_AB_Testing' ) ) {
			return;
		}
		$ab_testing = new FRC_AB_Testing();
		$results    = $ab_testing->get_results();

		if ( empty( $results ) ) {
			echo '<p>' . esc_html__( 'No A/B tests found.', 'flexi-revive-cart' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Test Name', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Winner', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'A Sent / Recovered', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'B Sent / Recovered', 'flexi-revive-cart' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $results as $test ) {
			echo '<tr>';
			echo '<td>' . esc_html( $test->test_name ) . '</td>';
			echo '<td>' . esc_html( $test->status ) . '</td>';
			echo '<td>' . esc_html( $test->winner ? strtoupper( $test->winner ) : '–' ) . '</td>';
			echo '<td>' . esc_html( $test->variant_a_sent . ' / ' . $test->variant_a_recovered ) . '</td>';
			echo '<td>' . esc_html( $test->variant_b_sent . ' / ' . $test->variant_b_recovered ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Get summary statistics.
	 *
	 * @return array
	 */
	public function get_stats() {
		$transient_key = 'frc_dashboard_stats';
		$cached        = get_transient( $transient_key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$total_abandoned  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('abandoned','expired')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total_recovered  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('recovered','converted')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$revenue_recovered = (float) $wpdb->get_var( "SELECT SUM(cart_total) FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('recovered','converted')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$revenue_lost      = (float) $wpdb->get_var( "SELECT SUM(cart_total) FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('abandoned','expired')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		$total_all     = $total_abandoned + $total_recovered;
		$recovery_rate = $total_all > 0 ? round( ( $total_recovered / $total_all ) * 100, 1 ) : 0;

		$stats = array(
			'total_abandoned'   => $total_abandoned,
			'total_recovered'   => $total_recovered,
			'recovery_rate'     => $recovery_rate,
			'revenue_recovered' => $revenue_recovered,
			'revenue_lost'      => $revenue_lost,
		);

		set_transient( $transient_key, $stats, 5 * MINUTE_IN_SECONDS );
		return $stats;
	}

	/**
	 * Get chart data for last 30 days.
	 *
	 * @return array
	 */
	public function get_chart_data() {
		global $wpdb;

		// Line chart: abandoned vs recovered last 30 days.
		$dates     = array();
		$abandoned = array();
		$recovered = array();

		for ( $i = 29; $i >= 0; $i-- ) {
			$date        = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$dates[]     = $date;
			$abandoned[] = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE DATE(abandoned_at) = %s AND status IN ('abandoned','expired')",
				$date
			) );
			$recovered[] = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE DATE(recovered_at) = %s AND status IN ('recovered','converted')",
				$date
			) );
		}

		// Pie chart: channel breakdown.
		$channels = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT recovery_channel, COUNT(*) as count FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('recovered','converted') GROUP BY recovery_channel" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		// Bar chart: recovery rate by email stage.
		$bar_stages = array();
		for ( $stage = 1; $stage <= 3; $stage++ ) {
			$recovered_by_stage = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE status IN ('recovered','converted') AND emails_sent = %d",
				$stage
			) );
			$bar_stages[ 'Stage ' . $stage ] = $recovered_by_stage;
		}

		return array(
			'line' => array(
				'labels'    => $dates,
				'abandoned' => $abandoned,
				'recovered' => $recovered,
			),
			'pie'  => $channels,
			'bar'  => $bar_stages,
		);
	}
}
