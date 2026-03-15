<?php
/**
 * Plugin Name: Flexi Revive Cart - Abandoned Cart Recovery for WooCommerce
 * Plugin URI: https://github.com/Darpan-Sarmah/flexi-revive-cart
 * Description: Recover abandoned carts with automated email reminders. Extensible Core engine – install the Pro add-on for SMS, push notifications, exit-intent popups, and dynamic discounts.
 * Version: 1.0.0
 * Author: Darpan Sarmah
 * Author URI: https://github.com/Darpan-Sarmah
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flexi-revive-cart
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * WC tested up to: 9.5
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'FRC_VERSION', '1.0.0' );
define( 'FRC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FRC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FRC_PRO_ACTIVE', defined( 'FRC_PRO_VERSION' ) );

/**
 * Check if the Pro add-on is active AND has a valid license.
 *
 * This is the authoritative check for Pro feature availability.
 * Unlike the FRC_PRO_ACTIVE constant (which only checks if the constant
 * exists at load time), this function also verifies the Pro add-on has
 * confirmed its license via the frc_pro_license_valid filter.
 *
 * Security: Simply defining FRC_PRO_VERSION in wp-config.php will NOT
 * unlock Pro features because the frc_pro_license_valid filter will
 * still return false without the actual Pro plugin loaded and licensed.
 *
 * @return bool True if Pro is active and licensed.
 */
function frc_is_pro_licensed() {
	return defined( 'FRC_PRO_VERSION' ) && apply_filters( 'frc_pro_license_valid', false );
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function frc_is_woocommerce_active() {
	return in_array(
		'woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
		true
	) || ( is_multisite() && array_key_exists(
		'woocommerce/woocommerce.php',
		get_site_option( 'active_sitewide_plugins', array() )
	) );
}

/**
 * Show admin notice if WooCommerce is not active.
 */
function frc_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Flexi Revive Cart requires WooCommerce to be installed and active.', 'flexi-revive-cart' ) .
		'</p></div>';
}

/**
 * Auto-deactivate this plugin when WooCommerce is no longer active.
 *
 * Runs on admin_init so it catches the case where a user deactivates
 * WooCommerce while Flexi Revive Cart is still active.
 */
function frc_check_wc_still_active() {
	if ( ! frc_is_woocommerce_active() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		set_transient( 'frc_wc_deactivated_notice', true, 30 );
	}
}
add_action( 'admin_init', 'frc_check_wc_still_active' );

/**
 * Show admin notice after auto-deactivation due to missing WooCommerce.
 */
function frc_wc_deactivated_notice() {
	if ( get_transient( 'frc_wc_deactivated_notice' ) ) {
		delete_transient( 'frc_wc_deactivated_notice' );
		echo '<div class="notice notice-warning is-dismissible"><p>' .
			esc_html__( 'Flexi Revive Cart has been deactivated because WooCommerce is no longer active.', 'flexi-revive-cart' ) .
			'</p></div>';
	}
}
add_action( 'admin_notices', 'frc_wc_deactivated_notice' );

/**
 * Enqueue the deactivation-feedback modal assets on the Plugins page.
 *
 * @param string $hook Current admin page hook.
 */
function frc_enqueue_deactivation_assets( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'frc-deactivate-modal',
		FRC_PLUGIN_URL . 'admin/css/frc-deactivate-modal.css',
		array(),
		FRC_VERSION
	);

	wp_enqueue_script(
		'frc-deactivate-modal',
		FRC_PLUGIN_URL . 'admin/js/frc-deactivate-modal.js',
		array( 'jquery' ),
		FRC_VERSION,
		true
	);

	wp_localize_script(
		'frc-deactivate-modal',
		'frcDeactivate',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'frc_deactivate_cleanup' ),
			'basename' => plugin_basename( __FILE__ ),
			'i18n'     => array(
				'title'      => __( 'Flexi Revive Cart – Deactivation', 'flexi-revive-cart' ),
				'message'    => __( 'Would you like to delete all plugin data (database tables, settings, and generated coupons) or keep it for future use?', 'flexi-revive-cart' ),
				'deleteBtn'  => __( 'Delete All Data & Deactivate', 'flexi-revive-cart' ),
				'keepBtn'    => __( 'Keep Data & Deactivate', 'flexi-revive-cart' ),
				'cancelBtn'  => __( 'Cancel', 'flexi-revive-cart' ),
				'cleaning'   => __( 'Cleaning up…', 'flexi-revive-cart' ),
				'cleanError' => __( 'Data cleanup failed. The plugin was not deactivated.', 'flexi-revive-cart' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'frc_enqueue_deactivation_assets' );

/**
 * AJAX handler: flag that data should be deleted on deactivation.
 *
 * Instead of deleting data immediately (which would be undone by
 * maybe_upgrade_db() on the subsequent deactivation page load),
 * we set a short-lived transient.  The deactivation hook reads
 * the flag and performs the actual cleanup after plugins_loaded
 * has already finished, so no code can re-create the tables.
 */
function frc_ajax_cleanup_data() {
	check_ajax_referer( 'frc_deactivate_cleanup', 'nonce' );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'flexi-revive-cart' ) ) );
		return;
	}

	set_transient( 'frc_cleanup_on_deactivate', true, 120 );

	wp_send_json_success();
}
add_action( 'wp_ajax_frc_cleanup_data', 'frc_ajax_cleanup_data' );

// Load the plugin only if WooCommerce is active.
add_action( 'plugins_loaded', 'frc_init_plugin' );

/**
 * Initialize the plugin.
 */
function frc_init_plugin() {
	if ( ! frc_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'frc_woocommerce_missing_notice' );
		return;
	}

	// Load text domain for i18n.
	load_plugin_textdomain(
		'flexi-revive-cart',
		false,
		dirname( FRC_PLUGIN_BASENAME ) . '/languages'
	);

	// Include the main loader class.
	require_once FRC_PLUGIN_DIR . 'includes/class-frc-loader.php';

	// Initialize the plugin loader.
	$plugin = new FRC_Loader();
	$plugin->run();
}

/**
 * Activation hook.
 */
function frc_activate_plugin() {
	if ( ! frc_is_woocommerce_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Flexi Revive Cart requires WooCommerce to be installed and active.', 'flexi-revive-cart' ),
			esc_html__( 'Plugin Activation Error', 'flexi-revive-cart' ),
			array( 'back_link' => true )
		);
	}
	require_once FRC_PLUGIN_DIR . 'includes/class-frc-activator.php';
	FRC_Activator::activate();
}
register_activation_hook( __FILE__, 'frc_activate_plugin' );

/**
 * Deactivation hook.
 *
 * Always clears cron jobs.  When the user chose "Delete All Data"
 * in the deactivation modal (recorded via a transient), also
 * removes database tables, options, coupons, and Action Scheduler
 * actions.  Running cleanup here – after plugins_loaded – prevents
 * maybe_upgrade_db() from re-creating the tables it just deleted.
 */
function frc_deactivate_plugin() {
	require_once FRC_PLUGIN_DIR . 'includes/class-frc-deactivator.php';
	FRC_Deactivator::deactivate();

	if ( get_transient( 'frc_cleanup_on_deactivate' ) ) {
		delete_transient( 'frc_cleanup_on_deactivate' );
		FRC_Deactivator::cleanup_data();
	}
}
register_deactivation_hook( __FILE__, 'frc_deactivate_plugin' );

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);
