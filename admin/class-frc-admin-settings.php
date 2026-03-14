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

		// Language.
		register_setting( 'frc_language', 'frc_default_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );
		register_setting( 'frc_language', 'frc_admin_preview_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => '' ) );

		// Email.
		register_setting( 'frc_email', 'frc_enable_email_reminders', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_email', 'frc_num_reminders', array( 'sanitize_callback' => 'absint', 'default' => 3 ) );
		register_setting( 'frc_email', 'frc_reminder_intervals', array( 'sanitize_callback' => array( $this, 'sanitize_intervals' ) ) );
		register_setting( 'frc_email', 'frc_from_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_email', 'frc_from_email', array( 'sanitize_callback' => 'sanitize_email' ) );
		register_setting( 'frc_email', 'frc_email_subjects', array( 'sanitize_callback' => array( $this, 'sanitize_subjects' ) ) );

		// Discount (Pro).
		register_setting( 'frc_discount', 'frc_enable_auto_discounts', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_discount_type', array( 'sanitize_callback' => array( $this, 'sanitize_discount_type' ), 'default' => 'percent' ) );
		register_setting( 'frc_discount', 'frc_discount_percentage', array( 'sanitize_callback' => array( $this, 'sanitize_positive_float' ), 'default' => 10 ) );
		register_setting( 'frc_discount', 'frc_min_cart_value', array( 'sanitize_callback' => array( $this, 'sanitize_positive_float' ), 'default' => 0 ) );
		register_setting( 'frc_discount', 'frc_coupon_expiry_days', array( 'sanitize_callback' => 'absint', 'default' => 7 ) );
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
		register_setting( 'frc_popup', 'frc_popup_message', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popup', 'frc_popup_button_text', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'frc_popup', 'frc_browse_followup_hours', array( 'sanitize_callback' => 'absint', 'default' => 2 ) );

		// Compliance.
		register_setting( 'frc_compliance', 'frc_data_retention_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
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
					'general'    => __( 'General', 'flexi-revive-cart' ),
					'language'   => __( 'Language', 'flexi-revive-cart' ),
					'email'      => __( 'Email', 'flexi-revive-cart' ),
					'discount'   => FRC_PRO_ACTIVE ? __( 'Discounts', 'flexi-revive-cart' ) : __( 'Discounts (Pro)', 'flexi-revive-cart' ),
					'sms'        => FRC_PRO_ACTIVE ? __( 'SMS', 'flexi-revive-cart' ) : __( 'SMS (Pro)', 'flexi-revive-cart' ),
					'whatsapp'   => FRC_PRO_ACTIVE ? __( 'WhatsApp', 'flexi-revive-cart' ) : __( 'WhatsApp (Pro)', 'flexi-revive-cart' ),
					'push'       => FRC_PRO_ACTIVE ? __( 'Push', 'flexi-revive-cart' ) : __( 'Push (Pro)', 'flexi-revive-cart' ),
					'popup'      => FRC_PRO_ACTIVE ? __( 'Popups', 'flexi-revive-cart' ) : __( 'Popups (Pro)', 'flexi-revive-cart' ),
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
		$languages    = FRC_Email_Templates::get_supported_languages();
		$default_lang = get_option( 'frc_default_language', 'en' );
		$preview_lang = get_option( 'frc_admin_preview_language', '' );
		?>
		<h3><?php esc_html_e( 'Language Settings', 'flexi-revive-cart' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Configure the default language for new users/guests and the admin preview language for testing emails and templates.', 'flexi-revive-cart' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default Language', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_default_language">
						<?php foreach ( $languages as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $default_lang ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Default language for new users and guests. Email templates and frontend strings will use this language unless the user has selected a different one.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Admin Preview Language', 'flexi-revive-cart' ); ?></th>
				<td>
					<select name="frc_admin_preview_language">
						<option value=""><?php esc_html_e( '— Use default language —', 'flexi-revive-cart' ); ?></option>
						<?php foreach ( $languages as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $preview_lang ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Override the language for admin previews and test emails. This allows you to preview how emails look in different languages without changing the default language.', 'flexi-revive-cart' ); ?></p>
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
		$intervals     = get_option( 'frc_reminder_intervals', array( 1, 6, 24 ) );
		$num_reminders = (int) get_option( 'frc_num_reminders', 3 );
		$max_reminders = FRC_PRO_ACTIVE ? 10 : 1;
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Email Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_email_reminders" value="1" <?php checked( get_option( 'frc_enable_email_reminders', '1' ) ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Number of Reminders', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" id="frc-num-reminders" name="frc_num_reminders" value="<?php echo esc_attr( $num_reminders ); ?>" min="1" max="<?php echo esc_attr( $max_reminders ); ?>" class="small-text" />
					<?php if ( ! FRC_PRO_ACTIVE ) : ?>
					<p class="description"><?php esc_html_e( 'Free version is limited to 1 friendly reminder. Upgrade to Pro for urgency/incentive emails and up to 10 reminders.', 'flexi-revive-cart' ); ?></p>
					<?php else : ?>
					<p class="description"><?php esc_html_e( 'Set up to 10 reminder emails. Each reminder has its own delay interval below.', 'flexi-revive-cart' ); ?></p>
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

		<h3><?php esc_html_e( 'Reminder Intervals', 'flexi-revive-cart' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Configure the delay (in hours) after cart abandonment for each reminder. Add more reminders by increasing the "Number of Reminders" above.', 'flexi-revive-cart' ); ?></p>

		<table class="form-table" id="frc-reminder-intervals">
			<?php
			// Default intervals for stages beyond 3.
			$default_intervals = array( 1, 6, 24, 48, 72, 96, 120, 144, 168, 192 );
			$display_count     = max( $num_reminders, count( $intervals ) );
			$display_count     = min( $display_count, $max_reminders );

			for ( $i = 1; $i <= $display_count; $i++ ) :
				if ( isset( $intervals[ $i - 1 ] ) ) {
					$interval_val = $intervals[ $i - 1 ];
				} elseif ( isset( $default_intervals[ $i - 1 ] ) ) {
					$interval_val = $default_intervals[ $i - 1 ];
				} else {
					$interval_val = $i * 24;
				}
				$stage_label  = '';
				if ( 1 === $i ) {
					$stage_label = __( '(Friendly Reminder)', 'flexi-revive-cart' );
				} elseif ( 2 === $i ) {
					$stage_label = __( '(Urgency Reminder)', 'flexi-revive-cart' );
				} elseif ( 3 === $i ) {
					$stage_label = __( '(Incentive/Discount)', 'flexi-revive-cart' );
				}
			?>
			<tr class="frc-reminder-row" data-reminder="<?php echo esc_attr( $i ); ?>">
				<th>
					<?php
					echo esc_html( sprintf(
						/* translators: %d: reminder number */
						__( 'Reminder %d – Send After (hours)', 'flexi-revive-cart' ),
						$i
					) );
					if ( $stage_label ) {
						echo ' <em>' . esc_html( $stage_label ) . '</em>';
					}
					?>
				</th>
				<td><input type="number" name="frc_reminder_intervals[<?php echo esc_attr( $i - 1 ); ?>]" value="<?php echo esc_attr( $interval_val ); ?>" min="1" class="small-text" /></td>
			</tr>
			<?php endfor; ?>
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
					$preview_lang = get_option( 'frc_admin_preview_language', '' );
					if ( empty( $preview_lang ) ) {
						$preview_lang = get_option( 'frc_default_language', 'en' );
					}
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
				<th scope="row"><?php esc_html_e( 'Coupon Expiry (days)', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_coupon_expiry_days"
						value="<?php echo esc_attr( $expiry_days ); ?>"
						min="1" class="small-text"
						<?php disabled( $is_locked ); ?> />
					<p class="description"><?php esc_html_e( 'Number of days from the moment the coupon is generated before it expires.', 'flexi-revive-cart' ); ?></p>
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
			<tr>
				<th><?php esc_html_e( 'Browse Abandonment Follow-up (hours)', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" name="frc_browse_followup_hours" value="<?php echo esc_attr( get_option( 'frc_browse_followup_hours', 2 ) ); ?>" min="1" max="72" class="small-text" <?php disabled( ! FRC_PRO_ACTIVE ); ?> />
					<p class="description"><?php esc_html_e( 'Hours after a product page view before sending a browse abandonment follow-up email.', 'flexi-revive-cart' ); ?></p>
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
