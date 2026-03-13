<?php
/**
 * Plugin Name: Flexi Revive Cart - Abandoned Cart Recovery for WooCommerce
 * Plugin URI: https://github.com/Darpan-Sarmah/flexi-revive-cart
 * Description: Recover abandoned carts with email, SMS, push notifications, exit-intent popups, and dynamic discounts. Free and Pro versions available.
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
define( 'FRC_PRO_ACTIVE', defined( 'FRC_PRO_LICENSE_KEY' ) && ! empty( FRC_PRO_LICENSE_KEY ) );

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
 */
function frc_deactivate_plugin() {
	require_once FRC_PLUGIN_DIR . 'includes/class-frc-deactivator.php';
	FRC_Deactivator::deactivate();
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
