<?php
/**
 * Admin settings page.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin_Settings
 */
class FRC_Admin_Settings {

	/**
	 * Constructor – register settings.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register all plugin settings using the WordPress Settings API.
	 */
	public function register_settings() {
		// General.
		register_setting( 'frc_general', 'frc_enable_tracking', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_general', 'frc_abandonment_timeout', array( 'sanitize_callback' => 'absint', 'default' => 60 ) );
		register_setting( 'frc_general', 'frc_auto_delete_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_general', 'frc_license_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Email.
		register_setting( 'frc_email', 'frc_enable_email_reminders', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_email', 'frc_num_reminders', array( 'sanitize_callback' => 'absint', 'default' => 3 ) );
		register_setting( 'frc_email', 'frc_reminder_intervals', array( 'sanitize_callback' => array( $this, 'sanitize_intervals' ) ) );
		register_setting( 'frc_email', 'frc_from_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_email', 'frc_from_email', array( 'sanitize_callback' => 'sanitize_email' ) );
		register_setting( 'frc_email', 'frc_email_subjects', array( 'sanitize_callback' => array( $this, 'sanitize_subjects' ) ) );

		// Discount (Pro).
		register_setting( 'frc_discount', 'frc_enable_auto_discounts', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_discount_percentage', array( 'sanitize_callback' => 'absint', 'default' => 10 ) );
		register_setting( 'frc_discount', 'frc_coupon_expiry_hours', array( 'sanitize_callback' => 'absint', 'default' => 72 ) );

		// SMS (Pro).
		register_setting( 'frc_sms', 'frc_enable_sms', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_sms', 'frc_sms_provider', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'twilio' ) );
		register_setting( 'frc_sms', 'frc_twilio_sid', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_twilio_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_twilio_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_auth_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_auth_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Push (Pro).
		register_setting( 'frc_push', 'frc_enable_push', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_push', 'frc_onesignal_app_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_push', 'frc_onesignal_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Popup (Pro).
		register_setting( 'frc_popup', 'frc_enable_guest_capture', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_popup', 'frc_enable_exit_intent', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_popup', 'frc_popup_delay_seconds', array( 'sanitize_callback' => 'absint', 'default' => 30 ) );
		register_setting( 'frc_popup', 'frc_popup_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popup', 'frc_popup_button_text', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Compliance.
		register_setting( 'frc_compliance', 'frc_data_retention_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_compliance', 'frc_optout_page_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	}

	/**
	 * Sanitize intervals array.
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	public function sanitize_intervals( $value ) {
		if ( ! is_array( $value ) ) {
			return array( 1, 6, 24 );
		}
		return array_map( 'absint', $value );
	}

	/**
	 * Sanitize email subjects array.
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	public function sanitize_subjects( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Flexi Revive Cart – Settings', 'flexi-revive-cart' ); ?></h1>

			<nav class="nav-tab-wrapper frc-tabs">
				<?php
				$tabs = array(
					'general'    => __( 'General', 'flexi-revive-cart' ),
					'email'      => __( 'Email', 'flexi-revive-cart' ),
					'discount'   => __( 'Discounts', 'flexi-revive-cart' ),
					'sms'        => __( 'SMS' . ( ! FRC_PRO_ACTIVE ? ' (Pro)' : '' ), 'flexi-revive-cart' ),
					'push'       => __( 'Push' . ( ! FRC_PRO_ACTIVE ? ' (Pro)' : '' ), 'flexi-revive-cart' ),
					'popup'      => __( 'Popups' . ( ! FRC_PRO_ACTIVE ? ' (Pro)' : '' ), 'flexi-revive-cart' ),
					'compliance' => __( 'Compliance', 'flexi-revive-cart' ),
				);
				foreach ( $tabs as $tab_id => $tab_label ) {
					$class = ( $tab_id === $active_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
					echo '<a href="' . esc_url( admin_url( 'admin.php?page=frc-settings&tab=' . $tab_id ) ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $tab_label ) . '</a>';
				}
				?>
			</nav>

			<form method="post" action="options.php" class="frc-settings-form">
				<?php

				switch ( $active_tab ) {
					case 'general':
						settings_fields( 'frc_general' );
						$this->render_general_settings();
						break;
					case 'email':
						settings_fields( 'frc_email' );
						$this->render_email_settings();
						break;
					case 'discount':
						settings_fields( 'frc_discount' );
						$this->render_discount_settings();
						break;
					case 'sms':
						settings_fields( 'frc_sms' );
						$this->render_sms_settings();
						break;
					case 'push':
						settings_fields( 'frc_push' );
						$this->render_push_settings();
						break;
					case 'popup':
						settings_fields( 'frc_popup' );
						$this->render_popup_settings();
						break;
					case 'compliance':
						settings_fields( 'frc_compliance' );
						$this->render_compliance_settings();
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/** Render General Settings tab. */
	private function render_general_settings() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Tracking', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_tracking" value="1" <?php checked( get_option( 'frc_enable_tracking', '1' ) ); ?> /> <?php esc_html_e( 'Track abandoned carts', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Abandonment Timeout (minutes)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_abandonment_timeout" value="<?php echo esc_attr( get_option( 'frc_abandonment_timeout', 60 ) ); ?>" min="5" max="1440" class="small-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-delete carts after (days)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_auto_delete_days" value="<?php echo esc_attr( get_option( 'frc_auto_delete_days', 90 ) ); ?>" min="7" max="365" class="small-text" /></td>
			</tr>
			<?php if ( ! FRC_PRO_ACTIVE ) : ?>
			<tr>
				<th><?php esc_html_e( 'Pro License Key', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_license_key" value="<?php echo esc_attr( get_option( 'frc_license_key', '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your Pro license key', 'flexi-revive-cart' ); ?>" />
					<p class="description"><a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank"><?php esc_html_e( 'Get a Pro license key', 'flexi-revive-cart' ); ?></a></p>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/** Render Email Settings tab. */
	private function render_email_settings() {
		$subjects  = get_option( 'frc_email_subjects', array(
			__( 'You left something behind!', 'flexi-revive-cart' ),
			__( 'Your cart is waiting – items may sell out!', 'flexi-revive-cart' ),
			__( 'Here\'s a special offer to complete your purchase!', 'flexi-revive-cart' ),
		) );
		$intervals = get_option( 'frc_reminder_intervals', array( 1, 6, 24 ) );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Email Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_email_reminders" value="1" <?php checked( get_option( 'frc_enable_email_reminders', '1' ) ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Number of Reminders', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_num_reminders" value="<?php echo esc_attr( get_option( 'frc_num_reminders', 3 ) ); ?>" min="1" max="<?php echo FRC_PRO_ACTIVE ? 10 : 1; ?>" class="small-text" />
					<?php if ( ! FRC_PRO_ACTIVE ) : ?>
					<p class="description"><?php esc_html_e( 'Free version is limited to 1 reminder. Upgrade to Pro for unlimited reminders.', 'flexi-revive-cart' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'From Name', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_from_name" value="<?php echo esc_attr( get_option( 'frc_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'From Email', 'flexi-revive-cart' ); ?></th>
				<td><input type="email" name="frc_from_email" value="<?php echo esc_attr( get_option( 'frc_from_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" /></td>
			</tr>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'Reminder %d – Subject', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td>
					<input type="text" name="frc_email_subjects[<?php echo esc_attr( $i - 1 ); ?>]" value="<?php echo esc_attr( isset( $subjects[ $i - 1 ] ) ? $subjects[ $i - 1 ] : '' ); ?>" class="large-text" />
				</td>
			</tr>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'Reminder %d – Send After (hours)', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td><input type="number" name="frc_reminder_intervals[<?php echo esc_attr( $i - 1 ); ?>]" value="<?php echo esc_attr( isset( $intervals[ $i - 1 ] ) ? $intervals[ $i - 1 ] : 1 ); ?>" min="1" class="small-text" /></td>
			</tr>
			<?php endfor; ?>
			<tr>
				<th><?php esc_html_e( 'Send Test Email', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="email" id="frc-test-email-to" placeholder="<?php esc_attr_e( 'Email address', 'flexi-revive-cart' ); ?>" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
					<select id="frc-test-email-stage">
						<option value="1"><?php esc_html_e( 'Stage 1 – Friendly', 'flexi-revive-cart' ); ?></option>
						<option value="2"><?php esc_html_e( 'Stage 2 – Urgency', 'flexi-revive-cart' ); ?></option>
						<option value="3"><?php esc_html_e( 'Stage 3 – Incentive', 'flexi-revive-cart' ); ?></option>
					</select>
					<button type="button" id="frc-send-test-email" class="button"><?php esc_html_e( 'Send Test', 'flexi-revive-cart' ); ?></button>
					<span id="frc-test-email-result"></span>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Render Discount Settings tab. */
	private function render_discount_settings() {
		$this->maybe_show_pro_notice();
		?>
		<table class="form-table <?php echo ! FRC_PRO_ACTIVE ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Enable Auto-Discounts', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_auto_discounts" value="1" <?php checked( get_option( 'frc_enable_auto_discounts', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Discount Percentage (%)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_discount_percentage" value="<?php echo esc_attr( get_option( 'frc_discount_percentage', 10 ) ); ?>" min="1" max="100" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Coupon Expiry (hours)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_coupon_expiry_hours" value="<?php echo esc_attr( get_option( 'frc_coupon_expiry_hours', 72 ) ); ?>" min="1" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	/** Render SMS Settings tab. */
	private function render_sms_settings() {
		$this->maybe_show_pro_notice();
		?>
		<table class="form-table <?php echo ! FRC_PRO_ACTIVE ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Enable SMS', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_sms" value="1" <?php checked( get_option( 'frc_enable_sms', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'SMS Provider', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_sms_provider" <?php disabled( ! FRC_PRO_ACTIVE ); ?>>
						<option value="twilio" <?php selected( get_option( 'frc_sms_provider' ), 'twilio' ); ?>><?php esc_html_e( 'Twilio', 'flexi-revive-cart' ); ?></option>
						<option value="plivo" <?php selected( get_option( 'frc_sms_provider' ), 'plivo' ); ?>><?php esc_html_e( 'Plivo', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Twilio Account SID', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_twilio_sid" value="<?php echo esc_attr( get_option( 'frc_twilio_sid', '' ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Twilio Auth Token', 'flexi-revive-cart' ); ?></th>
				<td><input type="password" name="frc_twilio_token" value="<?php echo esc_attr( get_option( 'frc_twilio_token', '' ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Twilio From Number', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_twilio_from" value="<?php echo esc_attr( get_option( 'frc_twilio_from', '' ) ); ?>" class="regular-text" placeholder="+1234567890" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	/** Render Push Settings tab. */
	private function render_push_settings() {
		$this->maybe_show_pro_notice();
		?>
		<table class="form-table <?php echo ! FRC_PRO_ACTIVE ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Enable Push Notifications', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_push" value="1" <?php checked( get_option( 'frc_enable_push', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'OneSignal App ID', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_onesignal_app_id" value="<?php echo esc_attr( get_option( 'frc_onesignal_app_id', '' ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'OneSignal REST API Key', 'flexi-revive-cart' ); ?></th>
				<td><input type="password" name="frc_onesignal_api_key" value="<?php echo esc_attr( get_option( 'frc_onesignal_api_key', '' ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	/** Render Popup Settings tab. */
	private function render_popup_settings() {
		$this->maybe_show_pro_notice();
		?>
		<table class="form-table <?php echo ! FRC_PRO_ACTIVE ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Enable Guest Capture Popup', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_guest_capture" value="1" <?php checked( get_option( 'frc_enable_guest_capture', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Enable Exit-Intent Popup', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_exit_intent" value="1" <?php checked( get_option( 'frc_enable_exit_intent', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Delay (seconds)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_popup_delay_seconds" value="<?php echo esc_attr( get_option( 'frc_popup_delay_seconds', 30 ) ); ?>" min="5" max="300" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Message', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_popup_message" value="<?php echo esc_attr( get_option( 'frc_popup_message', __( 'Wait! Don\'t leave your cart behind.', 'flexi-revive-cart' ) ) ); ?>" class="large-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Button Text', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_popup_button_text" value="<?php echo esc_attr( get_option( 'frc_popup_button_text', __( 'Save My Cart', 'flexi-revive-cart' ) ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
		</table>
		<?php
	}

	/** Render Compliance Settings tab. */
	private function render_compliance_settings() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Data Retention (days)', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" name="frc_data_retention_days" value="<?php echo esc_attr( get_option( 'frc_data_retention_days', 90 ) ); ?>" min="7" max="730" class="small-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Opt-Out Page URL', 'flexi-revive-cart' ); ?></th>
				<td><input type="url" name="frc_optout_page_url" value="<?php echo esc_attr( get_option( 'frc_optout_page_url', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Privacy Policy Snippet', 'flexi-revive-cart' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Add the following to your Privacy Policy:', 'flexi-revive-cart' ); ?>
					</p>
					<textarea class="large-text" rows="5" readonly><?php echo esc_textarea( __( 'This website uses Flexi Revive Cart to track and recover abandoned shopping carts. If you add products to your cart and provide your email address, we may send you reminder emails. You can unsubscribe at any time by clicking the unsubscribe link in any reminder email.', 'flexi-revive-cart' ) ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render a Pro upgrade notice if not Pro.
	 */
	private function maybe_show_pro_notice() {
		if ( ! FRC_PRO_ACTIVE ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo esc_html__( 'These settings require a Pro license.', 'flexi-revive-cart' );
			echo ' <a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank">' . esc_html__( 'Upgrade to Pro', 'flexi-revive-cart' ) . '</a>';
			echo '</p></div>';
		}
	}
}
