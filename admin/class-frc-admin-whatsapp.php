<?php
/**
 * WhatsApp bulk campaigns admin page. (Pro feature)
 *
 * Allows admins to:
 * - Create and send bulk WhatsApp campaigns to all abandoned-cart users.
 * - View campaign history with delivery statistics.
 * - Customize the message template with dynamic variables.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_WhatsApp
 */
class FRC_Admin_WhatsApp {

	/**
	 * Render the WhatsApp campaigns admin page.
	 */
	public function render() {
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'WhatsApp Bulk Campaigns', 'flexi-revive-cart' ); ?></h1>

			<?php if ( ! get_option( 'frc_enable_whatsapp', '0' ) ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'WhatsApp messaging is not enabled.', 'flexi-revive-cart' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=frc-settings&tab=whatsapp' ) ); ?>">
						<?php esc_html_e( 'Configure WhatsApp settings →', 'flexi-revive-cart' ); ?>
					</a>
				</p>
			</div>
			<?php endif; ?>

			<!-- Send New Campaign -->
			<div class="postbox" style="max-width:800px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Send New Bulk Campaign', 'flexi-revive-cart' ); ?></h2>
				</div>
				<div class="inside">
					<p class="description">
						<?php esc_html_e( 'Send a WhatsApp message to all abandoned cart users in one click. Messages are sent immediately or after the configured delay.', 'flexi-revive-cart' ); ?>
					</p>

					<table class="form-table">
						<tr>
							<th><label for="frc-wa-campaign-name"><?php esc_html_e( 'Campaign Name', 'flexi-revive-cart' ); ?></label></th>
							<td>
								<input type="text" id="frc-wa-campaign-name" class="regular-text"
									placeholder="<?php esc_attr_e( 'e.g. March Recovery Campaign', 'flexi-revive-cart' ); ?>" />
							</td>
						</tr>
						<tr>
							<th><label for="frc-wa-message"><?php esc_html_e( 'Message Template', 'flexi-revive-cart' ); ?></label></th>
							<td>
								<?php
								$default_bulk_tpl = __( 'Hi {user_name}, your cart at {store_name} is waiting! Complete your purchase and get {discount_amount} off with code {discount_code}: {cart_link}', 'flexi-revive-cart' );
								$bulk_tpl_value   = get_option( 'frc_whatsapp_template_bulk', $default_bulk_tpl );
								?>
								<textarea id="frc-wa-message" class="large-text" rows="4"
									placeholder="<?php esc_attr_e( 'Hi {user_name}, your cart is waiting! Complete now: {cart_link}', 'flexi-revive-cart' ); ?>"><?php echo esc_textarea( $bulk_tpl_value ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Supported variables:', 'flexi-revive-cart' ); ?>
									<code>{user_name}</code>, <code>{cart_total}</code>, <code>{cart_link}</code>,
									<code>{store_name}</code>, <code>{discount_code}</code>, <code>{discount_amount}</code>,
									<code>{abandoned_time}</code>
								</p>
							</td>
						</tr>
						<tr>
							<th><label for="frc-wa-delay"><?php esc_html_e( 'Target Carts Abandoned At Least', 'flexi-revive-cart' ); ?></label></th>
							<td>
								<input type="number" id="frc-wa-delay" class="small-text" value="1" min="0" max="720" />
								<?php esc_html_e( 'hours ago (0 = all abandoned carts)', 'flexi-revive-cart' ); ?>
							</td>
						</tr>
					</table>

					<p>
						<button type="button" id="frc-send-wa-bulk" class="button button-primary">
							<?php esc_html_e( 'Send Bulk WhatsApp Campaign', 'flexi-revive-cart' ); ?>
						</button>
						<span id="frc-wa-bulk-result" style="margin-left:12px;"></span>
					</p>
				</div>
			</div>

			<!-- Campaign History Table -->
			<h2><?php esc_html_e( 'Campaign History', 'flexi-revive-cart' ); ?></h2>
			<?php $this->render_campaigns_table(); ?>
		</div>

		<script>
		(function($) {
			$('#frc-send-wa-bulk').on('click', function() {
				var campaignName = $('#frc-wa-campaign-name').val().trim();
				var message      = $('#frc-wa-message').val().trim();
				var delayHours   = $('#frc-wa-delay').val();
				var $result      = $('#frc-wa-bulk-result');

				if ( ! campaignName ) {
					alert('<?php echo esc_js( __( 'Please enter a campaign name.', 'flexi-revive-cart' ) ); ?>');
					return;
				}
				if ( ! message ) {
					alert('<?php echo esc_js( __( 'Please enter a message template.', 'flexi-revive-cart' ) ); ?>');
					return;
				}

				$(this).prop('disabled', true);
				$result.text('<?php echo esc_js( __( 'Sending…', 'flexi-revive-cart' ) ); ?>');

				$.post(frcAdmin.ajaxUrl, {
					action:        'frc_send_whatsapp_bulk',
					nonce:         frcAdmin.nonce,
					campaign_name: campaignName,
					message:       message,
					delay_hours:   delayHours,
				}, function(response) {
					if ( response.success ) {
						$result.css('color', '#46b450').text(response.data.message);
						setTimeout(function() { location.reload(); }, 2000);
					} else {
						$result.css('color', '#d63638').text(response.data.message);
					}
				}).fail(function() {
					$result.css('color', '#d63638').text('<?php echo esc_js( __( 'Request failed. Please try again.', 'flexi-revive-cart' ) ); ?>');
				}).always(function() {
					$('#frc-send-wa-bulk').prop('disabled', false);
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Render the WhatsApp campaigns history table.
	 */
	private function render_campaigns_table() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaigns = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}frc_whatsapp_campaigns ORDER BY sent_at DESC LIMIT 100" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( empty( $campaigns ) ) {
			echo '<p>' . esc_html__( 'No campaigns sent yet.', 'flexi-revive-cart' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Campaign Name', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent At', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Recipients', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Sent', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Delivered', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Read', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Clicks', 'flexi-revive-cart' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'flexi-revive-cart' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $campaigns as $c ) {
			echo '<tr>';
			echo '<td>' . esc_html( $c->campaign_name ) . '</td>';
			echo '<td>' . esc_html( $c->sent_at ) . '</td>';
			echo '<td>' . esc_html( $c->recipients ) . '</td>';
			echo '<td>' . esc_html( $c->sent ) . '</td>';
			echo '<td>' . esc_html( $c->delivered ) . '</td>';
			echo '<td>' . esc_html( $c->read_count ) . '</td>';
			echo '<td>' . esc_html( $c->clicks ) . '</td>';
			echo '<td><span class="frc-badge ' . ( 'completed' === $c->status ? 'frc-badge-recovered' : 'frc-badge-abandoned' ) . '">' . esc_html( ucfirst( $c->status ) ) . '</span></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
