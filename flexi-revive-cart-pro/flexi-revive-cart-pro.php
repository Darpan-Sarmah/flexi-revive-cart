<?php
/**
 * Plugin Name: Flexi Revive Cart Pro
 * Plugin URI: https://github.com/Darpan-Sarmah/flexi-revive-cart-pro
 * Description: Pro add-on for Flexi Revive Cart – adds SMS, WhatsApp, push notifications, dynamic discounts, A/B testing, advanced analytics, exit-intent popups, guest capture, browse abandonment, and more.
 * Version: 1.0.0
 * Author: Darpan Sarmah
 * Author URI: https://github.com/Darpan-Sarmah
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: flexi-revive-cart-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.5
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Do NOT define FRC_PRO_VERSION until license is verified.
define( 'FRC_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRC_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FRC_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FRC_PRO_PLUGIN_VERSION', '1.0.0' );

/**
 * Check if the free version of Flexi Revive Cart is active.
 *
 * @return bool
 */
function frc_pro_is_free_active() {
	return defined( 'FRC_VERSION' ) && defined( 'FRC_PLUGIN_DIR' );
}

/**
 * Show admin notice if the free version is not active.
 */
function frc_pro_free_missing_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Flexi Revive Cart Pro requires the free version of Flexi Revive Cart to be installed and activated.', 'flexi-revive-cart-pro' ) .
		' <a href="' . esc_url( admin_url( 'plugin-install.php?s=flexi+revive+cart&tab=search&type=term' ) ) . '">' .
		esc_html__( 'Install Flexi Revive Cart', 'flexi-revive-cart-pro' ) .
		'</a></p></div>';
}

/**
 * Show admin notice if the license is invalid.
 */
function frc_pro_license_invalid_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	// Don't show on the license page itself.
	if ( $screen && 'flexi-revive-cart_page_frc-pro-license' === $screen->id ) {
		return;
	}
	echo '<div class="notice notice-warning is-dismissible"><p>' .
		esc_html__( 'Flexi Revive Cart Pro is installed but not activated. Please enter your license key to unlock Pro features.', 'flexi-revive-cart-pro' ) .
		' <a href="' . esc_url( admin_url( 'admin.php?page=frc-pro-license' ) ) . '">' .
		esc_html__( 'Enter License Key', 'flexi-revive-cart-pro' ) .
		'</a></p></div>';
}

/**
 * Initialize the Pro add-on.
 *
 * Hooks into 'frc_loaded' which fires after the free plugin is fully initialised.
 * This ensures all core classes and hooks are available.
 */
function frc_pro_init() {
	if ( ! frc_pro_is_free_active() ) {
		add_action( 'admin_notices', 'frc_pro_free_missing_notice' );
		return;
	}

	// Load the license manager first – it registers the admin page regardless of license status.
	require_once FRC_PRO_PLUGIN_DIR . 'includes/class-frc-pro-license.php';

	$license = FRC_Pro_License::get_instance();

	// Always register the license admin page so users can enter their key.
	if ( is_admin() ) {
		add_action( 'frc_admin_menu', array( $license, 'add_license_page' ) );
	}

	// Verify license before loading any Pro features.
	if ( ! $license->is_valid() ) {
		add_action( 'admin_notices', 'frc_pro_license_invalid_notice' );
		return;
	}

	// License is valid – define the constant that the free version checks.
	if ( ! defined( 'FRC_PRO_VERSION' ) ) {
		define( 'FRC_PRO_VERSION', FRC_PRO_PLUGIN_VERSION );
	}

	// Signal to the free plugin that Pro is licensed.
	add_filter( 'frc_pro_license_valid', '__return_true' );

	// Load the Pro loader which registers all Pro features via hooks.
	require_once FRC_PRO_PLUGIN_DIR . 'includes/class-frc-pro-loader.php';
	new FRC_Pro_Loader();
}
add_action( 'frc_loaded', 'frc_pro_init', 5 );

/**
 * Fallback: if the free plugin isn't active, still show the notice.
 */
function frc_pro_plugins_loaded_check() {
	if ( ! frc_pro_is_free_active() ) {
		add_action( 'admin_notices', 'frc_pro_free_missing_notice' );
	}
}
add_action( 'plugins_loaded', 'frc_pro_plugins_loaded_check', 20 );

/**
 * Activation hook – verify free version is active.
 */
function frc_pro_activate() {
	if ( ! frc_pro_is_free_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Flexi Revive Cart Pro requires the free version of Flexi Revive Cart to be installed and activated first.', 'flexi-revive-cart-pro' ),
			esc_html__( 'Plugin Activation Error', 'flexi-revive-cart-pro' ),
			array( 'back_link' => true )
		);
	}

	// Create Pro-specific tables.
	require_once FRC_PRO_PLUGIN_DIR . 'includes/class-frc-pro-activator.php';
	FRC_Pro_Activator::activate();
}
register_activation_hook( __FILE__, 'frc_pro_activate' );

/**
 * Deactivation hook.
 */
function frc_pro_deactivate() {
	// Clear Pro-specific scheduled events.
	wp_clear_scheduled_hook( 'frc_process_browse_abandonment' );
	delete_transient( 'frc_pro_license_valid' );
}
register_deactivation_hook( __FILE__, 'frc_pro_deactivate' );

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
