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
		register_setting( 'frc_general', 'frc_abandonment_timeout_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'minutes' ) );
		register_setting( 'frc_general', 'frc_auto_delete_interval', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_general', 'frc_auto_delete_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'days' ) );
		// Legacy setting kept for backward compatibility.
		register_setting( 'frc_general', 'frc_auto_delete_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_general', 'frc_license_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		// Language.
		register_setting( 'frc_language', 'frc_backend_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );
		register_setting( 'frc_language', 'frc_frontend_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );
		// Legacy settings kept for backward compatibility.
		register_setting( 'frc_language', 'frc_admin_preview_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => '' ) );
		register_setting( 'frc_language', 'frc_default_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );

		// Email.
		register_setting( 'frc_email', 'frc_enable_email_reminders', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_email', 'frc_num_reminders', array( 'sanitize_callback' => 'absint', 'default' => 3 ) );
		register_setting( 'frc_email', 'frc_reminder_interval', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_email', 'frc_reminder_interval_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'hours' ) );
		register_setting( 'frc_email', 'frc_reminder_type', array( 'sanitize_callback' => array( $this, 'sanitize_reminder_type' ) ) );
		// Legacy settings kept for backward compatibility.
		register_setting( 'frc_email', 'frc_reminder_intervals', array( 'sanitize_callback' => array( $this, 'sanitize_intervals' ) ) );
		register_setting( 'frc_email', 'frc_reminder_types', array( 'sanitize_callback' => array( $this, 'sanitize_reminder_types' ) ) );
		register_setting( 'frc_email', 'frc_reminder_enabled', array( 'sanitize_callback' => array( $this, 'sanitize_reminder_enabled' ) ) );
		register_setting( 'frc_email', 'frc_from_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_email', 'frc_from_email', array( 'sanitize_callback' => 'sanitize_email' ) );
		register_setting( 'frc_email', 'frc_email_subjects', array( 'sanitize_callback' => array( $this, 'sanitize_subjects' ) ) );

		// Discount (Pro).
		register_setting( 'frc_discount', 'frc_enable_auto_discounts', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_discount_type', array( 'sanitize_callback' => array( $this, 'sanitize_discount_type' ), 'default' => 'percent' ) );
		register_setting( 'frc_discount', 'frc_discount_percentage', array( 'sanitize_callback' => array( $this, 'sanitize_positive_float' ), 'default' => 10 ) );
		register_setting( 'frc_discount', 'frc_min_cart_value', array( 'sanitize_callback' => array( $this, 'sanitize_positive_float' ), 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_coupon_expiry_days', array( 'sanitize_callback' => 'absint', 'default' => 7 ) );
		register_setting( 'frc_discount', 'frc_coupon_expiry_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'days' ) );
		register_setting( 'frc_discount', 'frc_coupon_usage_limit', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_discount', 'frc_exclude_sale_items', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_exclude_product_ids', array( 'sanitize_callback' => array( $this, 'sanitize_id_list' ) ) );
		register_setting( 'frc_discount', 'frc_exclude_category_ids', array( 'sanitize_callback' => array( $this, 'sanitize_id_list' ) ) );
		register_setting( 'frc_discount', 'frc_coupon_prefix', array( 'sanitize_callback' => array( $this, 'sanitize_coupon_affix' ), 'default' => 'RECOVER' ) );
		register_setting( 'frc_discount', 'frc_coupon_suffix', array( 'sanitize_callback' => array( $this, 'sanitize_coupon_affix' ) ) );
		register_setting( 'frc_discount', 'frc_auto_apply_coupon', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		// Kept for backward compatibility; no longer shown in UI.
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
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_sms', 'frc_sms_template_' . $i, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		}

		// Push (Pro).
		register_setting( 'frc_push', 'frc_enable_push', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_push', 'frc_onesignal_app_id', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_push', 'frc_onesignal_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_push', 'frc_push_title_' . $i, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			register_setting( 'frc_push', 'frc_push_message_' . $i, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		}

		// WhatsApp (Pro).
		register_setting( 'frc_whatsapp', 'frc_enable_whatsapp', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_whatsapp', 'frc_whatsapp_provider', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'twilio' ) );
		register_setting( 'frc_whatsapp', 'frc_whatsapp_from', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		for ( $i = 1; $i <= 3; $i++ ) {
			register_setting( 'frc_whatsapp', 'frc_whatsapp_template_' . $i, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		}
		register_setting( 'frc_whatsapp', 'frc_whatsapp_template_bulk', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );

		// Popup (Pro).
		register_setting( 'frc_popup', 'frc_enable_guest_capture', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_popup', 'frc_enable_exit_intent', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_popup', 'frc_popup_delay_seconds', array( 'sanitize_callback' => 'absint', 'default' => 30 ) );
		register_setting( 'frc_popup', 'frc_popup_delay_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'seconds' ) );
		register_setting( 'frc_popup', 'frc_popup_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popup', 'frc_popup_button_text', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popup', 'frc_browse_followup_hours', array( 'sanitize_callback' => 'absint', 'default' => 2 ) );
		register_setting( 'frc_popup', 'frc_browse_followup_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'hours' ) );

		// Guest Tracking (Pro).
		register_setting( 'frc_guest_tracking', 'frc_enable_guest_tracking', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_capture_method', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'popup' ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_popup_timing', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => 'exit_intent' ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_force_login', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_pre_checkout_capture', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_cart_retention', array( 'sanitize_callback' => 'absint', 'default' => 30 ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_cart_retention_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'days' ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_exclude_countries', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_guest_tracking', 'frc_guest_exclude_ips', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );

		// Compliance.
		register_setting( 'frc_compliance', 'frc_data_retention_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_compliance', 'frc_data_retention_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'days' ) );
		register_setting( 'frc_compliance', 'frc_optout_page_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	}

	/**
	 * Sanitize a positive float (discount amount, min cart value, etc.).
	 *
	 * @param mixed $value Input value.
	 * @return float
	 */
	public function sanitize_positive_float( $value ) {
		$float = floatval( $value );
		return max( 0.0, $float );
	}

	/**
	 * Sanitize a language code against supported languages.
	 *
	 * @param mixed $value Input value.
	 * @return string Valid language code or 'en'.
	 */
	public function sanitize_language_code( $value ) {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			return '';
		}
		return FRC_Email_Templates::validate_lang( $value );
	}

	/**
	 * Sanitize the discount type option.
	 *
	 * @param mixed $value Input value.
	 * @return string 'percent' or 'fixed_cart'.
	 */
	public function sanitize_discount_type( $value ) {
		return in_array( $value, array( 'percent', 'fixed_cart' ), true ) ? $value : 'percent';
	}

	/**
	 * Sanitize a comma-separated list of positive integer IDs.
	 *
	 * @param mixed $value Input value.
	 * @return string Sanitized comma-separated IDs.
	 */
	public function sanitize_id_list( $value ) {
		if ( empty( $value ) ) {
			return '';
		}
		$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( $value ) ) ) );
		return implode( ',', $ids );
	}

	/**
	 * Sanitize a coupon code prefix or suffix (alphanumeric and hyphens only).
	 *
	 * @param mixed $value Input value.
	 * @return string
	 */
	public function sanitize_coupon_affix( $value ) {
		// Allow letters, digits, and hyphens; strip everything else.
		return strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', sanitize_text_field( $value ) ) );
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
	 * Sanitize reminder types array.
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	public function sanitize_reminder_types( $value ) {
		if ( ! is_array( $value ) ) {
			return array( 'friendly', 'friendly', 'friendly' );
		}
		$allowed = array( 'friendly', 'urgency', 'incentive' );
		return array_map(
			function ( $v ) use ( $allowed ) {
				$v = sanitize_text_field( $v );
				return in_array( $v, $allowed, true ) ? $v : 'friendly';
			},
			$value
		);
	}

	/**
	 * Sanitize a single reminder type value.
	 *
	 * @param mixed $value Input value.
	 * @return string Valid reminder type.
	 */
	public function sanitize_reminder_type( $value ) {
		$value   = sanitize_text_field( $value );
		$allowed = array( 'friendly', 'urgency', 'incentive' );
		return in_array( $value, $allowed, true ) ? $value : 'friendly';
	}

	/**
	 * Sanitize reminder enabled flags array.
	 *
	 * @param mixed $value Input value.
	 * @return array
	 */
	public function sanitize_reminder_enabled( $value ) {
		if ( ! is_array( $value ) ) {
			return array( 1, 1, 1 );
		}
		return array_map( 'absint', $value );
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Flexi Revive Cart – Settings', 'flexi-revive-cart' ); ?></h1>
			<?php settings_errors( 'flexi-revive-cart-settings' ); ?>

			<nav class="nav-tab-wrapper frc-tabs">
				<?php
				$tabs = array(
					'general'        => __( 'General', 'flexi-revive-cart' ),
					'language'       => __( 'Language', 'flexi-revive-cart' ),
					'email'          => __( 'Email', 'flexi-revive-cart' ),
					'discount'       => FRC_PRO_ACTIVE ? __( 'Discounts', 'flexi-revive-cart' ) : __( 'Discounts (Pro)', 'flexi-revive-cart' ),
					'sms'            => FRC_PRO_ACTIVE ? __( 'SMS', 'flexi-revive-cart' ) : __( 'SMS (Pro)', 'flexi-revive-cart' ),
					'whatsapp'       => FRC_PRO_ACTIVE ? __( 'WhatsApp', 'flexi-revive-cart' ) : __( 'WhatsApp (Pro)', 'flexi-revive-cart' ),
					'push'           => FRC_PRO_ACTIVE ? __( 'Push', 'flexi-revive-cart' ) : __( 'Push (Pro)', 'flexi-revive-cart' ),
					'popup'          => FRC_PRO_ACTIVE ? __( 'Popups', 'flexi-revive-cart' ) : __( 'Popups (Pro)', 'flexi-revive-cart' ),
					'guest_tracking' => FRC_PRO_ACTIVE ? __( 'Guest Tracking', 'flexi-revive-cart' ) : __( 'Guest Tracking (Pro)', 'flexi-revive-cart' ),
					'compliance'     => __( 'Compliance', 'flexi-revive-cart' ),
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
					case 'language':
						settings_fields( 'frc_language' );
						$this->render_language_settings();
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
					case 'whatsapp':
						settings_fields( 'frc_whatsapp' );
						$this->render_whatsapp_settings();
						break;
					case 'push':
						settings_fields( 'frc_push' );
						$this->render_push_settings();
						break;
					case 'popup':
						settings_fields( 'frc_popup' );
						$this->render_popup_settings();
						break;
					case 'guest_tracking':
						settings_fields( 'frc_guest_tracking' );
						$this->render_guest_tracking_settings();
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

	/** Render Language Settings tab. */
	private function render_language_settings() {
		$languages      = FRC_Email_Templates::get_supported_languages();
		$backend_lang   = get_option( 'frc_backend_language', get_option( 'frc_default_language', 'en' ) );
		$frontend_lang  = get_option( 'frc_frontend_language', 'en' );
		?>
		<!-- Section 1: Backend Language -->
		<h3><?php esc_html_e( 'Backend Language (Admin & Emails)', 'flexi-revive-cart' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Select the language for the plugin admin interface and all sent emails. When you change this language, all admin page content and reminder emails will be sent in the selected language.', 'flexi-revive-cart' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Backend Language', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_backend_language">
						<?php foreach ( $languages as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $backend_lang ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'This controls the language for the entire plugin admin interface (settings, tables, notices) and the language used when sending reminder emails.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
		</table>

		<!-- Section 2: Frontend Language -->
		<h3><?php esc_html_e( 'Frontend Language (Cart/Checkout)', 'flexi-revive-cart' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Select the default language for frontend plugin strings (e.g., popups, notices). Users can override this on the frontend.', 'flexi-revive-cart' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Frontend Language', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_frontend_language">
						<?php foreach ( $languages as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $frontend_lang ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'All frontend strings and texts used by this plugin (e.g., cart popups, exit-intent messages, language switcher) will appear in this language.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Supported Languages', 'flexi-revive-cart' ); ?></h3>
		<table class="widefat striped" style="max-width:500px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'flexi-revive-cart' ); ?></th>
					<th><?php esc_html_e( 'Language', 'flexi-revive-cart' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $languages as $code => $label ) : ?>
				<tr>
					<td><code><?php echo esc_html( $code ); ?></code></td>
					<td><?php echo esc_html( $label ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Translations fallback to English (en) if a translation is missing for the selected language.', 'flexi-revive-cart' ); ?></p>
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
				<th><?php esc_html_e( 'Abandonment Timeout', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_abandonment_timeout" value="<?php echo esc_attr( get_option( 'frc_abandonment_timeout', 60 ) ); ?>" min="1" class="small-text" />
					<?php $this->render_time_unit_select( 'frc_abandonment_timeout_unit', get_option( 'frc_abandonment_timeout_unit', 'minutes' ) ); ?>
					<p class="description"><?php esc_html_e( 'Time after which an inactive cart is considered abandoned.', 'flexi-revive-cart' ); ?></p>
					<?php if ( ! FRC_PRO_ACTIVE ) : ?>
					<p class="description" style="color:#d63638;"><?php esc_html_e( 'Free version: weeks, months, and years require Pro.', 'flexi-revive-cart' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-delete carts after', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_auto_delete_interval" value="<?php echo esc_attr( get_option( 'frc_auto_delete_interval', get_option( 'frc_auto_delete_days', 90 ) ) ); ?>" min="1" class="small-text" />
					<?php $this->render_time_unit_select( 'frc_auto_delete_unit', get_option( 'frc_auto_delete_unit', 'days' ) ); ?>
					<p class="description"><?php esc_html_e( 'Automatically delete abandoned cart data after this period.', 'flexi-revive-cart' ); ?></p>
				</td>
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
		$reminder_interval = (int) get_option( 'frc_reminder_interval', 1 );
		$num_reminders     = (int) get_option( 'frc_num_reminders', 3 );
		$reminder_type     = get_option( 'frc_reminder_type', 'friendly' );

		// In Free version, force type to friendly.
		if ( ! FRC_PRO_ACTIVE ) {
			$reminder_type = 'friendly';
		}

		$type_labels = array(
			'friendly'  => __( 'Friendly Reminder', 'flexi-revive-cart' ),
			'urgency'   => __( 'Urgency Reminder (Pro)', 'flexi-revive-cart' ),
			'incentive' => __( 'Incentive/Discount Reminder (Pro)', 'flexi-revive-cart' ),
		);
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Email Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_email_reminders" value="1" <?php checked( get_option( 'frc_enable_email_reminders', '1' ) ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reminder Type', 'flexi-revive-cart' ); ?></th>
				<td>
					<?php if ( FRC_PRO_ACTIVE ) : ?>
					<select name="frc_reminder_type">
						<?php foreach ( $type_labels as $type_key => $type_label ) : ?>
						<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $reminder_type, $type_key ); ?>>
							<?php echo esc_html( $type_label ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php else : ?>
					<input type="hidden" name="frc_reminder_type" value="friendly" />
					<select disabled>
						<option selected><?php esc_html_e( 'Friendly Reminder', 'flexi-revive-cart' ); ?></option>
						<option disabled><?php esc_html_e( 'Urgency Reminder (Pro)', 'flexi-revive-cart' ); ?></option>
						<option disabled><?php esc_html_e( 'Incentive/Discount Reminder (Pro)', 'flexi-revive-cart' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Upgrade to Pro to unlock Urgency and Incentive/Discount reminder types.', 'flexi-revive-cart' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Number of Reminders', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" id="frc-num-reminders" name="frc_num_reminders" value="<?php echo esc_attr( $num_reminders ); ?>" class="small-text" />
					<?php if ( ! FRC_PRO_ACTIVE ) : ?>
					<p class="description"><?php esc_html_e( 'Free version allows up to 3 friendly reminders. Upgrade to Pro for unlimited reminders.', 'flexi-revive-cart' ); ?></p>
					<?php else : ?>
					<p class="description"><?php esc_html_e( 'Set the number of reminder emails to send per abandoned cart.', 'flexi-revive-cart' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reminder Interval', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_reminder_interval" value="<?php echo esc_attr( $reminder_interval ); ?>" min="1" class="small-text" />
					<?php $this->render_time_unit_select( 'frc_reminder_interval_unit', get_option( 'frc_reminder_interval_unit', 'hours' ) ); ?>
					<p class="description"><?php esc_html_e( 'Time between each reminder email. All reminders are sent at this interval apart.', 'flexi-revive-cart' ); ?></p>
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
			<tr>
				<th><?php esc_html_e( 'Email Subjects', 'flexi-revive-cart' ); ?></th>
				<td>
					<p class="description">
						<?php
						printf(
							/* translators: %s: link to email templates page */
							esc_html__( 'Email subjects are now managed in the %s along with template bodies for multi-language support.', 'flexi-revive-cart' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=frc-email-editor' ) ) . '">' . esc_html__( 'Email Templates page', 'flexi-revive-cart' ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Send Test Email', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="email" id="frc-test-email-to" placeholder="<?php esc_attr_e( 'Email address', 'flexi-revive-cart' ); ?>" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
					<select id="frc-test-email-stage">
						<option value="1"><?php esc_html_e( 'Stage 1 – Friendly', 'flexi-revive-cart' ); ?></option>
						<?php if ( FRC_PRO_ACTIVE ) : ?>
						<option value="2"><?php esc_html_e( 'Stage 2 – Urgency', 'flexi-revive-cart' ); ?></option>
						<option value="3"><?php esc_html_e( 'Stage 3 – Incentive', 'flexi-revive-cart' ); ?></option>
						<?php endif; ?>
					</select>
					<?php
					// Add language dropdown for test email preview.
					$languages    = FRC_Email_Templates::get_supported_languages();
					$preview_lang = get_option( 'frc_backend_language', get_option( 'frc_default_language', 'en' ) );
					?>
					<select id="frc-test-email-lang">
						<?php foreach ( $languages as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $preview_lang ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( ! FRC_PRO_ACTIVE ) : ?>
					<span class="description" style="color:#d63638;"><?php esc_html_e( 'Free version: only friendly reminder test emails are available.', 'flexi-revive-cart' ); ?></span>
					<?php endif; ?>
					<br style="margin-bottom:6px;" />
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
		$discount_type        = get_option( 'frc_discount_type', 'percent' );
		$discount_amount      = get_option( 'frc_discount_percentage', 10 );
		$min_cart_value       = get_option( 'frc_min_cart_value', 0 );
		$expiry_days          = get_option( 'frc_coupon_expiry_days', 7 );
		$usage_limit          = get_option( 'frc_coupon_usage_limit', 1 );
		$exclude_sale         = get_option( 'frc_exclude_sale_items', '0' );
		$exclude_products     = get_option( 'frc_exclude_product_ids', '' );
		$exclude_categories   = get_option( 'frc_exclude_category_ids', '' );
		$coupon_prefix        = get_option( 'frc_coupon_prefix', 'RECOVER' );
		$coupon_suffix        = get_option( 'frc_coupon_suffix', '' );
		$auto_apply           = get_option( 'frc_auto_apply_coupon', '1' );
		$is_locked            = ! FRC_PRO_ACTIVE;
		$table_class          = $is_locked ? 'form-table frc-pro-locked' : 'form-table';
		?>
		<table class="<?php echo esc_attr( $table_class ); ?>">
			<!-- ── Enable / Disable ─────────────────────────────────────── -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Dynamic Coupons', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_enable_auto_discounts" value="1"
							<?php checked( get_option( 'frc_enable_auto_discounts', '0' ) ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Automatically generate a unique coupon for each abandoned cart recovery email.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>

			<!-- ── Coupon Rules ──────────────────────────────────────────── -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Discount Type', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_discount_type" <?php disabled( $is_locked ); ?>>
						<option value="percent" <?php selected( $discount_type, 'percent' ); ?>><?php esc_html_e( 'Percentage Discount (e.g. 10%)', 'flexi-revive-cart' ); ?></option>
						<option value="fixed_cart" <?php selected( $discount_type, 'fixed_cart' ); ?>><?php esc_html_e( 'Fixed Cart Discount (e.g. $5 off)', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Discount Amount', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_discount_percentage"
						value="<?php echo esc_attr( $discount_amount ); ?>"
						min="0" step="0.01" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Enter a percentage (e.g. 10 for 10%) or a fixed amount (e.g. 5 for $5 off) depending on the Discount Type above.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Minimum Cart Value', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_min_cart_value"
						value="<?php echo esc_attr( $min_cart_value ); ?>"
						min="0" step="0.01" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Apply coupon only when the cart total is at or above this amount. Set to 0 to apply to all carts.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Coupon Expiry', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_coupon_expiry_days"
						value="<?php echo esc_attr( $expiry_days ); ?>"
						min="1" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<?php $this->render_time_unit_select( 'frc_coupon_expiry_unit', get_option( 'frc_coupon_expiry_unit', 'days' ), $is_locked ); ?>
					<p class="description"><?php esc_html_e( 'Time from the moment the coupon is generated before it expires.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Usage Limit per Coupon', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_coupon_usage_limit"
						value="<?php echo esc_attr( $usage_limit ); ?>"
						min="1" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'How many times can this coupon be used in total (e.g. 1 for single-use).', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Sale Items', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_exclude_sale_items" value="1"
							<?php checked( $exclude_sale ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Do not apply the coupon to items that are already on sale.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Product IDs', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_exclude_product_ids"
						value="<?php echo esc_attr( $exclude_products ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. 12,45,78', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Comma-separated list of product IDs to exclude from this coupon.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude Category IDs', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_exclude_category_ids"
						value="<?php echo esc_attr( $exclude_categories ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. 5,10', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Comma-separated list of product category IDs to exclude from this coupon.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>

			<!-- ── Coupon Code Format ────────────────────────────────────── -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Coupon Code Prefix', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_coupon_prefix"
						value="<?php echo esc_attr( $coupon_prefix ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'RECOVER', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?> />
					<p class="description">
						<?php esc_html_e( 'Prefix prepended to each auto-generated coupon code (letters, digits and hyphens only). Example: SUMMER2024 produces codes like SUMMER2024-A1B2C3D4.', 'flexi-revive-cart' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Coupon Code Suffix', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_coupon_suffix"
						value="<?php echo esc_attr( $coupon_suffix ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Optional', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Optional suffix appended after the random segment (letters, digits and hyphens only). Leave blank to omit.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Coupon Code Preview', 'flexi-revive-cart' ); ?></th>
				<td>
					<code id="frc-coupon-preview"><?php echo esc_html( $this->build_coupon_preview( $coupon_prefix, $coupon_suffix ) ); ?></code>
					<p class="description"><?php esc_html_e( 'Sample of how a generated code will look. The random segment changes per cart.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>

			<!-- ── Auto-Apply ────────────────────────────────────────────── -->
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-Apply Coupon on Recovery', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_auto_apply_coupon" value="1"
							<?php checked( $auto_apply ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Automatically apply the coupon to the restored cart when a customer clicks the recovery link.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Build a human-readable coupon code preview using the current prefix/suffix.
	 *
	 * @param string $prefix Coupon prefix.
	 * @param string $suffix Coupon suffix.
	 * @return string
	 */
	private function build_coupon_preview( $prefix, $suffix ) {
		// Use 4 random bytes (8 hex chars) – matches the hex-style token used in generate_coupon_code().
		$random = strtoupper( bin2hex( random_bytes( 4 ) ) );
		$parts  = array_filter( array( strtoupper( $prefix ), $random, strtoupper( $suffix ) ) );
		return implode( '-', $parts );
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
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'SMS Template – Stage %d', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td>
					<textarea name="frc_sms_template_<?php echo esc_attr( $i ); ?>" class="large-text" rows="3" <?php disabled( ! FRC_PRO_ACTIVE ); ?>><?php echo esc_textarea( get_option( 'frc_sms_template_' . $i, '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Supports: {user_name}, {cart_total}, {recovery_link}, {store_name}, {discount_code}, {discount_amount}', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<?php endfor; ?>
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
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'Push Title – Stage %d', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td><input type="text" name="frc_push_title_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( get_option( 'frc_push_title_' . $i, __( 'Your cart is waiting!', 'flexi-revive-cart' ) ) ); ?>" class="large-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'Push Message – Stage %d', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td><input type="text" name="frc_push_message_<?php echo esc_attr( $i ); ?>" value="<?php echo esc_attr( get_option( 'frc_push_message_' . $i, __( 'Complete your purchase at {store_name}.', 'flexi-revive-cart' ) ) ); ?>" class="large-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<?php endfor; ?>
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
				<th><?php esc_html_e( 'Popup Delay', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_popup_delay_seconds" value="<?php echo esc_attr( get_option( 'frc_popup_delay_seconds', 30 ) ); ?>" min="1" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> />
					<?php $this->render_time_unit_select( 'frc_popup_delay_unit', get_option( 'frc_popup_delay_unit', 'seconds' ), ! FRC_PRO_ACTIVE ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Message', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_popup_message" value="<?php echo esc_attr( get_option( 'frc_popup_message', __( 'Wait! Don\'t leave your cart behind.', 'flexi-revive-cart' ) ) ); ?>" class="large-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Button Text', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" name="frc_popup_button_text" value="<?php echo esc_attr( get_option( 'frc_popup_button_text', __( 'Save My Cart', 'flexi-revive-cart' ) ) ); ?>" class="regular-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Browse Abandonment Follow-up', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_browse_followup_hours" value="<?php echo esc_attr( get_option( 'frc_browse_followup_hours', 2 ) ); ?>" min="1" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> />
					<?php $this->render_time_unit_select( 'frc_browse_followup_unit', get_option( 'frc_browse_followup_unit', 'hours' ), ! FRC_PRO_ACTIVE ); ?>
					<p class="description"><?php esc_html_e( 'Time after a product page view before sending a browse abandonment follow-up email.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Render WhatsApp Settings tab. */
	private function render_whatsapp_settings() {
		$this->maybe_show_pro_notice();
		?>
		<table class="form-table <?php echo ! FRC_PRO_ACTIVE ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Enable WhatsApp Notifications', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_whatsapp" value="1" <?php checked( get_option( 'frc_enable_whatsapp', '0' ) ); ?> <?php disabled( ! FRC_PRO_ACTIVE ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'WhatsApp Provider', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_whatsapp_provider" <?php disabled( ! FRC_PRO_ACTIVE ); ?>>
						<option value="twilio" <?php selected( get_option( 'frc_whatsapp_provider', 'twilio' ), 'twilio' ); ?>><?php esc_html_e( 'Twilio WhatsApp', 'flexi-revive-cart' ); ?></option>
						<option value="plivo" <?php selected( get_option( 'frc_whatsapp_provider', 'twilio' ), 'plivo' ); ?>><?php esc_html_e( 'Plivo WhatsApp', 'flexi-revive-cart' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Uses the Twilio/Plivo credentials configured in the SMS tab.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'WhatsApp From Number', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_whatsapp_from" value="<?php echo esc_attr( get_option( 'frc_whatsapp_from', '' ) ); ?>" class="regular-text" placeholder="+14155238886" <?php disabled( ! FRC_PRO_ACTIVE ); ?> />
					<p class="description"><?php esc_html_e( 'Your Twilio/Plivo WhatsApp-enabled phone number in E.164 format (e.g. +14155238886). For Twilio sandbox, use the sandbox number.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<tr>
				<th><?php echo esc_html( sprintf( __( 'WhatsApp Template – Stage %d', 'flexi-revive-cart' ), $i ) ); ?></th>
				<td>
					<textarea name="frc_whatsapp_template_<?php echo esc_attr( $i ); ?>" class="large-text" rows="3" <?php disabled( ! FRC_PRO_ACTIVE ); ?>><?php echo esc_textarea( get_option( 'frc_whatsapp_template_' . $i, '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Supports: {user_name}, {cart_total}, {cart_link}, {store_name}, {discount_code}, {discount_amount}, {abandoned_time}', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<?php endfor; ?>
			<tr>
				<th><?php esc_html_e( 'Bulk Campaign Default Message', 'flexi-revive-cart' ); ?></th>
				<td>
					<textarea name="frc_whatsapp_template_bulk" class="large-text" rows="3" <?php disabled( ! FRC_PRO_ACTIVE ); ?>><?php echo esc_textarea( get_option( 'frc_whatsapp_template_bulk', __( 'Hi {user_name}, your cart at {store_name} is waiting! Complete your purchase: {cart_link}', 'flexi-revive-cart' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Default message template used for bulk WhatsApp campaigns.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Render Guest Tracking Settings tab (Pro). */
	private function render_guest_tracking_settings() {
		$this->maybe_show_pro_notice();
		$is_locked          = ! FRC_PRO_ACTIVE;
		$capture_method     = get_option( 'frc_guest_capture_method', 'popup' );
		$popup_timing       = get_option( 'frc_guest_popup_timing', 'exit_intent' );
		$retention_value    = get_option( 'frc_guest_cart_retention', 30 );
		$retention_unit     = get_option( 'frc_guest_cart_retention_unit', 'days' );
		?>
		<table class="form-table <?php echo $is_locked ? 'frc-pro-locked' : ''; ?>">
			<tr>
				<th><?php esc_html_e( 'Track Guest Carts', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_enable_guest_tracking" value="1"
							<?php checked( get_option( 'frc_enable_guest_tracking', '0' ) ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Enable tracking of guest (non-logged-in) user carts.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Capture Method', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_guest_capture_method" <?php disabled( $is_locked ); ?>>
						<option value="popup" <?php selected( $capture_method, 'popup' ); ?>><?php esc_html_e( 'Popup', 'flexi-revive-cart' ); ?></option>
						<option value="checkout_field" <?php selected( $capture_method, 'checkout_field' ); ?>><?php esc_html_e( 'Checkout Field', 'flexi-revive-cart' ); ?></option>
						<option value="force_login" <?php selected( $capture_method, 'force_login' ); ?>><?php esc_html_e( 'Force Login', 'flexi-revive-cart' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'How to capture guest email addresses for cart recovery.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Timing', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_guest_popup_timing" <?php disabled( $is_locked ); ?>>
						<option value="exit_intent" <?php selected( $popup_timing, 'exit_intent' ); ?>><?php esc_html_e( 'Exit-Intent', 'flexi-revive-cart' ); ?></option>
						<option value="add_to_cart" <?php selected( $popup_timing, 'add_to_cart' ); ?>><?php esc_html_e( 'Add-to-Cart', 'flexi-revive-cart' ); ?></option>
						<option value="checkout_page" <?php selected( $popup_timing, 'checkout_page' ); ?>><?php esc_html_e( 'Checkout Page', 'flexi-revive-cart' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'When to display the email capture popup to guest users.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Force Login', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_guest_force_login" value="1"
							<?php checked( get_option( 'frc_guest_force_login', '0' ) ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Redirect guests to login/register before checkout.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Pre-Checkout Email Capture', 'flexi-revive-cart' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="frc_guest_pre_checkout_capture" value="1"
							<?php checked( get_option( 'frc_guest_pre_checkout_capture', '0' ) ); ?>
							<?php disabled( $is_locked ); ?> />
						<?php esc_html_e( 'Show email capture popup before checkout.', 'flexi-revive-cart' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Guest Cart Retention', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_guest_cart_retention"
						value="<?php echo esc_attr( $retention_value ); ?>"
						min="1" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<?php $this->render_time_unit_select( 'frc_guest_cart_retention_unit', $retention_unit, $is_locked ); ?>
					<p class="description"><?php esc_html_e( 'How long to retain guest cart data before automatic deletion.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Exclude by Country', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="text" name="frc_guest_exclude_countries"
						value="<?php echo esc_attr( get_option( 'frc_guest_exclude_countries', '' ) ); ?>"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'e.g. US,GB,DE', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Comma-separated list of country codes to exclude from guest tracking.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Exclude by IP', 'flexi-revive-cart' ); ?></th>
				<td>
					<textarea name="frc_guest_exclude_ips" class="large-text" rows="3"
						placeholder="<?php esc_attr_e( 'One IP address per line', 'flexi-revive-cart' ); ?>"
						<?php disabled( $is_locked ); ?>><?php echo esc_textarea( get_option( 'frc_guest_exclude_ips', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'IP addresses to blacklist from guest tracking (one per line).', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Render Compliance Settings tab. */
	private function render_compliance_settings() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Data Retention', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_data_retention_days" value="<?php echo esc_attr( get_option( 'frc_data_retention_days', 90 ) ); ?>" min="1" class="small-text" />
					<?php $this->render_time_unit_select( 'frc_data_retention_unit', get_option( 'frc_data_retention_unit', 'days' ) ); ?>
					<p class="description"><?php esc_html_e( 'Abandoned cart data older than this period will be automatically deleted.', 'flexi-revive-cart' ); ?></p>
				</td>
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
	 * Render a time unit dropdown selector.
	 *
	 * Pro-only units (weeks, months, years) are shown as disabled options in the Free version.
	 *
	 * @param string $name         The option name for the select element.
	 * @param string $current_unit Current selected unit value.
	 * @param bool   $disabled     Whether the entire select is disabled.
	 */
	private function render_time_unit_select( $name, $current_unit, $disabled = false ) {
		$all_units  = FRC_Helpers::get_all_time_units();
		$pro_units  = FRC_Helpers::get_pro_time_units();
		?>
		<select name="<?php echo esc_attr( $name ); ?>" <?php disabled( $disabled ); ?>>
			<?php foreach ( $all_units as $unit_key => $unit_label ) :
				$is_pro_unit = in_array( $unit_key, $pro_units, true );
				$option_disabled = ( ! FRC_PRO_ACTIVE && $is_pro_unit );
				$label = $is_pro_unit && ! FRC_PRO_ACTIVE ? $unit_label . ' (Pro)' : $unit_label;
			?>
			<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $current_unit, $unit_key ); ?> <?php disabled( $option_disabled ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
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
