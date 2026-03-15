<?php
/**
 * Admin page registration and rendering.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Admin
 */
class FRC_Admin {

	/**
	 * Constructor – register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . FRC_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'wp_ajax_frc_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_frc_get_chart_data', array( $this, 'ajax_get_chart_data' ) );
		add_action( 'wp_ajax_frc_delete_cart', array( $this, 'ajax_delete_cart' ) );
		add_action( 'wp_ajax_frc_resend_reminder', array( $this, 'ajax_resend_reminder' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Flexi Revive Cart', 'flexi-revive-cart' ),
			__( 'Flexi Revive', 'flexi-revive-cart' ),
			'manage_woocommerce',
			'flexi-revive-cart',
			array( $this, 'render_dashboard' ),
			'dashicons-cart',
			56
		);

		add_submenu_page(
			'flexi-revive-cart',
			__( 'Dashboard', 'flexi-revive-cart' ),
			__( 'Dashboard', 'flexi-revive-cart' ),
			'manage_woocommerce',
			'flexi-revive-cart',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'flexi-revive-cart',
			__( 'Abandoned Carts', 'flexi-revive-cart' ),
			__( 'Abandoned Carts', 'flexi-revive-cart' ),
			'manage_woocommerce',
			'frc-carts',
			array( $this, 'render_carts' )
		);

		// Email Template Editor – available to all users (Free).
		add_submenu_page(
			'flexi-revive-cart',
			__( 'Email Templates', 'flexi-revive-cart' ),
			__( 'Email Templates', 'flexi-revive-cart' ),
			'manage_woocommerce',
			'frc-email-editor',
			array( $this, 'render_email_editor' )
		);

		add_submenu_page(
			'flexi-revive-cart',
			__( 'Settings', 'flexi-revive-cart' ),
			__( 'Settings', 'flexi-revive-cart' ),
			'manage_woocommerce',
			'frc-settings',
			array( $this, 'render_settings' )
		);

		/**
		 * Fires after core admin menu items are registered.
		 *
		 * Pro add-ons can use this hook to add their own submenu pages
		 * (e.g., A/B Results, WhatsApp Campaigns, Analytics).
		 *
		 * @param string $parent_slug The parent menu slug ('flexi-revive-cart').
		 */
		do_action( 'frc_admin_menu', 'flexi-revive-cart' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$frc_pages = array(
			'toplevel_page_flexi-revive-cart',
			'flexi-revive_page_frc-carts',
			'flexi-revive_page_frc-settings',
			'flexi-revive_page_frc-email-editor',
		);

		/**
		 * Filters the list of admin page hooks where FRC assets are loaded.
		 *
		 * Pro add-ons can append their own page hooks so FRC styles/scripts load there too.
		 *
		 * @param array $frc_pages Array of admin page hook suffixes.
		 */
		$frc_pages = apply_filters( 'frc_admin_page_hooks', $frc_pages );

		if ( ! in_array( $hook, $frc_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'frc-admin',
			FRC_PLUGIN_URL . 'admin/css/frc-admin.css',
			array(),
			FRC_VERSION
		);

		wp_enqueue_script(
			'frc-admin',
			FRC_PLUGIN_URL . 'admin/js/frc-admin.js',
			array( 'jquery' ),
			FRC_VERSION,
			true
		);

		wp_localize_script(
			'frc-admin',
			'frcAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'frc_admin_nonce' ),
				'proActive' => frc_is_pro_licensed(),
				'i18n'      => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this cart?', 'flexi-revive-cart' ),
					'testEmailSent'  => __( 'Test email sent!', 'flexi-revive-cart' ),
					'error'          => __( 'An error occurred. Please try again.', 'flexi-revive-cart' ),
				),
			)
		);

