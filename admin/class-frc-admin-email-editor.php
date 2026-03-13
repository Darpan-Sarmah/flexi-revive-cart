<?php
/**
 * Email template editor (Pro).
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
		if ( ! FRC_PRO_ACTIVE ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Email Editor requires a Pro license.', 'flexi-revive-cart' ) . '</p></div>';
			return;
		}

		$templates    = FRC_Email_Templates::get_templates();
		$active_id    = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : 'reminder-1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_id    = array_key_exists( $active_id, $templates ) ? $active_id : 'reminder-1';
		$saved_key    = 'frc_email_template_' . $active_id;

		// Handle save.
		if ( isset( $_POST['frc_save_template'] ) && check_admin_referer( 'frc_save_email_template' ) ) {
			$content = isset( $_POST['frc_template_content'] ) ? wp_kses_post( wp_unslash( $_POST['frc_template_content'] ) ) : '';
			update_option( $saved_key, $content );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Template saved!', 'flexi-revive-cart' ) . '</p></div>';
		}

		$current_content = get_option( $saved_key, '' );
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Email Template Editor', 'flexi-revive-cart' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $templates as $id => $tmpl ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-email-editor&template=' . $id ) ); ?>" class="nav-tab <?php echo ( $id === $active_id ) ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tmpl['name'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<form method="post">
				<?php wp_nonce_field( 'frc_save_email_template' ); ?>

				<div class="frc-editor-row">
					<div class="frc-editor-main">
						<h3><?php echo esc_html( $templates[ $active_id ]['name'] ); ?></h3>

						<div class="frc-var-buttons">
							<strong><?php esc_html_e( 'Insert Variable:', 'flexi-revive-cart' ); ?></strong>
							<?php
							$vars = array( 'user_name', 'cart_items', 'cart_total', 'recovery_link', 'discount_code', 'discount_amount', 'store_name', 'abandoned_time', 'unsubscribe_link' );
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
					</div>
				</div>

				<?php submit_button( __( 'Save Template', 'flexi-revive-cart' ), 'primary', 'frc_save_template' ); ?>
			</form>
		</div>
		<?php
	}
}
