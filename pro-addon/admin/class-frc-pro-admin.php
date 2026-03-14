<?php
/**
 * Pro admin controller – adds Pro-specific admin pages and scripts.
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Admin
 */
class FRC_Pro_Admin {

	/**
	 * Add Pro submenu pages.
	 *
	 * Hooked to: frc_admin_menu
	 *
	 * @param string $parent_slug Parent menu slug.
	 */
	public function add_pro_menus( $parent_slug ) {
		$analytics = new FRC_Pro_Admin_Analytics();
		add_submenu_page(
			$parent_slug,
			__( 'Analytics', 'flexi-revive-cart-pro' ),
			__( 'Analytics', 'flexi-revive-cart-pro' ),
			'manage_woocommerce',
			'frc-analytics',
			array( $analytics, 'render' )
		);

		if ( get_option( 'frc_enable_whatsapp', '0' ) ) {
			$whatsapp = new FRC_Pro_Admin_WhatsApp();
			add_submenu_page(
				$parent_slug,
				__( 'WhatsApp Campaigns', 'flexi-revive-cart-pro' ),
				__( 'WhatsApp', 'flexi-revive-cart-pro' ),
				'manage_woocommerce',
				'frc-whatsapp',
				array( $whatsapp, 'render' )
			);
		}

		$ab = new FRC_Pro_Admin_AB_Results();
		add_submenu_page(
			$parent_slug,
			__( 'A/B Test Results', 'flexi-revive-cart-pro' ),
			__( 'A/B Tests', 'flexi-revive-cart-pro' ),
			'manage_woocommerce',
			'frc-ab-tests',
			array( $ab, 'render' )
		);
	}

	/**
	 * Enqueue Pro admin scripts and styles.
	 *
	 * Hooked to: frc_admin_enqueue_scripts
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		wp_enqueue_style(
			'frc-pro-admin',
			FRC_PRO_PLUGIN_URL . 'admin/css/frc-pro-admin.css',
			array(),
			FRC_PRO_PLUGIN_VERSION
		);
	}

	/**
	 * Extend which admin pages load FRC assets.
	 *
	 * Hooked to: frc_admin_page_hooks
	 *
	 * @param array $pages Page hook suffixes.
	 * @return array
	 */
	public function extend_page_hooks( $pages ) {
		$pages[] = 'flexi-revive-cart_page_frc-analytics';
		$pages[] = 'flexi-revive-cart_page_frc-whatsapp';
		$pages[] = 'flexi-revive-cart_page_frc-ab-tests';
		$pages[] = 'flexi-revive-cart_page_frc-pro-license';
		return $pages;
	}
}
