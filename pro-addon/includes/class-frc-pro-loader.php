<?php
/**
 * Pro add-on loader – registers all Pro feature modules via hooks.
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Loader
 *
 * Bootstraps all Pro modules and wires them into the free version's hooks.
 */
class FRC_Pro_Loader {

	/**
	 * Constructor – load dependencies and register all hooks.
	 */
	public function __construct() {
		$this->maybe_upgrade_db();
		$this->load_dependencies();
		$this->register_hooks();
	}

	/**
	 * Load all Pro module files.
	 */
	private function load_dependencies() {
		$dir = FRC_PRO_PLUGIN_DIR . 'includes/';

		require_once $dir . 'class-frc-pro-activator.php';
		require_once $dir . 'class-frc-pro-discount.php';
		require_once $dir . 'class-frc-pro-sms.php';
		require_once $dir . 'class-frc-pro-whatsapp.php';
		require_once $dir . 'class-frc-pro-push.php';
		require_once $dir . 'class-frc-pro-ab-testing.php';
		require_once $dir . 'class-frc-pro-guest-capture.php';
		require_once $dir . 'class-frc-pro-browse.php';
		require_once $dir . 'class-frc-pro-rest-api.php';
		require_once $dir . 'class-frc-pro-export.php';

		if ( is_admin() ) {
			$admin_dir = FRC_PRO_PLUGIN_DIR . 'admin/';
			require_once $admin_dir . 'class-frc-pro-admin.php';
			require_once $admin_dir . 'class-frc-pro-admin-settings.php';
			require_once $admin_dir . 'class-frc-pro-admin-analytics.php';
			require_once $admin_dir . 'class-frc-pro-admin-whatsapp.php';
			require_once $admin_dir . 'class-frc-pro-admin-ab-results.php';
		}
	}

