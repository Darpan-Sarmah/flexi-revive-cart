<?php
/**
 * Guest email capture popup template. (Pro)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message     = esc_html( get_option( 'frc_popup_message', __( 'Wait! Don\'t leave your cart behind.', 'flexi-revive-cart-pro' ) ) );
$button_text = esc_html( get_option( 'frc_popup_button_text', __( 'Save My Cart', 'flexi-revive-cart-pro' ) ) );
?>
<div id="frc-guest-capture-overlay" class="frc-popup-overlay" role="dialog" aria-modal="true" aria-labelledby="frc-gc-title" style="display:none;">
	<div class="frc-popup-modal">
		<button class="frc-popup-close" aria-label="<?php esc_attr_e( 'Close', 'flexi-revive-cart-pro' ); ?>">&times;</button>
		<div class="frc-popup-icon">🛒</div>
		<h2 id="frc-gc-title"><?php echo esc_html( $message ); ?></h2>
		<p><?php esc_html_e( 'Enter your email and we\'ll save your cart so you can complete your purchase later.', 'flexi-revive-cart-pro' ); ?></p>
		<form id="frc-guest-capture-form" class="frc-popup-form" novalidate>
			<input type="email" id="frc-gc-email" name="email" class="frc-popup-email-input"
				placeholder="<?php esc_attr_e( 'your@email.com', 'flexi-revive-cart-pro' ); ?>" required autocomplete="email" />
			<button type="submit" class="frc-popup-submit-btn"><?php echo esc_html( $button_text ); ?></button>
		</form>
		<div id="frc-gc-message" class="frc-popup-message" aria-live="polite"></div>
		<button class="frc-popup-dismiss"><?php esc_html_e( 'No thanks, I don\'t want to save my cart.', 'flexi-revive-cart-pro' ); ?></button>
	</div>
</div>
