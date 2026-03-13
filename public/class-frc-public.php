<?php
/**
 * Frontend / public-facing hooks.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Public
 */
class FRC_Public {

	/**
	 * Constructor – register hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Render popups in footer (Pro).
		if ( FRC_PRO_ACTIVE ) {
			add_action( 'wp_footer', array( $this, 'render_guest_capture_popup' ) );
			add_action( 'wp_footer', array( $this, 'render_exit_intent_popup' ) );
		}
	}

	/**
	 * Enqueue public-facing scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'frc-public',
			FRC_PLUGIN_URL . 'public/css/frc-public.css',
			array(),
			FRC_VERSION
		);

		// Cart tracker heartbeat.
		wp_enqueue_script(
			'frc-cart-tracker',
			FRC_PLUGIN_URL . 'public/js/frc-cart-tracker.js',
			array( 'jquery' ),
			FRC_VERSION,
			true
		);

		wp_localize_script(
			'frc-cart-tracker',
			'frcTracker',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'frc_track_cart_nonce' ),
			)
		);

		// Pro feature scripts.
		if ( FRC_PRO_ACTIVE ) {
			if ( get_option( 'frc_enable_guest_capture', '0' ) ) {
				wp_enqueue_script(
					'frc-guest-capture',
					FRC_PLUGIN_URL . 'public/js/frc-guest-capture.js',
					array( 'jquery' ),
					FRC_VERSION,
					true
				);
				wp_localize_script(
					'frc-guest-capture',
					'frcGuestCapture',
					array(
						'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
						'nonce'        => wp_create_nonce( 'frc_guest_capture_nonce' ),
						'delaySeconds' => (int) get_option( 'frc_popup_delay_seconds', 30 ),
					)
				);
			}

			if ( get_option( 'frc_enable_exit_intent', '0' ) ) {
				wp_enqueue_script(
					'frc-exit-intent',
					FRC_PLUGIN_URL . 'public/js/frc-exit-intent.js',
					array( 'jquery' ),
					FRC_VERSION,
					true
				);
				wp_localize_script(
					'frc-exit-intent',
					'frcExitIntent',
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'frc_guest_capture_nonce' ),
					)
				);
			}

			// Browse tracker on product pages.
			if ( is_product() ) {
				wp_enqueue_script(
					'frc-browse-tracker',
					FRC_PLUGIN_URL . 'public/js/frc-browse-tracker.js',
					array( 'jquery' ),
					FRC_VERSION,
					true
				);
				wp_localize_script(
					'frc-browse-tracker',
					'frcBrowse',
					array(
						'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
						'nonce'     => wp_create_nonce( 'frc_browse_nonce' ),
						'productId' => get_the_ID(),
					)
				);
			}
		}
	}

	/**
	 * Render guest capture popup in footer.
	 */
	public function render_guest_capture_popup() {
		if ( ! FRC_PRO_ACTIVE || ! get_option( 'frc_enable_guest_capture', '0' ) ) {
			return;
		}
		if ( is_user_logged_in() ) {
			return;
		}
		$template = FRC_PLUGIN_DIR . 'templates/popups/guest-capture.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render exit-intent popup in footer.
	 */
	public function render_exit_intent_popup() {
		if ( ! FRC_PRO_ACTIVE || ! get_option( 'frc_enable_exit_intent', '0' ) ) {
			return;
		}
		$template = FRC_PLUGIN_DIR . 'templates/popups/exit-intent.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
