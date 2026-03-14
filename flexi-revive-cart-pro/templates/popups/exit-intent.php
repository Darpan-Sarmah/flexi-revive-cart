<?php
/**
 * Exit-intent popup template. (Pro)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$discount_pct = get_option( 'frc_enable_auto_discounts', '0' ) ? (int) get_option( 'frc_discount_percentage', 10 ) : 0;
?>
<div id="frc-exit-intent-overlay" class="frc-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="frc-ei-title" style="display:none;">
	<div class="frc-popup-modal">
		<button class="frc-popup-close" aria-label="<?php esc_attr_e( 'Close', 'flexi-revive-cart-pro' ); ?>">&times;</button>
		<div class="frc-popup-icon">🚪</div>
		<h2 id="frc-ei-title">
			<?php
			if ( $discount_pct > 0 ) {
				echo esc_html( sprintf(
					/* translators: %d: discount percentage */
					__( 'Wait! Get %d%% off your cart!', 'flexi-revive-cart-pro' ),
					$discount_pct
				) );
			} else {
				esc_html_e( 'Wait – don\'t go just yet!', 'flexi-revive-cart-pro' );
			}
			?>
		</h2>
		<p>
			<?php
			if ( $discount_pct > 0 ) {
				echo esc_html( sprintf(
					/* translators: %d: discount percentage */
					__( 'Leave your email and we\'ll send you a %d%% discount code for your cart.', 'flexi-revive-cart-pro' ),
					$discount_pct
				) );
			} else {
				esc_html_e( 'Leave your email and we\'ll save your cart so you can complete your purchase when you\'re ready.', 'flexi-revive-cart-pro' );
			}
			?>
		</p>
		<form id="frc-exit-intent-form" class="frc-popup-form" novalidate>
			<input type="email" id="frc-ei-email" name="email" class="frc-popup-email-input"
				placeholder="<?php esc_attr_e( 'your@email.com', 'flexi-revive-cart-pro' ); ?>" required autocomplete="email" />
			<button type="submit" class="frc-popup-submit-btn">
				<?php
				if ( $discount_pct > 0 ) {
					echo esc_html( sprintf( __( 'Get %d%% Off', 'flexi-revive-cart-pro' ), $discount_pct ) );
				} else {
					esc_html_e( 'Save My Cart', 'flexi-revive-cart-pro' );
				}
				?>
			</button>
		</form>
		<div id="frc-ei-message" class="frc-popup-message" aria-live="polite"></div>
		<button class="frc-popup-dismiss"><?php esc_html_e( 'No thanks, I\'ll pay full price.', 'flexi-revive-cart-pro' ); ?></button>
	</div>
</div>
