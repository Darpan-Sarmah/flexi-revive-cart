<?php
/**
 * Cart restoration landing page template.
 *
 * This template is shown briefly while the cart is being restored.
 * In practice, the plugin redirects to checkout immediately, so this is a fallback.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="woocommerce" style="text-align:center;padding:60px 20px;">
	<div class="entry-content">
		<div style="font-size:64px;margin-bottom:20px;">🛒</div>
		<h1><?php esc_html_e( 'Restoring your cart…', 'flexi-revive-cart' ); ?></h1>
		<p><?php esc_html_e( 'Please wait while we restore your saved cart and redirect you to checkout.', 'flexi-revive-cart' ); ?></p>
		<p>
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button wc-forward">
				<?php esc_html_e( 'Continue to Checkout', 'flexi-revive-cart' ); ?>
			</a>
		</p>
	</div>
</div>
<?php
get_footer();