		// Chart.js – only on dashboard.
		if ( 'toplevel_page_flexi-revive-cart' === $hook ) {
			wp_enqueue_script(
				'chartjs',
				FRC_PLUGIN_URL . 'admin/js/chart.min.js',
				array(),
				'4.5.1',
				true
			);

			wp_enqueue_script(
				'frc-charts',
				FRC_PLUGIN_URL . 'admin/js/frc-admin-charts.js',
				array( 'jquery', 'chartjs' ),
				FRC_VERSION,
				true
			);

			wp_localize_script(
				'frc-charts',
				'frcCharts',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'frc_admin_nonce' ),
				)
			);
		}

		/**
		 * Fires after core admin scripts are enqueued on FRC pages.
		 *
		 * @param string $hook Current admin page hook.
		 */
		do_action( 'frc_admin_enqueue_scripts', $hook );
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=frc-settings' ) . '">' . __( 'Settings', 'flexi-revive-cart' ) . '</a>',
		);
		if ( ! frc_is_pro_licensed() ) {
			$plugin_links[] = '<a href="https://github.com/Darpan-Sarmah/flexi-revive-cart" target="_blank" style="color:#46b450;font-weight:bold;">' . __( 'Upgrade to Pro', 'flexi-revive-cart' ) . '</a>';
		}
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'flexi-revive-cart' ) );
		}
		$dashboard = new FRC_Admin_Dashboard();
		$dashboard->render();
	}

	/**
	 * Render the abandoned carts page.
	 */
	public function render_carts() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'flexi-revive-cart' ) );
		}
		$carts = new FRC_Admin_Carts();
		$carts->render();
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'flexi-revive-cart' ) );
		}
		$settings = new FRC_Admin_Settings();
		$settings->render();
	}

	/**
	 * Render the email editor page.
	 */
	public function render_email_editor() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'flexi-revive-cart' ) );
		}
		if ( class_exists( 'FRC_Admin_Email_Editor' ) ) {
			$editor = new FRC_Admin_Email_Editor();
			$editor->render();
		}
	}

	/**
	 * AJAX: Send a test email.
	 *
	 * In Free version, only stage 1 (Friendly Reminder) test emails are allowed.
	 * Pro allows test emails for all stages.
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'frc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-revive-cart' ) ) );
			return;
		}

		$to      = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : get_option( 'admin_email' );
		$stage   = isset( $_POST['stage'] ) ? absint( wp_unslash( $_POST['stage'] ) ) : 1;
		$lang    = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';

		// Determine language for test email: POST param > admin preview > default.
		if ( empty( $lang ) ) {
			$lang = get_option( 'frc_admin_preview_language', '' );
		}
		if ( empty( $lang ) ) {
			$lang = get_option( 'frc_default_language', 'en' );
		}
		$lang = FRC_Email_Templates::validate_lang( $lang );

		// Restrict test emails to allowed stages.
		$allowed_stages = array( 1 );

		/**
		 * Filters the allowed test email stages.
		 *
		 * @param array $allowed_stages Array of allowed stage numbers.
		 */
		$allowed_stages = apply_filters( 'frc_test_email_allowed_stages', $allowed_stages );

		if ( ! in_array( $stage, $allowed_stages, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Install the Pro add-on to send test emails for additional reminder types.', 'flexi-revive-cart' ) ) );
			return;
		}

		// Create a mock cart for preview.
		$mock = (object) array(
			'id'             => 0,
			'user_id'        => get_current_user_id(),
			'user_email'     => $to,
			'cart_contents'  => '[]',
			'cart_total'     => 49.99,
			'currency'       => get_woocommerce_currency(),
			'recovery_token' => FRC_Helpers::generate_recovery_token(),
			'abandoned_at'   => current_time( 'mysql' ),
			'emails_sent'    => 0,
			'opted_out'      => 0,
			'discount_code'  => '',
		);

		$template_id = 'reminder-' . $stage;
		$vars        = FRC_Email_Templates::build_vars( $mock );
		$body        = FRC_Email_Templates::render( $template_id, $vars, $lang );

		// Get subject from the unified template subject storage (with placeholder replacement).
		$subject = FRC_Email_Templates::get_subject( $template_id, $lang, $vars );
		if ( empty( $subject ) ) {
			$subject = __( '[Test] Cart Recovery Email', 'flexi-revive-cart' );
		} else {
			$subject = '[Test] ' . $subject;
		}

		$sent = wp_mail(
			$to,
			$subject,
			$body,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent!', 'flexi-revive-cart' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send test email.', 'flexi-revive-cart' ) ) );
		}
	}

	/**
	 * AJAX: Get chart data for the dashboard.
	 */
	public function ajax_get_chart_data() {
		check_ajax_referer( 'frc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
			return;
		}

		$dashboard = new FRC_Admin_Dashboard();
		wp_send_json_success( $dashboard->get_chart_data() );
	}

	/**
	 * AJAX: Delete a cart.
	 */
	public function ajax_delete_cart() {
		check_ajax_referer( 'frc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
			return;
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'flexi-revive-cart' ) ) );
			return;
		}

		global $wpdb;
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'frc_abandoned_carts',
			array( 'id' => $cart_id ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Cart deleted.', 'flexi-revive-cart' ) ) );
	}

	/**
	 * AJAX: Resend a reminder for a cart.
	 */
	public function ajax_resend_reminder() {
		check_ajax_referer( 'frc_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
			return;
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'flexi-revive-cart' ) ) );
			return;
		}

		$cart = FRC_Helpers::get_cart_by_id( $cart_id );
		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'flexi-revive-cart' ) ) );
			return;
		}

		$max_stage     = min( (int) get_option( 'frc_num_reminders', 3 ), apply_filters( 'frc_max_reminders', 3 ) );
		$stage         = min( (int) $cart->emails_sent + 1, $max_stage );
		$email_manager = new FRC_Email_Manager();
		$sent          = $email_manager->send_reminder( $cart, $stage );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Reminder sent!', 'flexi-revive-cart' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send reminder.', 'flexi-revive-cart' ) ) );
		}
	}

}
