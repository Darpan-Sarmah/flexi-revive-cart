<?php
/**
 * Pro settings tabs – registers all Pro-specific settings.
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Admin_Settings
 */
class FRC_Pro_Admin_Settings {

	/**
	 * Add Pro settings tabs.
	 *
	 * Hooked to: frc_admin_tabs
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_settings_tabs( $tabs ) {
		$tabs['frc_discounts']      = __( 'Discounts', 'flexi-revive-cart-pro' );
		$tabs['frc_sms']            = __( 'SMS', 'flexi-revive-cart-pro' );
		$tabs['frc_whatsapp']       = __( 'WhatsApp', 'flexi-revive-cart-pro' );
		$tabs['frc_push']           = __( 'Push Notifications', 'flexi-revive-cart-pro' );
		$tabs['frc_popups']         = __( 'Popups', 'flexi-revive-cart-pro' );
		$tabs['frc_guest_tracking'] = __( 'Guest Tracking', 'flexi-revive-cart-pro' );
		$tabs['frc_ab_testing']     = __( 'A/B Testing', 'flexi-revive-cart-pro' );
		return $tabs;
	}

	/**
	 * Register all Pro settings.
	 *
	 * Hooked to: frc_register_settings
	 */
	public function register_settings() {
		// ── Discount Settings ─────────────────────────────────────────────
		register_setting( 'frc_discounts', 'frc_enable_auto_discounts', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_discounts', 'frc_discount_type', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_discounts', 'frc_discount_percentage', array( 'sanitize_callback' => 'floatval' ) );
		register_setting( 'frc_discounts', 'frc_min_cart_value', array( 'sanitize_callback' => 'floatval' ) );
		register_setting( 'frc_discounts', 'frc_coupon_expiry_days', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_discounts', 'frc_coupon_expiry_unit', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_discounts', 'frc_coupon_usage_limit', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_discounts', 'frc_coupon_prefix', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_discounts', 'frc_coupon_suffix', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_discounts', 'frc_exclude_sale_items', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_discounts', 'frc_exclude_product_ids', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_discounts', 'frc_exclude_category_ids', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// ── SMS Settings ──────────────────────────────────────────────────
		register_setting( 'frc_sms', 'frc_enable_sms', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_sms', 'frc_sms_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_twilio_sid', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_twilio_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_twilio_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_auth_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_auth_token', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_sms', 'frc_plivo_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_sms', 'frc_sms_template_' . $i, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		}

		// ── WhatsApp Settings ─────────────────────────────────────────────
		register_setting( 'frc_whatsapp', 'frc_enable_whatsapp', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_whatsapp', 'frc_whatsapp_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_whatsapp', 'frc_whatsapp_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_whatsapp', 'frc_whatsapp_template_' . $i, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		}
		register_setting( 'frc_whatsapp', 'frc_whatsapp_template_bulk', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );

		// ── Push Settings ─────────────────────────────────────────────────
		register_setting( 'frc_push', 'frc_enable_push', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_push', 'frc_onesignal_app_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_push', 'frc_onesignal_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_push', 'frc_push_title_' . $i, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			register_setting( 'frc_push', 'frc_push_message_' . $i, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		}

		// ── Popup Settings ────────────────────────────────────────────────
		register_setting( 'frc_popups', 'frc_enable_guest_capture', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_popups', 'frc_enable_exit_intent', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_popups', 'frc_guest_capture_delay', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_popups', 'frc_popup_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popups', 'frc_popup_button_text', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// ── Guest Tracking Settings ───────────────────────────────────────
		register_setting( 'frc_guest_tracking', 'frc_enable_browse_abandonment', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_guest_tracking', 'frc_browse_followup_hours', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'frc_guest_tracking', 'frc_browse_followup_unit', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// ── A/B Testing Settings ──────────────────────────────────────────
		register_setting( 'frc_ab_testing', 'frc_enable_ab_testing', array( 'sanitize_callback' => 'absint' ) );

		// Render callbacks for each Pro tab.
		add_action( 'frc_render_settings_tab_frc_discounts', array( $this, 'render_discounts_tab' ) );
		add_action( 'frc_render_settings_tab_frc_sms', array( $this, 'render_sms_tab' ) );
		add_action( 'frc_render_settings_tab_frc_whatsapp', array( $this, 'render_whatsapp_tab' ) );
		add_action( 'frc_render_settings_tab_frc_push', array( $this, 'render_push_tab' ) );
		add_action( 'frc_render_settings_tab_frc_popups', array( $this, 'render_popups_tab' ) );
		add_action( 'frc_render_settings_tab_frc_guest_tracking', array( $this, 'render_guest_tracking_tab' ) );
		add_action( 'frc_render_settings_tab_frc_ab_testing', array( $this, 'render_ab_testing_tab' ) );
	}

	/**
	 * Render the Discounts settings tab.
	 */
	public function render_discounts_tab() {
		settings_fields( 'frc_discounts' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_auto_discounts"><?php esc_html_e( 'Enable Auto Discounts', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_auto_discounts" name="frc_enable_auto_discounts" value="1" <?php checked( get_option( 'frc_enable_auto_discounts', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_discount_type"><?php esc_html_e( 'Discount Type', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<select id="frc_discount_type" name="frc_discount_type">
						<option value="percent" <?php selected( get_option( 'frc_discount_type', 'percent' ), 'percent' ); ?>><?php esc_html_e( 'Percentage', 'flexi-revive-cart-pro' ); ?></option>
						<option value="fixed_cart" <?php selected( get_option( 'frc_discount_type', 'percent' ), 'fixed_cart' ); ?>><?php esc_html_e( 'Fixed Amount', 'flexi-revive-cart-pro' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="frc_discount_percentage"><?php esc_html_e( 'Discount Amount', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="number" id="frc_discount_percentage" name="frc_discount_percentage" value="<?php echo esc_attr( get_option( 'frc_discount_percentage', 10 ) ); ?>" min="0" max="100" step="0.01" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_min_cart_value"><?php esc_html_e( 'Minimum Cart Value', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="number" id="frc_min_cart_value" name="frc_min_cart_value" value="<?php echo esc_attr( get_option( 'frc_min_cart_value', 0 ) ); ?>" min="0" step="0.01" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_coupon_expiry_days"><?php esc_html_e( 'Coupon Expiry', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<input type="number" id="frc_coupon_expiry_days" name="frc_coupon_expiry_days" value="<?php echo esc_attr( get_option( 'frc_coupon_expiry_days', 7 ) ); ?>" min="1" class="small-text" />
					<select name="frc_coupon_expiry_unit">
						<option value="days" <?php selected( get_option( 'frc_coupon_expiry_unit', 'days' ), 'days' ); ?>><?php esc_html_e( 'Days', 'flexi-revive-cart-pro' ); ?></option>
						<option value="hours" <?php selected( get_option( 'frc_coupon_expiry_unit', 'days' ), 'hours' ); ?>><?php esc_html_e( 'Hours', 'flexi-revive-cart-pro' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="frc_coupon_usage_limit"><?php esc_html_e( 'Usage Limit per Coupon', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="number" id="frc_coupon_usage_limit" name="frc_coupon_usage_limit" value="<?php echo esc_attr( get_option( 'frc_coupon_usage_limit', 1 ) ); ?>" min="1" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_coupon_prefix"><?php esc_html_e( 'Coupon Code Prefix', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_coupon_prefix" name="frc_coupon_prefix" value="<?php echo esc_attr( get_option( 'frc_coupon_prefix', 'RECOVER' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_coupon_suffix"><?php esc_html_e( 'Coupon Code Suffix', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_coupon_suffix" name="frc_coupon_suffix" value="<?php echo esc_attr( get_option( 'frc_coupon_suffix', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_exclude_sale_items"><?php esc_html_e( 'Exclude Sale Items', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_exclude_sale_items" name="frc_exclude_sale_items" value="1" <?php checked( get_option( 'frc_exclude_sale_items', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_exclude_product_ids"><?php esc_html_e( 'Exclude Product IDs', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<input type="text" id="frc_exclude_product_ids" name="frc_exclude_product_ids" value="<?php echo esc_attr( get_option( 'frc_exclude_product_ids', '' ) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Comma-separated product IDs', 'flexi-revive-cart-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="frc_exclude_category_ids"><?php esc_html_e( 'Exclude Category IDs', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<input type="text" id="frc_exclude_category_ids" name="frc_exclude_category_ids" value="<?php echo esc_attr( get_option( 'frc_exclude_category_ids', '' ) ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Comma-separated category IDs', 'flexi-revive-cart-pro' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the SMS settings tab.
	 */
	public function render_sms_tab() {
		settings_fields( 'frc_sms' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_sms"><?php esc_html_e( 'Enable SMS Reminders', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_sms" name="frc_enable_sms" value="1" <?php checked( get_option( 'frc_enable_sms', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_sms_provider"><?php esc_html_e( 'SMS Provider', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<select id="frc_sms_provider" name="frc_sms_provider">
						<option value="twilio" <?php selected( get_option( 'frc_sms_provider', 'twilio' ), 'twilio' ); ?>>Twilio</option>
						<option value="plivo" <?php selected( get_option( 'frc_sms_provider', 'twilio' ), 'plivo' ); ?>>Plivo</option>
					</select>
				</td>
			</tr>
			<tr><th colspan="2"><hr><strong><?php esc_html_e( 'Twilio Credentials', 'flexi-revive-cart-pro' ); ?></strong></th></tr>
			<tr>
				<th><label for="frc_twilio_sid"><?php esc_html_e( 'Account SID', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_twilio_sid" name="frc_twilio_sid" value="<?php echo esc_attr( get_option( 'frc_twilio_sid', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_twilio_token"><?php esc_html_e( 'Auth Token', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="password" id="frc_twilio_token" name="frc_twilio_token" value="<?php echo esc_attr( get_option( 'frc_twilio_token', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_twilio_from"><?php esc_html_e( 'From Number', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_twilio_from" name="frc_twilio_from" value="<?php echo esc_attr( get_option( 'frc_twilio_from', '' ) ); ?>" class="regular-text" placeholder="+14155551234" /></td>
			</tr>
			<tr><th colspan="2"><hr><strong><?php esc_html_e( 'Plivo Credentials', 'flexi-revive-cart-pro' ); ?></strong></th></tr>
			<tr>
				<th><label for="frc_plivo_auth_id"><?php esc_html_e( 'Auth ID', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_plivo_auth_id" name="frc_plivo_auth_id" value="<?php echo esc_attr( get_option( 'frc_plivo_auth_id', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_plivo_auth_token"><?php esc_html_e( 'Auth Token', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="password" id="frc_plivo_auth_token" name="frc_plivo_auth_token" value="<?php echo esc_attr( get_option( 'frc_plivo_auth_token', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_plivo_from"><?php esc_html_e( 'From Number', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_plivo_from" name="frc_plivo_from" value="<?php echo esc_attr( get_option( 'frc_plivo_from', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><label for="frc_sms_template_<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Stage %d Template', 'flexi-revive-cart-pro' ), $i ) ); ?></label></th>
				<td><textarea id="frc_sms_template_<?php echo esc_attr( $i ); ?>" name="frc_sms_template_<?php echo esc_attr( $i ); ?>" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'frc_sms_template_' . $i, '' ) ); ?></textarea></td>
			</tr>
			<?php endfor; ?>
		</table>
		<?php
	}

	/**
	 * Render the WhatsApp settings tab.
	 */
	public function render_whatsapp_tab() {
		settings_fields( 'frc_whatsapp' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_whatsapp"><?php esc_html_e( 'Enable WhatsApp', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_whatsapp" name="frc_enable_whatsapp" value="1" <?php checked( get_option( 'frc_enable_whatsapp', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_whatsapp_provider"><?php esc_html_e( 'Provider', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<select id="frc_whatsapp_provider" name="frc_whatsapp_provider">
						<option value="twilio" <?php selected( get_option( 'frc_whatsapp_provider', 'twilio' ), 'twilio' ); ?>>Twilio</option>
						<option value="plivo" <?php selected( get_option( 'frc_whatsapp_provider', 'twilio' ), 'plivo' ); ?>>Plivo</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="frc_whatsapp_from"><?php esc_html_e( 'WhatsApp From Number', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_whatsapp_from" name="frc_whatsapp_from" value="<?php echo esc_attr( get_option( 'frc_whatsapp_from', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><label for="frc_whatsapp_template_<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Stage %d Template', 'flexi-revive-cart-pro' ), $i ) ); ?></label></th>
				<td><textarea id="frc_whatsapp_template_<?php echo esc_attr( $i ); ?>" name="frc_whatsapp_template_<?php echo esc_attr( $i ); ?>" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'frc_whatsapp_template_' . $i, '' ) ); ?></textarea></td>
			</tr>
			<?php endfor; ?>
		</table>
		<?php
	}

	/**
	 * Render the Push Notifications settings tab.
	 */
	public function render_push_tab() {
		settings_fields( 'frc_push' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_push"><?php esc_html_e( 'Enable Push Notifications', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_push" name="frc_enable_push" value="1" <?php checked( get_option( 'frc_enable_push', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_onesignal_app_id"><?php esc_html_e( 'OneSignal App ID', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_onesignal_app_id" name="frc_onesignal_app_id" value="<?php echo esc_attr( get_option( 'frc_onesignal_app_id', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_onesignal_api_key"><?php esc_html_e( 'OneSignal REST API Key', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="password" id="frc_onesignal_api_key" name="frc_onesignal_api_key" value="<?php echo esc_attr( get_option( 'frc_onesignal_api_key', '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><label for="frc_push_title_<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Stage %d Title', 'flexi-revive-cart-pro' ), $i ) ); ?></label></th>
				<td><input type="text" id="frc_push_title_<?php echo esc_attr( $i ); ?>" name="frc_push_title_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( get_option( 'frc_push_title_' . $i, '' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_push_message_<?php echo esc_attr( $i ); ?>"><?php echo esc_html( sprintf( __( 'Stage %d Message', 'flexi-revive-cart-pro' ), $i ) ); ?></label></th>
				<td><textarea id="frc_push_message_<?php echo esc_attr( $i ); ?>" name="frc_push_message_<?php echo esc_attr( $i ); ?>" class="large-text" rows="2"><?php echo esc_textarea( get_option( 'frc_push_message_' . $i, '' ) ); ?></textarea></td>
			</tr>
			<?php endfor; ?>
		</table>
		<?php
	}

	/**
	 * Render the Popups settings tab.
	 */
	public function render_popups_tab() {
		settings_fields( 'frc_popups' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_guest_capture"><?php esc_html_e( 'Enable Guest Capture Popup', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_guest_capture" name="frc_enable_guest_capture" value="1" <?php checked( get_option( 'frc_enable_guest_capture', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_enable_exit_intent"><?php esc_html_e( 'Enable Exit-Intent Popup', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_exit_intent" name="frc_enable_exit_intent" value="1" <?php checked( get_option( 'frc_enable_exit_intent', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_guest_capture_delay"><?php esc_html_e( 'Show Popup After (seconds)', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="number" id="frc_guest_capture_delay" name="frc_guest_capture_delay" value="<?php echo esc_attr( get_option( 'frc_guest_capture_delay', 5 ) ); ?>" min="0" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_popup_message"><?php esc_html_e( 'Popup Message', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_popup_message" name="frc_popup_message" value="<?php echo esc_attr( get_option( 'frc_popup_message', __( 'Wait! Don\'t leave your cart behind.', 'flexi-revive-cart-pro' ) ) ); ?>" class="large-text" /></td>
			</tr>
			<tr>
				<th><label for="frc_popup_button_text"><?php esc_html_e( 'Button Text', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="text" id="frc_popup_button_text" name="frc_popup_button_text" value="<?php echo esc_attr( get_option( 'frc_popup_button_text', __( 'Save My Cart', 'flexi-revive-cart-pro' ) ) ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the Guest Tracking settings tab.
	 */
	public function render_guest_tracking_tab() {
		settings_fields( 'frc_guest_tracking' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_browse_abandonment"><?php esc_html_e( 'Enable Browse Abandonment', 'flexi-revive-cart-pro' ); ?></label></th>
				<td><input type="checkbox" id="frc_enable_browse_abandonment" name="frc_enable_browse_abandonment" value="1" <?php checked( get_option( 'frc_enable_browse_abandonment', '0' ), '1' ); ?> /></td>
			</tr>
			<tr>
				<th><label for="frc_browse_followup_hours"><?php esc_html_e( 'Follow-up Delay', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<input type="number" id="frc_browse_followup_hours" name="frc_browse_followup_hours" value="<?php echo esc_attr( get_option( 'frc_browse_followup_hours', 2 ) ); ?>" min="1" class="small-text" />
					<select name="frc_browse_followup_unit">
						<option value="hours" <?php selected( get_option( 'frc_browse_followup_unit', 'hours' ), 'hours' ); ?>><?php esc_html_e( 'Hours', 'flexi-revive-cart-pro' ); ?></option>
						<option value="days" <?php selected( get_option( 'frc_browse_followup_unit', 'hours' ), 'days' ); ?>><?php esc_html_e( 'Days', 'flexi-revive-cart-pro' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the A/B Testing settings tab.
	 */
	public function render_ab_testing_tab() {
		settings_fields( 'frc_ab_testing' );
		?>
		<table class="form-table">
			<tr>
				<th><label for="frc_enable_ab_testing"><?php esc_html_e( 'Enable A/B Testing', 'flexi-revive-cart-pro' ); ?></label></th>
				<td>
					<input type="checkbox" id="frc_enable_ab_testing" name="frc_enable_ab_testing" value="1" <?php checked( get_option( 'frc_enable_ab_testing', '0' ), '1' ); ?> />
					<p class="description"><?php esc_html_e( 'When enabled, email subjects will be automatically split-tested. Manage tests from the A/B Tests page.', 'flexi-revive-cart-pro' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
}
