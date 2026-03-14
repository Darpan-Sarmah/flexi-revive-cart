<?php
/**
 * License management for Flexi Revive Cart Pro.
 *
 * Uses HMAC-SHA256 signed license keys. Keys are generated externally
 * and validated locally against the site URL.
 *
 * License key format: FRC-{base64_payload}-{hmac_hex}
 * Payload JSON: { "site": "example.com", "tier": "pro", "exp": "2099-12-31" }
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_License
 */
class FRC_Pro_License {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * HMAC signing key for license validation.
	 * This MUST be kept in sync with the license generator.
	 *
	 * @var string
	 */
	const LICENSE_SECRET = 'frc_pro_2024_s3cr3t_k3y_!@#$%^&*';

	/**
	 * Transient TTL for cached license validation (12 hours).
	 *
	 * @var int
	 */
	const CACHE_TTL = 43200;

	/**
	 * Option name for the stored license key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'frc_pro_license_key';

	/**
	 * Option name for license status.
	 *
	 * @var string
	 */
	const OPTION_STATUS = 'frc_pro_license_status';

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check whether the current license is valid.
	 *
	 * Uses a transient cache to avoid re-computing on every page load.
	 *
	 * @return bool
	 */
	public function is_valid() {
		// Check transient cache first.
		$cached = get_transient( 'frc_pro_license_valid' );
		if ( 'yes' === $cached ) {
			return true;
		}
		if ( 'no' === $cached ) {
			return false;
		}

		// Validate the stored key.
		$key    = get_option( self::OPTION_KEY, '' );
		$result = $this->validate_key( $key );

		set_transient( 'frc_pro_license_valid', $result ? 'yes' : 'no', self::CACHE_TTL );

		return $result;
	}

