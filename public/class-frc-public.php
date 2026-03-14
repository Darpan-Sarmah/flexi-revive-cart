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

		// Language switcher on cart/checkout.
		add_action( 'woocommerce_before_cart', array( $this, 'render_language_switcher' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render_language_switcher' ) );

		// Handle language switch AJAX / query param.
		add_action( 'wp_ajax_frc_set_language', array( $this, 'ajax_set_language' ) );
		add_action( 'wp_ajax_nopriv_frc_set_language', array( $this, 'ajax_set_language' ) );

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
	 * Render the frontend language switcher dropdown.
	 */
	public function render_language_switcher() {
		$languages    = FRC_Email_Templates::get_supported_languages();
		$current_lang = self::get_current_language();

		$flags = array(
			'en'    => '🇬🇧',
			'es'    => '🇪🇸',
			'fr'    => '🇫🇷',
			'de'    => '🇩🇪',
			'it'    => '🇮🇹',
			'hi'    => '🇮🇳',
			'pt_BR' => '🇧🇷',
			'zh_CN' => '🇨🇳',
			'ar'    => '🇸🇦',
			'ja'    => '🇯🇵',
		);
		?>
		<div class="frc-language-switcher" style="margin-bottom:16px;text-align:right;">
			<label for="frc-frontend-lang" style="font-size:14px;">
				<?php esc_html_e( 'Select Language', 'flexi-revive-cart' ); ?>:
			</label>
			<select id="frc-frontend-lang" style="font-size:14px;" onchange="if(this.value){var u=new URL(window.location.href);u.searchParams.set('frc_lang',this.value);window.location.href=u.toString();}">
				<?php foreach ( $languages as $code => $label ) :
					$flag = isset( $flags[ $code ] ) ? $flags[ $code ] . ' ' : '';
				?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $current_lang ); ?>>
					<?php echo esc_html( $flag . $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Get the current frontend language.
	 *
	 * Priority: query param > cookie > user meta > default option > 'en'.
	 *
	 * @return string Language code.
	 */
	public static function get_current_language() {
		// Check query param.
		if ( isset( $_GET['frc_lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$lang = sanitize_key( wp_unslash( $_GET['frc_lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$lang = FRC_Email_Templates::validate_lang( $lang );
			// Store in cookie for persistence.
			if ( ! headers_sent() ) {
				setcookie(
					'frc_language',
					$lang,
					array(
						'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
			}
			return $lang;
		}

		// Check cookie.
		if ( isset( $_COOKIE['frc_language'] ) ) {
			return FRC_Email_Templates::validate_lang( sanitize_key( $_COOKIE['frc_language'] ) );
		}

		// Check user meta for logged-in users.
		if ( is_user_logged_in() ) {
			$user_lang = get_user_meta( get_current_user_id(), 'frc_language', true );
			if ( $user_lang ) {
				return FRC_Email_Templates::validate_lang( $user_lang );
			}
		}

		// Default language.
		return FRC_Email_Templates::validate_lang( get_option( 'frc_default_language', 'en' ) );
	}

	/**
	 * AJAX handler to set the frontend language.
	 */
	public function ajax_set_language() {
		check_ajax_referer( 'frc_track_cart_nonce', 'nonce' );

		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : 'en';
		$lang = FRC_Email_Templates::validate_lang( $lang );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'frc_language', $lang );
		}

		wp_send_json_success( array( 'lang' => $lang ) );
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