	/**
	 * Register all Pro hooks with the free version's extension points.
	 */
	private function register_hooks() {
		// ── Email Templates ───────────────────────────────────────────────
		add_filter( 'frc_email_templates', array( $this, 'add_email_templates' ) );
		add_filter( 'frc_template_type_map', array( $this, 'add_template_type_map' ) );
		add_filter( 'frc_email_template_tabs', array( $this, 'add_email_template_tabs' ) );
		add_filter( 'frc_reminder_types', array( $this, 'add_reminder_types' ) );
		add_filter( 'frc_allowed_reminder_types', array( $this, 'add_allowed_reminder_types' ) );

		// ── Reminder Limits ───────────────────────────────────────────────
		add_filter( 'frc_max_reminders', array( $this, 'increase_max_reminders' ) );

		// ── Reminder Type per Stage ───────────────────────────────────────
		add_filter( 'frc_reminder_type_for_cart', array( $this, 'reminder_type_for_cart' ), 10, 3 );

		// ── Discount Injection ────────────────────────────────────────────
		$discount = new FRC_Pro_Discount();
		add_filter( 'frc_email_discount', array( $discount, 'inject_discount' ), 10, 4 );

		// ── Placeholder Management ────────────────────────────────────────
		add_filter( 'frc_pro_only_placeholders', '__return_empty_array' );
		add_filter( 'frc_allowed_placeholders', array( $this, 'add_allowed_placeholders' ), 10, 2 );
		add_filter( 'frc_email_template_variables', array( $this, 'add_template_variables' ) );

		// ── Test Email Stages ─────────────────────────────────────────────
		add_filter( 'frc_test_email_allowed_stages', array( $this, 'add_test_email_stages' ) );
		add_action( 'frc_test_email_stages', array( $this, 'render_test_email_stages' ) );

		// ── Multi-Channel Dispatch ────────────────────────────────────────
		$sms = new FRC_Pro_SMS();
		$wa  = new FRC_Pro_WhatsApp();
		$push = new FRC_Pro_Push();
		add_action( 'frc_dispatch_reminder', array( $sms, 'send_sms' ), 10, 2 );
		add_action( 'frc_dispatch_reminder', array( $wa, 'send_whatsapp' ), 10, 2 );
		add_action( 'frc_dispatch_reminder', array( $push, 'send_push' ), 10, 2 );

		// ── A/B Testing ───────────────────────────────────────────────────
		$ab = new FRC_Pro_AB_Testing();
		add_filter( 'frc_email_subject', array( $ab, 'apply_ab_test' ), 10, 4 );

		// ── Guest Capture ─────────────────────────────────────────────────
		new FRC_Pro_Guest_Capture();

		// ── Browse Abandonment ────────────────────────────────────────────
		new FRC_Pro_Browse();

		// ── REST API ──────────────────────────────────────────────────────
		new FRC_Pro_REST_API();

		// ── CSV Export ────────────────────────────────────────────────────
		$export = new FRC_Pro_Export();
		add_action( 'admin_init', array( $export, 'handle_export_request' ) );

		// ── Admin Pages & Settings ────────────────────────────────────────
		if ( is_admin() ) {
			$admin    = new FRC_Pro_Admin();
			$settings = new FRC_Pro_Admin_Settings();

			add_action( 'frc_admin_menu', array( $admin, 'add_pro_menus' ) );
			add_filter( 'frc_admin_tabs', array( $settings, 'add_settings_tabs' ) );
			add_action( 'frc_register_settings', array( $settings, 'register_settings' ) );
			add_action( 'frc_admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
			add_filter( 'frc_admin_page_hooks', array( $admin, 'extend_page_hooks' ) );
		}

		// ── Cart Recovery ─────────────────────────────────────────────────
		add_action( 'frc_cart_restored', array( $discount, 'auto_apply_coupon' ) );

		// ── Time Units ────────────────────────────────────────────────────
		add_filter( 'frc_time_units', array( $this, 'add_time_units' ) );
	}

	/**
	 * Add Pro email templates (reminder-2, reminder-3).
	 *
	 * @param array $templates Existing templates.
	 * @return array
	 */
	public function add_email_templates( $templates ) {
		$templates['reminder-2'] = array(
			'name'        => __( 'Urgency Reminder', 'flexi-revive-cart-pro' ),
			'description' => __( 'Creates urgency with low-stock warnings and time pressure.', 'flexi-revive-cart-pro' ),
			'file'        => FRC_PRO_PLUGIN_DIR . 'templates/emails/reminder-2.php',
		);
		$templates['reminder-3'] = array(
			'name'        => __( 'Incentive / Discount', 'flexi-revive-cart-pro' ),
			'description' => __( 'Offers a discount code to motivate the purchase.', 'flexi-revive-cart-pro' ),
			'file'        => FRC_PRO_PLUGIN_DIR . 'templates/emails/reminder-3.php',
		);
		return $templates;
	}

	/**
	 * Map reminder types to template IDs.
	 *
	 * @param array $map Existing type → template map.
	 * @return array
	 */
	public function add_template_type_map( $map ) {
		$map['urgency']   = 'reminder-2';
		$map['incentive'] = 'reminder-3';
		return $map;
	}

	/**
	 * Show Pro templates as editable tabs in the email editor.
	 *
	 * @param array $tabs Available template tab IDs.
	 * @return array
	 */
	public function add_email_template_tabs( $tabs ) {
		$tabs[] = 'reminder-2';
		$tabs[] = 'reminder-3';
		return $tabs;
	}

	/**
	 * Add Pro reminder types to the dropdown.
	 *
	 * @param array $types Existing types.
	 * @return array
	 */
	public function add_reminder_types( $types ) {
		$types['urgency']   = __( 'Urgency Reminder', 'flexi-revive-cart-pro' );
		$types['incentive'] = __( 'Incentive / Discount', 'flexi-revive-cart-pro' );
		return $types;
	}

	/**
	 * Allow Pro reminder types in sanitization.
	 *
	 * @param array $allowed Allowed types.
	 * @return array
	 */
	public function add_allowed_reminder_types( $allowed ) {
		$allowed[] = 'urgency';
		$allowed[] = 'incentive';
		return $allowed;
	}

	/**
	 * Increase max reminders to 10 for Pro.
	 *
	 * @param int $max Current max.
	 * @return int
	 */
	public function increase_max_reminders( $max ) {
		return 10;
	}

	/**
	 * Determine reminder type for a given cart and stage.
	 *
	 * Stage 1: friendly, Stage 2: urgency, Stage 3+: incentive
	 *
	 * @param string $type  Current type.
	 * @param int    $stage Reminder stage.
	 * @param object $cart  Cart row.
	 * @return string
	 */
	public function reminder_type_for_cart( $type, $stage, $cart ) {
		$configured = get_option( 'frc_reminder_type', 'friendly' );

		if ( 'friendly' === $configured ) {
			// Auto-escalate through stages.
			if ( $stage >= 3 ) {
				return 'incentive';
			}
			if ( $stage >= 2 ) {
				return 'urgency';
			}
			return 'friendly';
		}

		return $configured;
	}

	/**
	 * Add Pro placeholders to allowed list.
	 *
	 * @param array  $placeholders Current allowed placeholders.
	 * @param string $template_id  Template ID.
	 * @return array
	 */
	public function add_allowed_placeholders( $placeholders, $template_id ) {
		$pro_placeholders = array(
			'{discount_code}',
			'{discount_amount}',
			'{discount_expiry}',
			'{cart_expiry}',
			'{low_stock_alert}',
		);
		return array_merge( $placeholders, $pro_placeholders );
	}

	/**
	 * Add Pro template variables for the email editor variable reference.
	 *
	 * @param array $vars Current variables.
	 * @return array
	 */
	public function add_template_variables( $vars ) {
		$vars['{discount_code}']   = __( 'Auto-generated coupon code', 'flexi-revive-cart-pro' );
		$vars['{discount_amount}'] = __( 'Discount amount (e.g. 10% or $5)', 'flexi-revive-cart-pro' );
		$vars['{discount_expiry}'] = __( 'Coupon expiration date', 'flexi-revive-cart-pro' );
		$vars['{cart_expiry}']     = __( 'Cart reservation expiry', 'flexi-revive-cart-pro' );
		$vars['{low_stock_alert}'] = __( 'Low stock warning for cart items', 'flexi-revive-cart-pro' );
		return $vars;
	}

	/**
	 * Allow testing stages 2 and 3.
	 *
	 * @param array $stages Allowed stages.
	 * @return array
	 */
	public function add_test_email_stages( $stages ) {
		$stages[] = 2;
		$stages[] = 3;
		return $stages;
	}

	/**
	 * Render additional test email stage options.
	 */
	public function render_test_email_stages() {
		echo '<option value="2">' . esc_html__( 'Stage 2 – Urgency', 'flexi-revive-cart-pro' ) . '</option>';
		echo '<option value="3">' . esc_html__( 'Stage 3 – Incentive', 'flexi-revive-cart-pro' ) . '</option>';
	}

	/**
	 * Add Pro time units.
	 *
	 * @param array $units Current time units.
	 * @return array
	 */
	public function add_time_units( $units ) {
		if ( ! isset( $units['weeks'] ) ) {
			$units['weeks'] = __( 'Weeks', 'flexi-revive-cart-pro' );
		}
		if ( ! isset( $units['months'] ) ) {
			$units['months'] = __( 'Months', 'flexi-revive-cart-pro' );
		}
		if ( ! isset( $units['years'] ) ) {
			$units['years'] = __( 'Years', 'flexi-revive-cart-pro' );
		}
		return $units;
	}

	/**
	 * Run database upgrades when the Pro version changes.
	 */
	private function maybe_upgrade_db() {
		$installed = get_option( 'frc_pro_db_version', '0' );
		if ( version_compare( $installed, FRC_PRO_PLUGIN_VERSION, '<' ) ) {
			require_once FRC_PRO_PLUGIN_DIR . 'includes/class-frc-pro-activator.php';
			FRC_Pro_Activator::upgrade_db();
		}
	}
}