	/**
	 * Validate a license key string.
	 *
	 * @param string $key License key to validate.
	 * @return bool True if valid.
	 */
	public function validate_key( $key ) {
		$key = trim( $key );

		if ( empty( $key ) ) {
			return false;
		}

		// Key format: FRC-{base64_payload}-{hmac_hex}
		$parts = explode( '-', $key, 3 );
		if ( count( $parts ) < 3 || 'FRC' !== $parts[0] ) {
			return false;
		}

		$payload_b64 = $parts[1];
		$provided_hmac = $parts[2];

		// Decode payload.
		$payload_json = base64_decode( $payload_b64, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $payload_json ) {
			return false;
		}

		$payload = json_decode( $payload_json, true );
		if ( ! is_array( $payload ) ) {
			return false;
		}

		// Verify HMAC signature.
		$expected_hmac = hash_hmac( 'sha256', $payload_b64, self::LICENSE_SECRET );
		if ( ! hash_equals( $expected_hmac, $provided_hmac ) ) {
			return false;
		}

		// Validate payload contents.
		if ( empty( $payload['tier'] ) || 'pro' !== $payload['tier'] ) {
			return false;
		}

		// Check expiration if set.
		if ( ! empty( $payload['exp'] ) ) {
			$expiry = strtotime( $payload['exp'] );
			if ( $expiry && $expiry < time() ) {
				return false;
			}
		}

		// Validate site URL if set (domain-locked licenses).
		if ( ! empty( $payload['site'] ) ) {
			$licensed_domain = strtolower( trim( $payload['site'] ) );
			$current_domain  = strtolower( wp_parse_url( home_url(), PHP_URL_HOST ) );

			// Allow wildcard '*' for development/testing.
			if ( '*' !== $licensed_domain && $licensed_domain !== $current_domain ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Activate a license key.
	 *
	 * @param string $key License key.
	 * @return array { success: bool, message: string }
	 */
	public function activate( $key ) {
		$key = sanitize_text_field( trim( $key ) );

		if ( empty( $key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a license key.', 'flexi-revive-cart-pro' ),
			);
		}

		if ( ! $this->validate_key( $key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid license key. Please check your key and try again.', 'flexi-revive-cart-pro' ),
			);
		}

		update_option( self::OPTION_KEY, $key );
		update_option( self::OPTION_STATUS, 'active' );
		delete_transient( 'frc_pro_license_valid' );
		set_transient( 'frc_pro_license_valid', 'yes', self::CACHE_TTL );

		return array(
			'success' => true,
			'message' => __( 'License activated successfully! Pro features are now enabled.', 'flexi-revive-cart-pro' ),
		);
	}

	/**
	 * Deactivate the current license key.
	 *
	 * @return array { success: bool, message: string }
	 */
	public function deactivate_license() {
		delete_option( self::OPTION_KEY );
		update_option( self::OPTION_STATUS, 'inactive' );
		delete_transient( 'frc_pro_license_valid' );

		return array(
			'success' => true,
			'message' => __( 'License deactivated. Pro features have been disabled.', 'flexi-revive-cart-pro' ),
		);
	}

	/**
	 * Get the current license status.
	 *
	 * @return string 'active', 'inactive', or 'expired'.
	 */
	public function get_status() {
		$key = get_option( self::OPTION_KEY, '' );
		if ( empty( $key ) ) {
			return 'inactive';
		}
		if ( ! $this->validate_key( $key ) ) {
			return 'expired';
		}
		return 'active';
	}

	/**
	 * Get license details from the stored key.
	 *
	 * @return array|null Payload data or null.
	 */
	public function get_details() {
		$key = get_option( self::OPTION_KEY, '' );
		if ( empty( $key ) ) {
			return null;
		}

		$parts = explode( '-', $key, 3 );
		if ( count( $parts ) < 3 ) {
			return null;
		}

		$payload_json = base64_decode( $parts[1], true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $payload_json ) {
			return null;
		}

		return json_decode( $payload_json, true );
	}

	/**
	 * Generate a license key for testing/development.
	 *
	 * This is a helper for administrators and should NOT be exposed in production.
	 *
	 * @param string $site   Domain name (e.g. 'example.com' or '*' for all).
	 * @param string $expiry Expiry date (Y-m-d format).
	 * @return string License key.
	 */
	public static function generate_key( $site = '*', $expiry = '2099-12-31' ) {
		$payload = wp_json_encode( array(
			'site' => $site,
			'tier' => 'pro',
			'exp'  => $expiry,
			'ts'   => time(),
		) );

		$payload_b64 = base64_encode( $payload ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$hmac        = hash_hmac( 'sha256', $payload_b64, self::LICENSE_SECRET );

		return 'FRC-' . $payload_b64 . '-' . $hmac;
	}

	/**
	 * Add the license management submenu page.
	 *
	 * @param string $parent_slug Parent menu slug.
	 */
	public function add_license_page( $parent_slug ) {
		add_submenu_page(
			$parent_slug,
			__( 'Pro License', 'flexi-revive-cart-pro' ),
			__( 'Pro License', 'flexi-revive-cart-pro' ),
			'manage_options',
			'frc-pro-license',
			array( $this, 'render_license_page' )
		);
	}

	/**
	 * Render the license management page.
	 */
	public function render_license_page() {
		$message = '';
		$type    = '';

		// Handle form submission.
		if ( isset( $_POST['frc_license_action'] ) && check_admin_referer( 'frc_pro_license_nonce', 'frc_license_nonce' ) ) {
			$action = sanitize_text_field( wp_unslash( $_POST['frc_license_action'] ) );

			if ( 'activate' === $action && isset( $_POST['frc_license_key'] ) ) {
				$key    = sanitize_text_field( wp_unslash( $_POST['frc_license_key'] ) );
				$result = $this->activate( $key );
				$message = $result['message'];
				$type    = $result['success'] ? 'success' : 'error';
			} elseif ( 'deactivate' === $action ) {
				$result  = $this->deactivate_license();
				$message = $result['message'];
				$type    = 'success';
			}
		}

		$status     = $this->get_status();
		$stored_key = get_option( self::OPTION_KEY, '' );
		$details    = $this->get_details();

		?>
		<div class="wrap frc-wrap">
			<h1><?php esc_html_e( 'Flexi Revive Cart Pro – License', 'flexi-revive-cart-pro' ); ?></h1>

			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			<?php endif; ?>

			<div class="postbox" style="max-width:700px;margin-top:20px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'License Status', 'flexi-revive-cart-pro' ); ?></h2>
				</div>
				<div class="inside">

					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Status', 'flexi-revive-cart-pro' ); ?></th>
							<td>
								<?php if ( 'active' === $status ) : ?>
									<span style="color:#46b450;font-weight:700;">● <?php esc_html_e( 'Active', 'flexi-revive-cart-pro' ); ?></span>
								<?php elseif ( 'expired' === $status ) : ?>
									<span style="color:#d63638;font-weight:700;">● <?php esc_html_e( 'Expired', 'flexi-revive-cart-pro' ); ?></span>
								<?php else : ?>
									<span style="color:#dba617;font-weight:700;">● <?php esc_html_e( 'Inactive', 'flexi-revive-cart-pro' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>

						<?php if ( $details ) : ?>
						<tr>
							<th><?php esc_html_e( 'Licensed Domain', 'flexi-revive-cart-pro' ); ?></th>
							<td><?php echo esc_html( ! empty( $details['site'] ) && '*' !== $details['site'] ? $details['site'] : __( 'All domains', 'flexi-revive-cart-pro' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Expires', 'flexi-revive-cart-pro' ); ?></th>
							<td><?php echo esc_html( ! empty( $details['exp'] ) ? $details['exp'] : __( 'Never', 'flexi-revive-cart-pro' ) ); ?></td>
						</tr>
						<?php endif; ?>
					</table>

					<?php if ( 'active' === $status ) : ?>
						<form method="post">
							<?php wp_nonce_field( 'frc_pro_license_nonce', 'frc_license_nonce' ); ?>
							<input type="hidden" name="frc_license_action" value="deactivate" />
							<p>
								<input type="text" value="<?php echo esc_attr( $stored_key ); ?>" class="large-text" readonly />
							</p>
							<p>
								<button type="submit" class="button button-secondary"
									onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate your license?', 'flexi-revive-cart-pro' ) ); ?>');">
									<?php esc_html_e( 'Deactivate License', 'flexi-revive-cart-pro' ); ?>
								</button>
							</p>
						</form>
					<?php else : ?>
						<form method="post">
							<?php wp_nonce_field( 'frc_pro_license_nonce', 'frc_license_nonce' ); ?>
							<input type="hidden" name="frc_license_action" value="activate" />
							<p>
								<label for="frc_license_key"><strong><?php esc_html_e( 'License Key', 'flexi-revive-cart-pro' ); ?></strong></label>
							</p>
							<p>
								<input type="text" name="frc_license_key" id="frc_license_key" class="large-text"
									placeholder="FRC-xxxxx-xxxxx" value="" autocomplete="off" />
							</p>
							<p class="description">
								<?php esc_html_e( 'Enter the license key you received after purchasing the Pro add-on.', 'flexi-revive-cart-pro' ); ?>
							</p>
							<p>
								<button type="submit" class="button button-primary">
									<?php esc_html_e( 'Activate License', 'flexi-revive-cart-pro' ); ?>
								</button>
							</p>
						</form>
					<?php endif; ?>

				</div>
			</div>

			<div class="postbox" style="max-width:700px;margin-top:20px;">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Pro Features Included', 'flexi-revive-cart-pro' ); ?></h2>
				</div>
				<div class="inside">
					<ul style="list-style:disc;margin-left:20px;">
						<li><?php esc_html_e( 'Unlimited email reminders with Urgency and Incentive/Discount templates', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Dynamic coupon/discount code generation', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'SMS notifications via Twilio or Plivo', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'WhatsApp messaging and bulk campaigns', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Push notifications via OneSignal', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'A/B subject-line testing with auto-winner detection', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Guest email capture popup', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Exit-intent popup with discount offer', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Browse abandonment follow-up emails', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'Advanced recovery analytics by channel', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'CSV export of abandoned/recovered carts', 'flexi-revive-cart-pro' ); ?></li>
						<li><?php esc_html_e( 'REST API for headless/mobile integrations', 'flexi-revive-cart-pro' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
