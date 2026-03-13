<?php
/**
 * Email template editor (Free & Pro).
 *
 * Free: Edit email template body for all reminder stages with a WYSIWYG editor
 * and multi-language support (English, Spanish, French, German).
 * Pro: Additional A/B testing and conditional logic features.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_Email_Editor
 */
class FRC_Admin_Email_Editor {

	/**
	 * Render the email editor page.
	 */
	public function render() {
		$templates    = FRC_Email_Templates::get_templates();
		$languages    = FRC_Email_Templates::get_supported_languages();
		$active_id    = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : 'reminder-1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_id    = array_key_exists( $active_id, $templates ) ? $active_id : 'reminder-1';
		$active_lang  = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : 'en'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_lang  = array_key_exists( $active_lang, $languages ) ? $active_lang : 'en';
		$saved_key    = 'frc_email_template_' . $active_id . '_' . $active_lang;

		// Handle save.
		if ( isset( $_POST['frc_save_template'] ) && check_admin_referer( 'frc_save_email_template' ) ) {
			$content = isset( $_POST['frc_template_content'] ) ? wp_kses_post( wp_unslash( $_POST['frc_template_content'] ) ) : '';
			update_option( $saved_key, $content );
			// Also update the legacy generic key for English (backwards compat).
			if ( 'en' === $active_lang ) {
				update_option( 'frc_email_template_' . $active_id, $content );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Template saved!', 'flexi-revive-cart' ) . '</p></div>';
		}

		// Handle reset to default.
		if ( isset( $_POST['frc_reset_template'] ) && check_admin_referer( 'frc_save_email_template' ) ) {
			delete_option( $saved_key );
			if ( 'en' === $active_lang ) {
				delete_option( 'frc_email_template_' . $active_id );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Template reset to default!', 'flexi-revive-cart' ) . '</p></div>';
		}

		$current_content = get_option( $saved_key, '' );
		// If no saved content, load the pre-loaded default for this language.
		if ( '' === $current_content ) {
			$current_content = FRC_Email_Templates::get_default_template_content( $active_id, $active_lang );
		}
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Email Template Editor', 'flexi-revive-cart' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Customize your abandoned cart email templates. Changes are saved per language. Use the variable buttons to insert dynamic content.', 'flexi-revive-cart' ); ?>
			</p>

			<?php if ( FRC_PRO_ACTIVE ) : ?>
			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Pro:', 'flexi-revive-cart' ); ?></strong>
					<?php esc_html_e( 'A/B testing and conditional logic (e.g., show discount only for carts over $100) are available via the A/B Results page.', 'flexi-revive-cart' ); ?>
				</p>
			</div>
			<?php endif; ?>

			<!-- Template Tabs -->
			<nav class="nav-tab-wrapper">
				<?php foreach ( $templates as $id => $tmpl ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-email-editor&template=' . $id . '&lang=' . $active_lang ) ); ?>" class="nav-tab <?php echo ( $id === $active_id ) ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tmpl['name'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<form method="post">
				<?php wp_nonce_field( 'frc_save_email_template' ); ?>

				<div class="frc-editor-row" style="display:flex;gap:20px;margin-top:20px;">
					<div class="frc-editor-main" style="flex:1;">

						<!-- Language Selector -->
						<div style="margin-bottom:16px;">
							<label for="frc-lang-switcher"><strong><?php esc_html_e( 'Language:', 'flexi-revive-cart' ); ?></strong></label>
							<select id="frc-lang-switcher">
								<?php foreach ( $languages as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $active_lang ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<script>
							document.getElementById('frc-lang-switcher').addEventListener('change', function() {
								var url = new URL(window.location.href);
								url.searchParams.set('lang', this.value);
								window.location.href = url.toString();
							});
							</script>
						</div>

						<h3>
							<?php echo esc_html( $templates[ $active_id ]['name'] ); ?>
							&mdash;
							<?php echo esc_html( $languages[ $active_lang ] ); ?>
						</h3>

						<!-- Variable Insertion Buttons -->
						<div class="frc-var-buttons" style="margin-bottom:12px;">
							<strong><?php esc_html_e( 'Insert Variable:', 'flexi-revive-cart' ); ?></strong>
							<?php
							$vars = array( 'user_name', 'cart_items', 'cart_total', 'recovery_link', 'cart_link', 'discount_code', 'discount_amount', 'store_name', 'abandoned_time', 'unsubscribe_link' );
							foreach ( $vars as $var ) {
								echo '<button type="button" class="button button-small frc-insert-var" data-var="{' . esc_attr( $var ) . '}">{' . esc_html( $var ) . '}</button> ';
							}
							?>
						</div>

						<?php
						wp_editor(
							$current_content,
							'frc_template_content',
							array(
								'textarea_name' => 'frc_template_content',
								'media_buttons' => false,
								'teeny'         => false,
								'textarea_rows' => 25,
							)
						);
						?>

						<div style="margin-top:12px;">
							<?php submit_button( __( 'Save Template', 'flexi-revive-cart' ), 'primary', 'frc_save_template', false ); ?>
							&nbsp;
							<?php submit_button( __( 'Reset to Default', 'flexi-revive-cart' ), 'secondary', 'frc_reset_template', false ); ?>
						</div>
					</div>

					<!-- Sidebar: Variable Reference -->
					<div class="frc-editor-sidebar" style="width:260px;">
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Available Variables', 'flexi-revive-cart' ); ?></h2></div>
							<div class="inside">
								<table class="widefat striped" style="font-size:12px;">
									<tbody>
										<tr><td><code>{user_name}</code></td><td><?php esc_html_e( 'Customer first name', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_items}</code></td><td><?php esc_html_e( 'HTML list of cart items', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_total}</code></td><td><?php esc_html_e( 'Cart total (formatted)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{recovery_link}</code></td><td><?php esc_html_e( 'Cart recovery URL', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_link}</code></td><td><?php esc_html_e( 'Same as recovery_link', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{discount_code}</code></td><td><?php esc_html_e( 'Generated coupon code', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{discount_amount}</code></td><td><?php esc_html_e( 'Discount percentage (e.g. 10%)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{store_name}</code></td><td><?php esc_html_e( 'Your store name', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{abandoned_time}</code></td><td><?php esc_html_e( 'Time since abandonment', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{unsubscribe_link}</code></td><td><?php esc_html_e( 'Opt-out URL', 'flexi-revive-cart' ); ?></td></tr>
									</tbody>
								</table>
							</div>
						</div>

						<?php if ( FRC_PRO_ACTIVE ) : ?>
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Pro: Conditional Logic', 'flexi-revive-cart' ); ?></h2></div>
							<div class="inside">
								<p style="font-size:12px;"><?php esc_html_e( 'Show discount block only for high-value carts by using A/B test variants. Configure conditions in A/B Results.', 'flexi-revive-cart' ); ?></p>
							</div>
						</div>
						<?php else : ?>
						<div class="postbox">
							<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></h2></div>
							<div class="inside">
								<p style="font-size:12px;"><?php esc_html_e( 'Pro unlocks A/B testing, conditional logic, SMS/WhatsApp messages, and advanced analytics.', 'flexi-revive-cart' ); ?></p>
								<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></a>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}
