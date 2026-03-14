<?php
/**
 * WhatsApp bulk campaigns admin page. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Admin_WhatsApp
 */
class FRC_Pro_Admin_WhatsApp {

	/**
	 * Constructor – register AJAX handler.
	 */
	public function __construct() {
		add_action( 'wp_ajax_frc_send_whatsapp_bulk', array( $this, 'ajax_send_bulk' ) );
	}

	/**
	 * Render the WhatsApp campaigns admin page.
	 */
	public function render() {
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'WhatsApp Bulk Campaigns', 'flexi-revive-cart-pro' ); ?></h1>

			<?php if ( ! get_option( 'frc_enable_whatsapp', '0' ) ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'WhatsApp is not enabled.', 'flexi-revive-cart-pro' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-settings&tab=frc_whatsapp' ) ); ?>">
						<?php esc_html_e( 'Configure WhatsApp →', 'flexi-revive-cart-pro' ); ?>
					</a>
				</p>
			</div>
			<?php endif; ?>

			<div class="postbox" style="max-width:800px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Send New Bulk Campaign', 'flexi-revive-cart-pro' ); ?></h2>
				</div>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th><label for="frc-wa-campaign-name"><?php esc_html_e( 'Campaign Name', 'flexi-revive-cart-pro' ); ?></label></th>
							<td><input type="text" id="frc-wa-campaign-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. March Recovery', 'flexi-revive-cart-pro' ); ?>" /></td>
						</tr>
						<tr>
							<th><label for="frc-wa-message"><?php esc_html_e( 'Message Template', 'flexi-revive-cart-pro' ); ?></label></th>
							<td>
								<textarea id="frc-wa-message" class="large-text" rows="4"><?php echo esc_textarea( get_option( 'frc_whatsapp_template_bulk', __( 'Hi {user_name}, your cart at {store_name} is waiting! Complete your purchase: {cart_link}', 'flexi-revive-cart-pro' ) ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Variables: {user_name}, {cart_total}, {cart_link}, {store_name}, {discount_code}, {discount_amount}', 'flexi-revive-cart-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="frc-wa-delay"><?php esc_html_e( 'Minimum Abandonment Age', 'flexi-revive-cart-pro' ); ?></label></th>
							<td><input type="number" id="frc-wa-delay" class="small-text" value="1" min="0" max="720" /> <?php esc_html_e( 'hours (0 = all)', 'flexi-revive-cart-pro' ); ?></td>
						</tr>
					</table>
					<p>
						<button type="button" id="frc-send-wa-bulk" class="button button-primary">
							<?php esc_html_e( 'Send Bulk Campaign', 'flexi-revive-cart-pro' ); ?>
						</button>
						<span id="frc-wa-bulk-result" style="margin-left:12px;"></span>
					</p>
				</div>
			</div>

			<h2><?php esc_html_e( 'Campaign History', 'flexi-revive-cart-pro' ); ?></h2>
			<?php $this->render_campaigns_table(); ?>
		</div>

		<script>
		(function($) {
			$('#frc-send-wa-bulk').on('click', function() {
				var name    = $('#frc-wa-campaign-name').val().trim();
				var message = $('#frc-wa-message').val().trim();
				var delay   = $('#frc-wa-delay').val();
				var $result = $('#frc-wa-bulk-result');

				if ( ! name ) { alert('<?php echo esc_js( __( 'Enter a campaign name.', 'flexi-revive-cart-pro' ) ); ?>'); return; }
				if ( ! message ) { alert('<?php echo esc_js( __( 'Enter a message template.', 'flexi-revive-cart-pro' ) ); ?>'); return; }

				$(this).prop('disabled', true);
				$result.text('<?php echo esc_js( __( 'Sending…', 'flexi-revive-cart-pro' ) ); ?>');

				$.post(typeof frcAdmin !== 'undefined' ? frcAdmin.ajaxUrl : ajaxurl, {
					action:        'frc_send_whatsapp_bulk',
					nonce:         typeof frcAdmin !== 'undefined' ? frcAdmin.nonce : '',
					campaign_name: name,
					message:       message,
					delay_hours:   delay,
				}, function(response) {
					if ( response.success ) {
						$result.css('color', '#46b450').text(response.data.message);
						setTimeout(function() { location.reload(); }, 2000);
					} else {
						$result.css('color', '#d63638').text(response.data.message);
					}
				}).fail(function() {
					$result.css('color', '#d63638').text('<?php echo esc_js( __( 'Request failed.', 'flexi-revive-cart-pro' ) ); ?>');
				}).always(function() {
					$('#frc-send-wa-bulk').prop('disabled', false);
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * AJAX handler for sending a bulk WhatsApp campaign.
	 */
	public function ajax_send_bulk() {
		check_ajax_referer( 'frc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-revive-cart-pro' ) ) );
			return;
		}

		$name    = isset( $_POST['campaign_name'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_name'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$delay   = isset( $_POST['delay_hours'] ) ? absint( wp_unslash( $_POST['delay_hours'] ) ) : 0;

		if ( empty( $name ) || empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Campaign name and message are required.', 'flexi-revive-cart-pro' ) ) );
			return;
		}

		$wa     = new FRC_Pro_WhatsApp();
		$result = $wa->send_bulk_campaign( $name, $message, $delay );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: sent count, 2: failed count */
				__( 'Campaign completed. Sent: %1$d, Failed: %2$d', 'flexi-revive-cart-pro' ),
				$result['sent'],
				$result['failed']
			),
		) );
	}

	/**
	 * Render the campaigns history table.
	 */
	private function render_campaigns_table() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaigns = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}frc_whatsapp_campaigns ORDER BY sent_at DESC LIMIT 50" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( empty( $campaigns ) ) {
			echo '<p>' . esc_html__( 'No campaigns yet.', 'flexi-revive-cart-pro' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Campaign', 'flexi-revive-cart-pro' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent At', 'flexi-revive-cart-pro' ) . '</th>';
		echo '<th>' . esc_html__( 'Recipients', 'flexi-revive-cart-pro' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent', 'flexi-revive-cart-pro' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'flexi-revive-cart-pro' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $campaigns as $c ) {
			echo '<tr>';
			echo '<td>' . esc_html( $c->campaign_name ) . '</td>';
			echo '<td>' . esc_html( $c->sent_at ) . '</td>';
			echo '<td>' . esc_html( $c->recipients ) . '</td>';
			echo '<td>' . esc_html( $c->sent ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $c->status ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
