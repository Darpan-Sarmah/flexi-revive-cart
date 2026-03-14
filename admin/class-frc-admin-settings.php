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

		// Language.
		register_setting( 'frc_language', 'frc_backend_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );
		register_setting( 'frc_language', 'frc_frontend_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );
		// Legacy settings kept for backward compatibility.
		register_setting( 'frc_language', 'frc_admin_preview_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => '' ) );
		register_setting( 'frc_language', 'frc_default_language', array( 'sanitize_callback' => array( $this, 'sanitize_language_code' ), 'default' => 'en' ) );

		// Email.
		register_setting( 'frc_email', 'frc_enable_email_reminders', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
		register_setting( 'frc_email', 'frc_num_reminders', array( 'sanitize_callback' => array( $this, 'sanitize_num_reminders' ), 'default' => 3 ) );
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

		// Compliance.
		register_setting( 'frc_compliance', 'frc_data_retention_days', array( 'sanitize_callback' => 'absint', 'default' => 90 ) );
		register_setting( 'frc_compliance', 'frc_data_retention_unit', array( 'sanitize_callback' => array( 'FRC_Helpers', 'sanitize_time_unit' ), 'default' => 'days' ) );
		register_setting( 'frc_compliance', 'frc_optout_page_url', array( 'sanitize_callback' => 'esc_url_raw' ) );

		/**
		 * Fires after core settings are registered. Pro add-ons should use this
		 * hook to call register_setting() for their own option groups.
		 */
		do_action( 'frc_register_settings' );
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
		$allowed = array( 'friendly' );
		/**
		 * Filters the allowed reminder type values for sanitization.
		 *
		 * Pro can add 'urgency', 'incentive', etc.
		 *
		 * @param array $allowed List of allowed type strings.
		 */
		$allowed = apply_filters( 'frc_allowed_reminder_types', $allowed );
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
		$allowed = array( 'friendly' );
		/** This filter is documented in sanitize_reminder_types(). */
		$allowed = apply_filters( 'frc_allowed_reminder_types', $allowed );
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
	 * Sanitize the number of reminders.
	 *
	 * Free version is capped at 3 reminders. Pro can override via filter.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	public function sanitize_num_reminders( $value ) {
		$value = absint( $value );
		if ( $value < 1 ) {
			$value = 1;
		}
		/**
		 * Filters the maximum number of reminders allowed.
		 *
		 * @param int $max_reminders Default maximum (3 in Free).
		 */
		$max = apply_filters( 'frc_max_reminders', 3 );
		if ( $value > $max ) {
			$value = $max;
		}
		return $value;
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
					'compliance' => __( 'Compliance', 'flexi-revive-cart' ),
				);

				/**
				 * Filters the settings page tabs.
				 *
				 * Pro add-ons can add their own tabs (e.g., Discounts, SMS, WhatsApp).
				 *
				 * @param array $tabs Associative array of tab_id => tab_label.
				 */
				$tabs = apply_filters( 'frc_admin_tabs', $tabs );

				// Pro teaser tabs – shown only when the Pro add-on is not active.
				$pro_teaser_tab_ids = array();
				if ( ! FRC_PRO_ACTIVE ) {
					$pro_teaser_tabs = array(
						'discounts'      => __( 'Discounts', 'flexi-revive-cart' ),
						'sms'            => __( 'SMS', 'flexi-revive-cart' ),
						'whatsapp'       => __( 'WhatsApp', 'flexi-revive-cart' ),
						'push'           => __( 'Push Notifications', 'flexi-revive-cart' ),
						'popups'         => __( 'Popups', 'flexi-revive-cart' ),
						'guest_tracking' => __( 'Guest Tracking', 'flexi-revive-cart' ),
						'ab_testing'     => __( 'A/B Testing', 'flexi-revive-cart' ),
					);
					$pro_teaser_tab_ids = array_keys( $pro_teaser_tabs );
					$tabs = array_merge( $tabs, $pro_teaser_tabs );
				}

				foreach ( $tabs as $tab_id => $tab_label ) {
					$is_pro_teaser = in_array( $tab_id, $pro_teaser_tab_ids, true );
					$class         = ( $tab_id === $active_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
					if ( $is_pro_teaser ) {
						$class .= ' frc-pro-tab';
					}
					echo '<a href="' . esc_url( admin_url( 'admin.php?page=frc-settings&tab=' . $tab_id ) ) . '" class="' . esc_attr( $class ) . '">'
						. esc_html( $tab_label )
						. ( $is_pro_teaser ? ' <span class="frc-pro-badge">PRO</span>' : '' )
						. '</a>';
				}
				?>
			</nav>

			<?php
			$is_pro_teaser_tab = in_array( $active_tab, $pro_teaser_tab_ids, true );
			if ( $is_pro_teaser_tab ) :
			?>
			<div class="frc-settings-form">
				<?php $this->render_pro_teaser_tab( $active_tab ); ?>
			</div>
			<?php else : ?>
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
					case 'compliance':
						settings_fields( 'frc_compliance' );
						$this->render_compliance_settings();
						break;
					default:
						/**
						 * Fires when rendering a custom settings tab added via frc_admin_tabs.
						 *
						 * Pro add-ons should hook into frc_render_settings_tab_{tab_id}
						 * to output their settings_fields() call and form fields.
						 *
						 * @param string $active_tab The current tab ID.
						 */
						do_action( 'frc_render_settings_tab_' . $active_tab );
						break;
				}

				submit_button();
				?>
			</form>
			<?php endif; ?>
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
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-delete carts after', 'flexi-revive-cart' ); ?></th>
				<td>
					<?php $auto_delete_fallback = get_option( 'frc_auto_delete_days', 90 ); ?>
					<input type="number" name="frc_auto_delete_interval" value="<?php echo esc_attr( get_option( 'frc_auto_delete_interval', $auto_delete_fallback ) ); ?>" min="1" class="small-text" />
					<?php $this->render_time_unit_select( 'frc_auto_delete_unit', get_option( 'frc_auto_delete_unit', 'days' ) ); ?>
					<p class="description"><?php esc_html_e( 'Automatically delete abandoned cart data after this period.', 'flexi-revive-cart' ); ?></p>
				</td>
			</tr>
			<?php if ( ! FRC_PRO_ACTIVE ) : ?>
			<tr>
				<th><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></th>
				<td>
					<p class="description"><a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank"><?php esc_html_e( 'Install the Flexi Revive Cart Pro add-on for SMS, WhatsApp, push notifications, exit-intent popups, dynamic discounts, A/B testing, and more.', 'flexi-revive-cart' ); ?></a></p>
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

		$type_labels = array(
			'friendly' => __( 'Friendly Reminder', 'flexi-revive-cart' ),
		);

		/**
		 * Filters the available reminder types.
		 *
		 * Pro can add urgency, incentive, and other custom types.
		 *
		 * @param array $type_labels Associative array of type_key => label.
		 */
		$type_labels = apply_filters( 'frc_reminder_types', $type_labels );

		// If current type is not in available labels, reset to friendly.
		if ( ! isset( $type_labels[ $reminder_type ] ) ) {
			$reminder_type = 'friendly';
		}

		/** This filter is documented in class-frc-admin-settings.php */
		$max_reminders = apply_filters( 'frc_max_reminders', 3 );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Email Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" name="frc_enable_email_reminders" value="1" <?php checked( get_option( 'frc_enable_email_reminders', '1' ) ); ?> /></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reminder Type', 'flexi-revive-cart' ); ?></th>
				<td>
					<?php if ( count( $type_labels ) > 1 ) : ?>
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
					</select>
					<p class="description"><?php esc_html_e( 'Install the Pro add-on to unlock additional reminder types (Urgency, Incentive/Discount).', 'flexi-revive-cart' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Number of Reminders', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="number" id="frc-num-reminders" name="frc_num_reminders" value="<?php echo esc_attr( $num_reminders ); ?>" min="1" max="<?php echo esc_attr( $max_reminders ); ?>" class="small-text" />
					<?php if ( 3 === $max_reminders && ! FRC_PRO_ACTIVE ) : ?>
					<p class="description"><?php esc_html_e( 'Free version allows up to 3 friendly reminders. Install the Pro add-on for unlimited reminders.', 'flexi-revive-cart' ); ?></p>
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
		</table>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Send Test Email', 'flexi-revive-cart' ); ?></th>
				<td>
					<input type="email" id="frc-test-email-to" placeholder="<?php esc_attr_e( 'Email address', 'flexi-revive-cart' ); ?>" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
					<select id="frc-test-email-stage">
						<option value="1"><?php esc_html_e( 'Stage 1 – Friendly', 'flexi-revive-cart' ); ?></option>
						<?php
						/**
						 * Fires to allow Pro to add additional test email stage options.
						 */
						do_action( 'frc_test_email_stages' );
						?>
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
					<span class="description" style="color:#d63638;"><?php esc_html_e( 'Install the Pro add-on to test urgency and incentive emails.', 'flexi-revive-cart' ); ?></span>
					<?php endif; ?>
					<br style="margin-bottom:6px;" />
					<button type="button" id="frc-send-test-email" class="button"><?php esc_html_e( 'Send Test', 'flexi-revive-cart' ); ?></button>
					<span id="frc-test-email-result"></span>
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
	 * @param string $name         The option name for the select element.
	 * @param string $current_unit Current selected unit value.
	 * @param bool   $disabled     Whether the entire select is disabled.
	 */
	private function render_time_unit_select( $name, $current_unit, $disabled = false ) {
		/**
		 * Filters the available time units.
		 *
		 * @param array $units Associative array of unit_key => label.
		 */
		$all_units = apply_filters( 'frc_time_units', FRC_Helpers::get_all_time_units() );
		?>
		<select name="<?php echo esc_attr( $name ); ?>" <?php disabled( $disabled ); ?>>
			<?php foreach ( $all_units as $unit_key => $unit_label ) : ?>
			<option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $current_unit, $unit_key ); ?>>
				<?php echo esc_html( $unit_label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/* ===================================================================
	 * Pro Teaser Tabs – UI-only placeholders with upsell messages.
	 * No Pro logic is included; these are purely visual teasers.
	 * =================================================================== */

	/**
	 * Render the Pro teaser content for a given tab.
	 *
	 * @param string $tab_id The Pro teaser tab ID.
	 */
	private function render_pro_teaser_tab( $tab_id ) {
		$this->render_pro_upsell_banner( $tab_id );

		echo '<div class="frc-pro-locked">';

		switch ( $tab_id ) {
			case 'discounts':
				$this->render_discounts_teaser();
				break;
			case 'sms':
				$this->render_sms_teaser();
				break;
			case 'whatsapp':
				$this->render_whatsapp_teaser();
				break;
			case 'push':
				$this->render_push_teaser();
				break;
			case 'popups':
				$this->render_popups_teaser();
				break;
			case 'guest_tracking':
				$this->render_guest_tracking_teaser();
				break;
			case 'ab_testing':
				$this->render_ab_testing_teaser();
				break;
		}

		echo '</div>';
	}

	/**
	 * Render the Pro upsell banner shown above teaser content.
	 *
	 * @param string $tab_id The Pro teaser tab ID.
	 */
	private function render_pro_upsell_banner( $tab_id ) {
		$feature_names = array(
			'discounts'      => __( 'Dynamic Discounts & Coupons', 'flexi-revive-cart' ),
			'sms'            => __( 'SMS Reminders', 'flexi-revive-cart' ),
			'whatsapp'       => __( 'WhatsApp Reminders', 'flexi-revive-cart' ),
			'push'           => __( 'Push Notifications', 'flexi-revive-cart' ),
			'popups'         => __( 'Exit-Intent Popups', 'flexi-revive-cart' ),
			'guest_tracking' => __( 'Advanced Guest Tracking', 'flexi-revive-cart' ),
			'ab_testing'     => __( 'A/B Testing', 'flexi-revive-cart' ),
		);
		$feature = isset( $feature_names[ $tab_id ] ) ? $feature_names[ $tab_id ] : '';
		?>
		<div class="frc-pro-upsell">
			<span class="dashicons dashicons-lock"></span>
			<div>
				<strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: Pro feature name */
						__( '%s is a Pro Feature', 'flexi-revive-cart' ),
						$feature
					)
				);
				?>
				</strong>
				<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: Pro feature name */
						__( 'Upgrade to the Pro add-on to unlock %s and more advanced cart recovery features.', 'flexi-revive-cart' ),
						$feature
					)
				);
				?>
				</p>
				<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'flexi-revive-cart' ); ?></a>
			</div>
		</div>
		<?php
	}

	/** Render Discounts teaser fields. */
	private function render_discounts_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Dynamic Coupons', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Automatically generate unique coupon codes for recovery emails', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Discount Type', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Percentage', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Discount Amount', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" disabled value="10" class="small-text" /> <span class="description">%</span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Coupon Expiry', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" disabled value="72" class="small-text" /> <span class="description"><?php esc_html_e( 'hours', 'flexi-revive-cart' ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Minimum Cart Value', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" disabled value="0" class="small-text" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Apply to Reminder', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Reminder 3 (Incentive)', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Render SMS teaser fields. */
	private function render_sms_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable SMS Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Send abandoned cart reminders via SMS', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'SMS Provider', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Twilio', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Account SID / API Key', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auth Token / API Secret', 'flexi-revive-cart' ); ?></th>
				<td><input type="password" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'From Number', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="+1234567890" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'SMS Template', 'flexi-revive-cart' ); ?></th>
				<td><textarea disabled class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Hi {user_name}, you left items in your cart at {store_name}. Complete your order: {recovery_link}', 'flexi-revive-cart' ); ?>"></textarea></td>
			</tr>
		</table>
		<?php
	}

	/** Render WhatsApp teaser fields. */
	private function render_whatsapp_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable WhatsApp Reminders', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Send abandoned cart reminders via WhatsApp', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'WhatsApp Business API Provider', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Official Cloud API', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Phone Number ID', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Access Token', 'flexi-revive-cart' ); ?></th>
				<td><input type="password" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Template Name', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="abandoned_cart_reminder" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Preview', 'flexi-revive-cart' ); ?></th>
				<td><textarea disabled class="large-text" rows="3" placeholder="<?php esc_attr_e( 'Hi {user_name}, your cart at {store_name} is waiting! Tap here to complete your purchase: {recovery_link}', 'flexi-revive-cart' ); ?>"></textarea></td>
			</tr>
		</table>
		<?php
	}

	/** Render Push Notifications teaser fields. */
	private function render_push_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Push Notifications', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Send abandoned cart reminders via browser push notifications', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Push Provider', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'OneSignal', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'App ID', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'REST API Key', 'flexi-revive-cart' ); ?></th>
				<td><input type="password" disabled class="regular-text" placeholder="••••••••" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notification Title', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'You left items in your cart!', 'flexi-revive-cart' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notification Message', 'flexi-revive-cart' ); ?></th>
				<td><textarea disabled class="large-text" rows="2" placeholder="<?php esc_attr_e( 'Hi {user_name}, complete your purchase at {store_name} before your cart expires.', 'flexi-revive-cart' ); ?>"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Notification Icon URL', 'flexi-revive-cart' ); ?></th>
				<td><input type="url" disabled class="regular-text" placeholder="https://example.com/icon.png" /></td>
			</tr>
		</table>
		<?php
	}

	/** Render Popups teaser fields. */
	private function render_popups_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Exit-Intent Popups', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Show a popup when a user is about to leave with items in their cart', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Trigger', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Exit Intent', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Display Delay', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" disabled value="0" class="small-text" /> <span class="description"><?php esc_html_e( 'seconds', 'flexi-revive-cart' ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Headline', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'Wait! Don\'t forget your items!', 'flexi-revive-cart' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Message', 'flexi-revive-cart' ); ?></th>
				<td><textarea disabled class="large-text" rows="2" placeholder="<?php esc_attr_e( 'Complete your purchase now and enjoy free shipping on orders over $50.', 'flexi-revive-cart' ); ?>"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Show On Pages', 'flexi-revive-cart' ); ?></th>
				<td>
					<label><input type="checkbox" disabled checked /> <?php esc_html_e( 'Cart', 'flexi-revive-cart' ); ?></label><br>
					<label><input type="checkbox" disabled checked /> <?php esc_html_e( 'Checkout', 'flexi-revive-cart' ); ?></label><br>
					<label><input type="checkbox" disabled /> <?php esc_html_e( 'Product pages', 'flexi-revive-cart' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Include Discount', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Offer a discount code in the popup', 'flexi-revive-cart' ); ?></label></td>
			</tr>
		</table>
		<?php
	}

	/** Render Guest Tracking teaser fields. */
	private function render_guest_tracking_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Advanced Guest Tracking', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Capture guest emails before checkout for cart recovery', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email Capture Method', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Add to Cart Popup', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Popup Timing', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Immediately after add to cart', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Consent Text', 'flexi-revive-cart' ); ?></th>
				<td><textarea disabled class="large-text" rows="2" placeholder="<?php esc_attr_e( 'By providing your email, you agree to receive cart reminder emails. You can unsubscribe at any time.', 'flexi-revive-cart' ); ?>"></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-detect Billing Email', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled checked /> <?php esc_html_e( 'Capture email as it is typed on the checkout page', 'flexi-revive-cart' ); ?></label></td>
			</tr>
		</table>
		<?php
	}

	/** Render A/B Testing teaser fields. */
	private function render_ab_testing_teaser() {
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable A/B Testing', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled /> <?php esc_html_e( 'Automatically split test email subject lines and templates', 'flexi-revive-cart' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Test Metric', 'flexi-revive-cart' ); ?></th>
				<td>
					<select disabled>
						<option><?php esc_html_e( 'Open Rate', 'flexi-revive-cart' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Minimum Sample Size', 'flexi-revive-cart' ); ?></th>
				<td><input type="number" disabled value="100" class="small-text" /> <span class="description"><?php esc_html_e( 'emails per variant', 'flexi-revive-cart' ); ?></span></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Variant A – Subject', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'You left something behind!', 'flexi-revive-cart' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Variant B – Subject', 'flexi-revive-cart' ); ?></th>
				<td><input type="text" disabled class="regular-text" placeholder="<?php esc_attr_e( 'Your cart misses you 🛒', 'flexi-revive-cart' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Auto-select Winner', 'flexi-revive-cart' ); ?></th>
				<td><label><input type="checkbox" disabled checked /> <?php esc_html_e( 'Automatically use the winning variant after the test completes', 'flexi-revive-cart' ); ?></label></td>
			</tr>
		</table>
		<?php
	}
}
