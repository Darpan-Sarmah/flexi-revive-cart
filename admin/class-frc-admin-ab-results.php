<?php
/**
 * A/B test results admin page (Pro).
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_AB_Results
 */
class FRC_Admin_AB_Results {

	/**
	 * Render the A/B results page.
	 */
	public function render() {
		if ( ! FRC_PRO_ACTIVE || ! class_exists( 'FRC_AB_Testing' ) ) {
			echo '<div class="wrap"><p>' . esc_html__( 'A/B Testing requires a Pro license.', 'flexi-revive-cart' ) . '</p></div>';
			return;
		}

		$ab_testing = new FRC_AB_Testing();
		$ab_testing->check_for_winners();
		$results = $ab_testing->get_results();

		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'A/B Test Results', 'flexi-revive-cart' ); ?></h1>

			<?php if ( empty( $results ) ) : ?>
				<p><?php esc_html_e( 'No A/B tests have been run yet.', 'flexi-revive-cart' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Test Name', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'Type', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'Status', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'Winner', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'Variant A (Sent/Opened/Clicked/Recovered)', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'Variant B (Sent/Opened/Clicked/Recovered)', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'A Recovery Rate', 'flexi-revive-cart' ); ?></th>
						<th><?php esc_html_e( 'B Recovery Rate', 'flexi-revive-cart' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $test ) : ?>
					<?php
					$rate_a  = $test->variant_a_sent > 0 ? round( ( $test->variant_a_recovered / $test->variant_a_sent ) * 100, 1 ) : 0;
					$rate_b  = $test->variant_b_sent > 0 ? round( ( $test->variant_b_recovered / $test->variant_b_sent ) * 100, 1 ) : 0;
					$winner_class_a = $test->winner === 'a' ? 'frc-winner' : '';
					$winner_class_b = $test->winner === 'b' ? 'frc-winner' : '';
					?>
					<tr>
						<td><?php echo esc_html( $test->test_name ); ?></td>
						<td><?php echo esc_html( $test->test_type ); ?></td>
						<td><?php echo esc_html( $test->status ); ?></td>
						<td><?php echo $test->winner ? '<strong>' . esc_html( strtoupper( $test->winner ) ) . '</strong>' : '–'; ?></td>
						<td class="<?php echo esc_attr( $winner_class_a ); ?>"><?php echo esc_html( "{$test->variant_a_sent}/{$test->variant_a_opened}/{$test->variant_a_clicked}/{$test->variant_a_recovered}" ); ?></td>
						<td class="<?php echo esc_attr( $winner_class_b ); ?>"><?php echo esc_html( "{$test->variant_b_sent}/{$test->variant_b_opened}/{$test->variant_b_clicked}/{$test->variant_b_recovered}" ); ?></td>
						<td class="<?php echo esc_attr( $winner_class_a ); ?>"><?php echo esc_html( $rate_a . '%' ); ?></td>
						<td class="<?php echo esc_attr( $winner_class_b ); ?>"><?php echo esc_html( $rate_b . '%' ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
