<?php
/**
 * Email template definitions.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Email_Templates
 *
 * Provides template metadata and variable replacement for email templates.
 */
class FRC_Email_Templates {

	/**
	 * Return all available template definitions.
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'reminder-1' => array(
				'id'       => 'reminder-1',
				'name'     => __( 'Friendly Reminder', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-1.php',
				'stage'    => 1,
			),
			'reminder-2' => array(
				'id'       => 'reminder-2',
				'name'     => __( 'Urgency Reminder', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-2.php',
				'stage'    => 2,
			),
			'reminder-3' => array(
				'id'       => 'reminder-3',
				'name'     => __( 'Incentive / Discount', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-3.php',
				'stage'    => 3,
			),
		);
	}

	/**
	 * Render a template by replacing placeholder variables.
	 *
	 * @param string $template_id Template ID key.
	 * @param array  $vars        Associative array of variable replacements.
	 * @return string Rendered HTML.
	 */
	public static function render( $template_id, $vars = array() ) {
		$templates = self::get_templates();
		if ( ! isset( $templates[ $template_id ] ) ) {
			return '';
		}

		$template_file = FRC_PLUGIN_DIR . 'templates/' . $templates[ $template_id ]['file'];

		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		ob_start();
		// Make variables available within template scope.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $template_file;
		$html = ob_get_clean();

		// Replace template variables.
		$placeholders = array_keys( $vars );
		$values       = array_values( $vars );

		$search  = array_map(
			function ( $k ) {
				return '{' . $k . '}';
			},
			$placeholders
		);

		return str_replace( $search, $values, $html );
	}

	/**
	 * Replace dynamic placeholders in a string.
	 *
	 * @param string $content String with {placeholder} tags.
	 * @param array  $vars    Associative key => value array.
	 * @return string
	 */
	public static function replace_vars( $content, $vars ) {
		foreach ( $vars as $key => $value ) {
			$content = str_replace( '{' . $key . '}', $value, $content );
		}
		return $content;
	}

	/**
	 * Build the replacement variables array for a given cart row.
	 *
	 * @param object $cart          Database cart row.
	 * @param int    $log_id        Email log ID (for tracking links).
	 * @param string $discount_code Optional discount code.
	 * @param float  $discount_pct  Optional discount percentage.
	 * @return array
	 */
	public static function build_vars( $cart, $log_id = 0, $discount_code = '', $discount_pct = 0 ) {
		// User name.
		$user_name = '';
		if ( $cart->user_id ) {
			$user = get_userdata( (int) $cart->user_id );
			if ( $user ) {
				$user_name = $user->first_name ? $user->first_name : $user->display_name;
			}
		}
		if ( ! $user_name ) {
			$parts     = explode( '@', $cart->user_email );
			$user_name = ucfirst( $parts[0] );
		}

		// Recovery link with tracking.
		$recovery_url = FRC_Helpers::get_recovery_url( $cart->recovery_token );
		if ( $log_id ) {
			$recovery_url = add_query_arg(
				array(
					'frc_channel' => 'email',
					'frc_lid'     => $log_id,
				),
				$recovery_url
			);
		}

		// Pixel / open-tracking URL.
		$pixel_url = add_query_arg(
			array(
				'action' => 'frc_track_open',
				'lid'    => $log_id,
			),
			admin_url( 'admin-ajax.php' )
		);

		// Unsubscribe link.
		$unsubscribe_url = add_query_arg(
			array(
				'frc_optout' => rawurlencode( $cart->recovery_token ),
			),
			home_url( '/' )
		);

		// Cart items HTML.
		$cart_items_html = FRC_Helpers::build_cart_items_html( FRC_Helpers::unserialize_cart( $cart->cart_contents ) );

		return array(
			'user_name'        => esc_html( $user_name ),
			'cart_items'       => $cart_items_html,
			'cart_total'       => wp_kses_post( FRC_Helpers::format_currency( $cart->cart_total, $cart->currency ) ),
			'recovery_link'    => esc_url( $recovery_url ),
			'discount_code'    => esc_html( $discount_code ),
			'discount_amount'  => $discount_pct ? esc_html( $discount_pct . '%' ) : '',
			'store_name'       => esc_html( get_bloginfo( 'name' ) ),
			'abandoned_time'   => esc_html( FRC_Helpers::time_ago( $cart->abandoned_at ) ),
			'unsubscribe_link' => esc_url( $unsubscribe_url ),
			'tracking_pixel'   => '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" style="display:none;" alt="" />',
		);
	}
}
