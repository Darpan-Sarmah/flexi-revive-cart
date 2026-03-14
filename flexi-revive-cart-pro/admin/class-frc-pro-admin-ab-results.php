<?php
/**
 * A/B test results admin page. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Admin_AB_Results
 */
class FRC_Pro_Admin_AB_Results {

	/**
	 * Render the A/B results page.
	 */
	public function render() {
		$ab = new FRC_Pro_AB_Testing();
		$ab->check_for_winners();
		$results = $ab->get_results();

		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'A/B Test Results', 'flexi-revive-cart-pro' ); ?></h1>

			<?php if ( empty( $results ) ) : ?>
				<p><?php esc_html_e( 'No A/B tests have been run yet. Enable A/B testing in Settings to start.', 'flexi-revive-cart-pro' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Test Name', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'Type', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'Status', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'Winner', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'A (Sent/Opened/Recovered)', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'B (Sent/Opened/Recovered)', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'A Rate', 'flexi-revive-cart-pro' ); ?></th>
						<th><?php esc_html_e( 'B Rate', 'flexi-revive-cart-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $test ) : ?>
					<?php
					$rate_a = $test->variant_a_sent > 0 ? round( ( $test->variant_a_recovered / $test->variant_a_sent ) * 100, 1 ) : 0;
					$rate_b = $test->variant_b_sent > 0 ? round( ( $test->variant_b_recovered / $test->variant_b_sent ) * 100, 1 ) : 0;
					?>
					<tr>
						<td><?php echo esc_html( $test->test_name ); ?></td>
						<td><?php echo esc_html( $test->test_type ); ?></td>
						<td><?php echo esc_html( ucfirst( $test->status ) ); ?></td>
						<td><?php echo $test->winner ? '<strong>' . esc_html( strtoupper( $test->winner ) ) . '</strong>' : '–'; ?></td>
						<td><?php echo esc_html( "{$test->variant_a_sent}/{$test->variant_a_opened}/{$test->variant_a_recovered}" ); ?></td>
						<td><?php echo esc_html( "{$test->variant_b_sent}/{$test->variant_b_opened}/{$test->variant_b_recovered}" ); ?></td>
						<td><?php echo esc_html( $rate_a . '%' ); ?></td>
						<td><?php echo esc_html( $rate_b . '%' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
