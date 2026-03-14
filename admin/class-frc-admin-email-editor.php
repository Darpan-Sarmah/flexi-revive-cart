<?php
/**
 * Email template editor (Free & Pro).
 *
 * Free: Edit only the Friendly Reminder template (reminder-1) subject and body.
 *       Urgency and Incentive templates are hidden with an upgrade notice.
 * Pro:  Edit all 3 templates (friendly, urgency, incentive/discount)
 *       with full subject + body editing and multi-language support.
 *
 * Subjects are managed here (unified with template bodies) and support
 * dynamic placeholders such as {user_name}, {store_name}, {discount_amount}, etc.
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

		// In Free version, force the active template to friendly reminder only.
		if ( ! FRC_PRO_ACTIVE && 'reminder-1' !== $active_id ) {
			$active_id = 'reminder-1';
		}

		$saved_key    = 'frc_email_template_' . $active_id . '_' . $active_lang;
		$subject_key  = 'frc_email_subject_' . $active_id . '_' . $active_lang;

		// Handle save.
		if ( isset( $_POST['frc_save_template'] ) && check_admin_referer( 'frc_save_email_template' ) ) {
			$content = isset( $_POST['frc_template_content'] ) ? wp_kses_post( wp_unslash( $_POST['frc_template_content'] ) ) : '';
			$subject = isset( $_POST['frc_template_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['frc_template_subject'] ) ) : '';

			// In Free version, strip Pro-only placeholders and warn the admin.
			if ( ! FRC_PRO_ACTIVE ) {
				$pro_placeholders = FRC_Email_Templates::get_pro_only_placeholders();
				$found_pro = array();
				foreach ( $pro_placeholders as $pp ) {
					if ( strpos( $content, '{' . $pp . '}' ) !== false || strpos( $subject, '{' . $pp . '}' ) !== false ) {
						$found_pro[] = '{' . $pp . '}';
						$content = str_replace( '{' . $pp . '}', '', $content );
						$subject = str_replace( '{' . $pp . '}', '', $subject );
					}
				}
				if ( ! empty( $found_pro ) ) {
					echo '<div class="notice notice-warning"><p>';
					echo esc_html(
						sprintf(
							/* translators: %s: list of placeholder names */
							__( 'The following Pro-only placeholders were removed from your template: %s. Upgrade to Pro to use these placeholders.', 'flexi-revive-cart' ),
							implode( ', ', $found_pro )
						)
					);
					echo '</p></div>';
				}
			}

			update_option( $saved_key, $content );
			update_option( $subject_key, $subject );
			// Also update the legacy generic key for English (backwards compat).
			if ( 'en' === $active_lang ) {
				update_option( 'frc_email_template_' . $active_id, $content );
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Template saved!', 'flexi-revive-cart' ) . '</p></div>';
		}

		// Handle reset to default.
		if ( isset( $_POST['frc_reset_template'] ) && check_admin_referer( 'frc_save_email_template' ) ) {
			delete_option( $saved_key );
			delete_option( $subject_key );
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

		$current_subject = get_option( $subject_key, '' );
		if ( '' === $current_subject ) {
			$current_subject = FRC_Email_Templates::get_default_subject( $active_id, $active_lang );
		}
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Email Template Editor', 'flexi-revive-cart' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Customize your abandoned cart email templates. Subjects and bodies are managed here and saved per language. Use the variable buttons to insert dynamic placeholders into both subjects and bodies.', 'flexi-revive-cart' ); ?>
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
				<?php foreach ( $templates as $id => $tmpl ) :
					$is_pro_template = ( 'reminder-1' !== $id );
					$is_locked       = ( $is_pro_template && ! FRC_PRO_ACTIVE );
				?>
				<?php if ( ! $is_locked ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-email-editor&template=' . $id . '&lang=' . $active_lang ) ); ?>" class="nav-tab <?php echo ( $id === $active_id ) ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tmpl['name'] ); ?>
				</a>
				<?php else : ?>
				<span class="nav-tab" style="color:#999;cursor:default;" title="<?php esc_attr_e( 'Upgrade to Pro for this template', 'flexi-revive-cart' ); ?>">
					<?php echo esc_html( $tmpl['name'] ); ?> 🔒
				</span>
				<?php endif; ?>
				<?php endforeach; ?>
			</nav>

			<?php if ( ! FRC_PRO_ACTIVE ) : ?>
			<div class="notice notice-warning inline" style="margin-top:10px;">
				<p>
					<?php esc_html_e( 'Urgency and Incentive/Discount templates require a Pro license. Upgrade to Pro to edit all templates, send urgency/discount emails, and use coupon features.', 'flexi-revive-cart' ); ?>
					<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" rel="noopener noreferrer"><strong><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></strong></a>
				</p>
			</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'frc_save_email_template' ); ?>

				<div class="frc-editor-row" style="display:flex;gap:20px;margin-top:20px;">
					<div class="frc-editor-main" style="flex:1;">

						<!-- Template Language Selector (for editing/previewing only) -->
						<div style="margin-bottom:16px;">
							<label for="frc-lang-switcher"><strong><?php esc_html_e( 'Template Language (Editing Only):', 'flexi-revive-cart' ); ?></strong></label>
							<select id="frc-lang-switcher">
								<?php foreach ( $languages as $code => $label ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $active_lang ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<p class="description" style="display:inline;margin-left:8px;"><?php esc_html_e( 'This switcher is for editing/previewing templates only. It does not affect the language of sent emails or admin interface.', 'flexi-revive-cart' ); ?></p>
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

						<!-- Email Subject Field -->
						<div style="margin-bottom:16px;">
							<label for="frc_template_subject"><strong><?php esc_html_e( 'Email Subject:', 'flexi-revive-cart' ); ?></strong></label>
							<input type="text" id="frc_template_subject" name="frc_template_subject"
								value="<?php echo esc_attr( $current_subject ); ?>"
								class="large-text"
								placeholder="<?php esc_attr_e( 'e.g. Hi {user_name}, your cart is waiting!', 'flexi-revive-cart' ); ?>" />
							<p class="description">
								<?php esc_html_e( 'Dynamic placeholders like {user_name}, {store_name}, {discount_code}, {discount_amount}, {cart_total}, {abandoned_time} are supported in subjects.', 'flexi-revive-cart' ); ?>
							</p>
						</div>

						<!-- Variable Insertion Buttons -->
						<div class="frc-var-buttons" style="margin-bottom:12px;">
							<strong><?php esc_html_e( 'Insert Variable:', 'flexi-revive-cart' ); ?></strong>
							<?php
							$pro_only_vars = FRC_Email_Templates::get_pro_only_placeholders();
							$vars = array( 'user_name', 'cart_items', 'cart_total', 'recovery_link', 'cart_link', 'discount_code', 'discount_amount', 'discount_expiry', 'store_name', 'abandoned_time', 'unsubscribe_link', 'cart_expiry', 'low_stock_alert' );
							foreach ( $vars as $var ) {
								$is_pro_var = in_array( $var, $pro_only_vars, true );
								if ( $is_pro_var && ! FRC_PRO_ACTIVE ) {
									// Hide Pro-only placeholder buttons in Free version.
									continue;
								}
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
								<p style="font-size:12px;margin-top:0;">
									<?php esc_html_e( 'These variables work in both subjects and email bodies.', 'flexi-revive-cart' ); ?>
								</p>
								<table class="widefat striped" style="font-size:12px;">
									<tbody>
										<tr><td><code>{user_name}</code></td><td><?php esc_html_e( 'Customer first name', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_items}</code></td><td><?php esc_html_e( 'HTML list of cart items', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_total}</code></td><td><?php esc_html_e( 'Cart total (formatted)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{recovery_link}</code></td><td><?php esc_html_e( 'Cart recovery URL', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_link}</code></td><td><?php esc_html_e( 'Same as recovery_link', 'flexi-revive-cart' ); ?></td></tr>
										<?php if ( FRC_PRO_ACTIVE ) : ?>
										<tr><td><code>{discount_code}</code></td><td><?php esc_html_e( 'Generated coupon code (Pro)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{discount_amount}</code></td><td><?php esc_html_e( 'Discount percentage (Pro)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{discount_expiry}</code></td><td><?php esc_html_e( 'Coupon expiry date (Pro)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{cart_expiry}</code></td><td><?php esc_html_e( 'Cart expiry countdown (Pro)', 'flexi-revive-cart' ); ?></td></tr>
										<tr><td><code>{low_stock_alert}</code></td><td><?php esc_html_e( 'Low stock warning (Pro)', 'flexi-revive-cart' ); ?></td></tr>
										<?php endif; ?>
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
								<p style="font-size:12px;"><?php esc_html_e( 'Pro unlocks urgency/incentive templates, A/B testing, conditional logic, SMS/WhatsApp messages, coupon features, and advanced analytics.', 'flexi-revive-cart' ); ?></p>
								<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></a>
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
