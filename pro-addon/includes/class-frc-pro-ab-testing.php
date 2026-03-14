<?php
/**
 * A/B testing engine. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_AB_Testing
 */
class FRC_Pro_AB_Testing {

	/**
	 * Apply A/B test variant to the email subject.
	 *
	 * Hooked to: frc_email_subject
	 *
	 * @param string $subject     Current subject.
	 * @param object $cart        Cart row.
	 * @param int    $stage       Reminder stage.
	 * @param string $template_id Template ID.
	 * @return string
	 */
	public function apply_ab_test( $subject, $cart, $stage, $template_id ) {
		$active_test = $this->get_active_test( 'subject_line', $stage );
		if ( ! $active_test ) {
			return $subject;
		}

		$variant = $this->assign_variant( (int) $cart->id );
		$new_subject = 'a' === $variant ? $active_test->variant_a : $active_test->variant_b;
		$this->record_send( $active_test->id, $variant );

		return $new_subject ?: $subject;
	}

	/**
	 * Assign a variant deterministically based on cart ID.
	 *
	 * @param int $cart_id Cart ID.
	 * @return string 'a' or 'b'.
	 */
	public function assign_variant( $cart_id ) {
		return ( $cart_id % 2 === 0 ) ? 'a' : 'b';
	}

	/**
	 * Fetch the active A/B test for a type and stage.
	 *
	 * @param string $type  Test type.
	 * @param int    $stage Email stage.
	 * @return object|null
	 */
	private function get_active_test( $type, $stage ) {
		global $wpdb;
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}frc_ab_tests
				 WHERE test_type = %s AND status = 'active'
				 AND test_name LIKE %s
				 LIMIT 1",
				$type,
				$wpdb->esc_like( 'stage_' . $stage ) . '%'
			)
		);
	}

	/**
	 * Increment the sent counter for a variant.
	 *
	 * @param int    $test_id Test ID.
	 * @param string $variant 'a' or 'b'.
	 */
	private function record_send( $test_id, $variant ) {
		global $wpdb;
		$field = 'a' === $variant ? 'variant_a_sent' : 'variant_b_sent';
		$allowed = array( 'variant_a_sent', 'variant_b_sent' );
		if ( ! in_array( $field, $allowed, true ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}frc_ab_tests SET `{$field}` = `{$field}` + 1 WHERE id = %d", $test_id ) );
	}

	/**
	 * Record an open event for A/B testing.
	 *
	 * @param int    $test_id Test ID.
	 * @param string $variant 'a' or 'b'.
	 */
	public function record_open( $test_id, $variant ) {
		global $wpdb;
		$field = 'a' === $variant ? 'variant_a_opened' : 'variant_b_opened';
		$allowed = array( 'variant_a_opened', 'variant_b_opened' );
		if ( ! in_array( $field, $allowed, true ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}frc_ab_tests SET `{$field}` = `{$field}` + 1 WHERE id = %d", $test_id ) );
	}

	/**
	 * Get all A/B test results.
	 *
	 * @return array
	 */
	public function get_results() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_ab_tests ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Check tests for statistical significance and auto-declare winners.
	 */
	public function check_for_winners() {
		global $wpdb;
		$tests = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$wpdb->prefix}frc_ab_tests WHERE status = 'active'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		foreach ( $tests as $test ) {
			if ( $test->variant_a_sent < 100 || $test->variant_b_sent < 100 ) {
				continue;
			}

			$rate_a = $test->variant_a_sent > 0 ? $test->variant_a_recovered / $test->variant_a_sent : 0;
			$rate_b = $test->variant_b_sent > 0 ? $test->variant_b_recovered / $test->variant_b_sent : 0;

			$winner = $rate_a >= $rate_b ? 'a' : 'b';

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'frc_ab_tests',
				array(
					'status'   => 'completed',
					'winner'   => $winner,
					'ended_at' => current_time( 'mysql' ),
				),
				array( 'id' => (int) $test->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
	}
}
