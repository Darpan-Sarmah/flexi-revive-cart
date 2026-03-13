<?php
/**
 * Utility / helper functions.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Helpers
 *
 * Static utility methods used across the plugin.
 */
class FRC_Helpers {

	/**
	 * Generate a cryptographically random recovery token.
	 *
	 * @return string 64-character alphanumeric token.
	 */
	public static function generate_recovery_token() {
		return wp_generate_password( 64, false );
	}

	/**
	 * Get the cart recovery URL for a given token.
	 *
	 * @param string $token Recovery token.
	 * @return string Recovery URL.
	 */
	public static function get_recovery_url( $token ) {
		return add_query_arg( 'frc_recover', rawurlencode( $token ), home_url( '/' ) );
	}

	/**
	 * Format a currency amount.
	 *
	 * @param float  $amount   Amount to format.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function format_currency( $amount, $currency = '' ) {
		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $amount );
		}
		return number_format( (float) $amount, 2 ) . ' ' . esc_html( $currency );
	}

	/**
	 * Return a human-readable "time ago" string.
	 *
	 * @param string $datetime_str A datetime string (MySQL format).
	 * @return string
	 */
	public static function time_ago( $datetime_str ) {
		$timestamp = strtotime( $datetime_str );
		if ( ! $timestamp ) {
			return '';
		}
		$diff = time() - $timestamp;

		if ( $diff < 60 ) {
			return sprintf(
				/* translators: %d: number of seconds */
				_n( '%d second ago', '%d seconds ago', $diff, 'flexi-revive-cart' ),
				$diff
			);
		}
		if ( $diff < 3600 ) {
			$mins = (int) round( $diff / 60 );
			return sprintf(
				/* translators: %d: number of minutes */
				_n( '%d minute ago', '%d minutes ago', $mins, 'flexi-revive-cart' ),
				$mins
			);
		}
		if ( $diff < 86400 ) {
			$hours = (int) round( $diff / 3600 );
			return sprintf(
				/* translators: %d: number of hours */
				_n( '%d hour ago', '%d hours ago', $hours, 'flexi-revive-cart' ),
				$hours
			);
		}

		$days = (int) round( $diff / 86400 );
		return sprintf(
			/* translators: %d: number of days */
			_n( '%d day ago', '%d days ago', $days, 'flexi-revive-cart' ),
			$days
		);
	}

	/**
	 * Get a cart row from the database by recovery token.
	 *
	 * @param string $token Recovery token.
	 * @return object|null Database row or null.
	 */
	public static function get_cart_by_token( $token ) {
		global $wpdb;
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE recovery_token = %s LIMIT 1",
				$token
			)
		);
	}

	/**
	 * Get a cart row from the database by ID.
	 *
	 * @param int $cart_id Cart ID.
	 * @return object|null Database row or null.
	 */
	public static function get_cart_by_id( $cart_id ) {
		global $wpdb;
		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE id = %d LIMIT 1",
				(int) $cart_id
			)
		);
	}

	/**
	 * Safely unserialize cart contents.
	 *
	 * @param string $data Serialized or JSON cart data.
	 * @return array
	 */
	public static function unserialize_cart( $data ) {
		if ( empty( $data ) ) {
			return array();
		}
		// Try JSON first.
		$decoded = json_decode( $data, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
		// Fall back to PHP unserialize.
		if ( is_serialized( $data ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			return (array) unserialize( $data, array( 'allowed_classes' => false ) );
		}
		return array();
	}

	/**
	 * Safely serialize cart contents.
	 *
	 * @param array $cart Cart data array.
	 * @return string JSON-encoded string.
	 */
	public static function serialize_cart( $cart ) {
		return wp_json_encode( $cart );
	}

	/**
	 * Get the current visitor's IP address.
	 *
	 * @return string
	 */
	public static function get_ip_address() {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				$ip = explode( ',', $ip );
				$ip = trim( reset( $ip ) );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}

	/**
	 * Build an HTML cart items table for use in email templates.
	 *
	 * @param array $cart_items Unserialized cart items array.
	 * @return string HTML table string.
	 */
	public static function build_cart_items_html( $cart_items ) {
		if ( empty( $cart_items ) || ! is_array( $cart_items ) ) {
			return '';
		}

		$html = '<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
		$html .= '<thead><tr>';
		$html .= '<th style="text-align:left;padding:8px;border-bottom:2px solid #eee;">' . esc_html__( 'Product', 'flexi-revive-cart' ) . '</th>';
		$html .= '<th style="text-align:center;padding:8px;border-bottom:2px solid #eee;">' . esc_html__( 'Qty', 'flexi-revive-cart' ) . '</th>';
		$html .= '<th style="text-align:right;padding:8px;border-bottom:2px solid #eee;">' . esc_html__( 'Price', 'flexi-revive-cart' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $cart_items as $item ) {
			if ( empty( $item['product_id'] ) ) {
				continue;
			}

			$product_id   = (int) $item['product_id'];
			$product      = wc_get_product( $product_id );
			$product_name = ! empty( $item['product_name'] ) ? $item['product_name'] : ( $product ? $product->get_name() : '' );
			$quantity     = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
			$price        = isset( $item['line_total'] ) ? (float) $item['line_total'] : 0.0;

			// Product thumbnail.
			$thumb = '';
			if ( $product ) {
				$image_id = $product->get_image_id();
				if ( $image_id ) {
					$image_src = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					if ( $image_src ) {
						$thumb = '<img src="' . esc_url( $image_src ) . '" width="50" height="50" alt="' . esc_attr( $product_name ) . '" style="vertical-align:middle;margin-right:8px;" />';
					}
				}
			}

			$html .= '<tr>';
			$html .= '<td style="padding:8px;border-bottom:1px solid #eee;">' . $thumb . esc_html( $product_name ) . '</td>';
			$html .= '<td style="text-align:center;padding:8px;border-bottom:1px solid #eee;">' . esc_html( $quantity ) . '</td>';
			$html .= '<td style="text-align:right;padding:8px;border-bottom:1px solid #eee;">' . wp_kses_post( wc_price( $price ) ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';
		return $html;
	}
}
