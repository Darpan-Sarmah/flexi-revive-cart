<?php
/**
 * Email template definitions.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Email_Templates
 *
 * Provides template metadata and variable replacement for email templates.
 */
class FRC_Email_Templates {

	/**
	 * Return all available template definitions.
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'reminder-1' => array(
				'id'       => 'reminder-1',
				'name'     => __( 'Friendly Reminder', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-1.php',
				'stage'    => 1,
			),
			'reminder-2' => array(
				'id'       => 'reminder-2',
				'name'     => __( 'Urgency Reminder', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-2.php',
				'stage'    => 2,
			),
			'reminder-3' => array(
				'id'       => 'reminder-3',
				'name'     => __( 'Incentive / Discount', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-3.php',
				'stage'    => 3,
			),
		);
	}

	/**
	 * Render a template by replacing placeholder variables.
	 *
	 * Checks for a saved custom template (per language) before falling back
	 * to the PHP file template. This supports the free email template editor.
	 *
	 * @param string $template_id Template ID key.
	 * @param array  $vars        Associative array of variable replacements.
	 * @param string $lang        Language code (en, es, fr, de). Defaults to 'en'.
	 * @return string Rendered HTML.
	 */
	public static function render( $template_id, $vars = array(), $lang = 'en' ) {
		$templates = self::get_templates();
		if ( ! isset( $templates[ $template_id ] ) ) {
			return '';
		}

		// Check for a saved custom template (language-specific first, then generic).
		$lang        = in_array( $lang, array( 'en', 'es', 'fr', 'de' ), true ) ? $lang : 'en';
		$lang_key    = 'frc_email_template_' . $template_id . '_' . $lang;
		$generic_key = 'frc_email_template_' . $template_id;

		$custom_content = get_option( $lang_key, '' );
		if ( '' === $custom_content && 'en' !== $lang ) {
			// Fall back to generic (legacy) saved template.
			$custom_content = get_option( $generic_key, '' );
		}

		if ( '' !== $custom_content ) {
			return self::replace_vars( $custom_content, $vars );
		}

		$template_file = FRC_PLUGIN_DIR . 'templates/' . $templates[ $template_id ]['file'];

		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		ob_start();
		// Make variables available within template scope.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $template_file;
		$html = ob_get_clean();

		// Replace template variables.
		$placeholders = array_keys( $vars );
		$values       = array_values( $vars );

		$search  = array_map(
			function ( $k ) {
				return '{' . $k . '}';
			},
			$placeholders
		);

		return str_replace( $search, $values, $html );
	}

	/**
	 * Replace dynamic placeholders in a string.
	 *
	 * @param string $content String with {placeholder} tags.
	 * @param array  $vars    Associative key => value array.
	 * @return string
	 */
	public static function replace_vars( $content, $vars ) {
		foreach ( $vars as $key => $value ) {
			$content = str_replace( '{' . $key . '}', $value, $content );
		}
		return $content;
	}

	/**
	 * Build the replacement variables array for a given cart row.
	 *
	 * @param object $cart              Database cart row.
	 * @param int    $log_id            Email log ID (for tracking links).
	 * @param string $discount_code     Optional discount code.
	 * @param string $discount_amount   Human-readable discount amount string (e.g. "10%" or "$5.00").
	 * @return array
	 */
	public static function build_vars( $cart, $log_id = 0, $discount_code = '', $discount_amount = '' ) {
		// User name.
		$user_name = '';
		if ( $cart->user_id ) {
			$user = get_userdata( (int) $cart->user_id );
			if ( $user ) {
				$user_name = $user->first_name ? $user->first_name : $user->display_name;
			}
		}
		if ( ! $user_name ) {
			$parts     = explode( '@', $cart->user_email );
			$user_name = ucfirst( $parts[0] );
		}

		// Recovery link with tracking.
		$recovery_url = FRC_Helpers::get_recovery_url( $cart->recovery_token );
		if ( $log_id ) {
			$recovery_url = add_query_arg(
				array(
					'frc_channel' => 'email',
					'frc_lid'     => $log_id,
				),
				$recovery_url
			);
		}

		// Pixel / open-tracking URL.
		$pixel_url = add_query_arg(
			array(
				'action' => 'frc_track_open',
				'lid'    => $log_id,
			),
			admin_url( 'admin-ajax.php' )
		);

		// Unsubscribe link.
		$unsubscribe_url = add_query_arg(
			array(
				'frc_optout' => rawurlencode( $cart->recovery_token ),
			),
			home_url( '/' )
		);

		// Cart items HTML.
		$cart_items_html = FRC_Helpers::build_cart_items_html( FRC_Helpers::unserialize_cart( $cart->cart_contents ) );

		return array(
			'user_name'        => esc_html( $user_name ),
			'cart_items'       => $cart_items_html,
			'cart_total'       => wp_kses_post( FRC_Helpers::format_currency( $cart->cart_total, $cart->currency ) ),
			'recovery_link'    => esc_url( $recovery_url ),
			'cart_link'        => esc_url( $recovery_url ),
			'discount_code'    => esc_html( $discount_code ),
			'discount_amount'  => esc_html( $discount_amount ),
			'store_name'       => esc_html( get_bloginfo( 'name' ) ),
			'abandoned_time'   => esc_html( FRC_Helpers::time_ago( $cart->abandoned_at ) ),
			'unsubscribe_link' => esc_url( $unsubscribe_url ),
			'tracking_pixel'   => '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" style="display:none;" alt="" />',
		);
	}

	/**
	 * Return supported languages for email templates.
	 *
	 * @return array Associative array of language code => language label.
	 */
	public static function get_supported_languages() {
		return array(
			'en' => __( 'English', 'flexi-revive-cart' ),
			'es' => __( 'Spanish', 'flexi-revive-cart' ),
			'fr' => __( 'French', 'flexi-revive-cart' ),
			'de' => __( 'German', 'flexi-revive-cart' ),
		);
	}

	/**
	 * Get the email subject for a given template, language, and variable replacement.
	 *
	 * Checks for a saved custom subject (language-specific) first, then falls back
	 * to the built-in default subject for that template and language.
	 *
	 * @param string $template_id Template ID (e.g. 'reminder-1').
	 * @param string $lang        Language code: en, es, fr, de.
	 * @param array  $vars        Optional associative array of variable replacements for dynamic placeholders.
	 * @return string Subject line with placeholders replaced.
	 */
	public static function get_subject( $template_id, $lang = 'en', $vars = array() ) {
		$lang    = in_array( $lang, array( 'en', 'es', 'fr', 'de' ), true ) ? $lang : 'en';
		$key     = 'frc_email_subject_' . $template_id . '_' . $lang;
		$subject = get_option( $key, '' );

		if ( '' === $subject ) {
			$subject = self::get_default_subject( $template_id, $lang );
		}

		if ( ! empty( $vars ) ) {
			$subject = self::replace_vars( $subject, $vars );
		}

		return $subject;
	}

	/**
	 * Return the default email subject for a given template and language.
	 *
	 * @param string $template_id Template ID (e.g. 'reminder-1').
	 * @param string $lang        Language code: en, es, fr, de.
	 * @return string Default subject line.
	 */
	public static function get_default_subject( $template_id, $lang = 'en' ) {
		$lang = in_array( $lang, array( 'en', 'es', 'fr', 'de' ), true ) ? $lang : 'en';

		$defaults = array(
			'reminder-1' => array(
				'en' => __( 'Hi {user_name}, you left something behind at {store_name}!', 'flexi-revive-cart' ),
				'es' => __( 'Hola {user_name}, dejaste algo en {store_name}!', 'flexi-revive-cart' ),
				'fr' => __( 'Bonjour {user_name}, vous avez oublié quelque chose chez {store_name} !', 'flexi-revive-cart' ),
				'de' => __( 'Hallo {user_name}, Sie haben etwas bei {store_name} vergessen!', 'flexi-revive-cart' ),
			),
			'reminder-2' => array(
				'en' => __( '{user_name}, your cart at {store_name} is waiting – items may sell out!', 'flexi-revive-cart' ),
				'es' => __( '{user_name}, tu carrito en {store_name} te espera – ¡los artículos pueden agotarse!', 'flexi-revive-cart' ),
				'fr' => __( '{user_name}, votre panier chez {store_name} vous attend – les articles peuvent s\'épuiser !', 'flexi-revive-cart' ),
				'de' => __( '{user_name}, Ihr Warenkorb bei {store_name} wartet – Artikel können ausverkauft sein!', 'flexi-revive-cart' ),
			),
			'reminder-3' => array(
				'en' => __( '{user_name}, here\'s {discount_amount} off your cart at {store_name}!', 'flexi-revive-cart' ),
				'es' => __( '{user_name}, ¡aquí tienes {discount_amount} de descuento en {store_name}!', 'flexi-revive-cart' ),
				'fr' => __( '{user_name}, voici {discount_amount} de réduction chez {store_name} !', 'flexi-revive-cart' ),
				'de' => __( '{user_name}, hier sind {discount_amount} Rabatt bei {store_name}!', 'flexi-revive-cart' ),
			),
		);

		if ( isset( $defaults[ $template_id ][ $lang ] ) ) {
			return $defaults[ $template_id ][ $lang ];
		}

		// Fall back to English.
		if ( isset( $defaults[ $template_id ]['en'] ) ) {
			return $defaults[ $template_id ]['en'];
		}

		return '';
	}

	/**
	 * Return the default (pre-loaded) template body HTML for a given template and language.
	 *
	 * These are simple, editable starting-point templates without PHP logic.
	 * Variables like {user_name}, {cart_items} etc. are preserved as placeholders.
	 *
	 * @param string $template_id Template ID (e.g. 'reminder-1').
	 * @param string $lang        Language code: en, es, fr, de.
	 * @return string HTML content.
	 */
	public static function get_default_template_content( $template_id, $lang = 'en' ) {
		$lang = in_array( $lang, array( 'en', 'es', 'fr', 'de' ), true ) ? $lang : 'en';

		$defaults = array(
			'reminder-1' => array(
				'en' => '<p>Hi {user_name},</p>
<p>You left some items in your cart at {store_name}. Don&#39;t worry &ndash; we&#39;ve saved them for you!</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Complete Your Purchase</a></p>
<p style="font-size:12px;color:#999;">Your cart will be saved for 72 hours. | <a href="{unsubscribe_link}">Unsubscribe</a></p>',

				'es' => '<p>Hola {user_name},</p>
<p>Dejaste algunos artículos en tu carrito en {store_name}. ¡No te preocupes, los hemos guardado para ti!</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Completar Compra</a></p>
<p style="font-size:12px;color:#999;">Tu carrito se guardará durante 72 horas. | <a href="{unsubscribe_link}">Cancelar suscripción</a></p>',

				'fr' => '<p>Bonjour {user_name},</p>
<p>Vous avez laissé des articles dans votre panier sur {store_name}. Ne vous inquiétez pas, nous les avons sauvegardés pour vous !</p>
{cart_items}
<p><strong>Total : {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Finaliser ma commande</a></p>
<p style="font-size:12px;color:#999;">Votre panier sera sauvegardé pendant 72 heures. | <a href="{unsubscribe_link}">Se désabonner</a></p>',

				'de' => '<p>Hallo {user_name},</p>
<p>Sie haben einige Artikel in Ihrem Warenkorb bei {store_name} gelassen. Keine Sorge – wir haben sie für Sie gespeichert!</p>
{cart_items}
<p><strong>Gesamt: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Kauf abschließen</a></p>
<p style="font-size:12px;color:#999;">Ihr Warenkorb wird 72 Stunden lang gespeichert. | <a href="{unsubscribe_link}">Abmelden</a></p>',
			),

			'reminder-2' => array(
				'en' => '<p>Hi {user_name},</p>
<p>⚠️ Your cart at {store_name} is still waiting – items may sell out soon! You saved it {abandoned_time}.</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#d63638;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Secure My Cart Now</a></p>
<p style="font-size:12px;color:#999;"><a href="{unsubscribe_link}">Unsubscribe</a></p>',

				'es' => '<p>Hola {user_name},</p>
<p>⚠️ Tu carrito en {store_name} sigue esperando, ¡los artículos pueden agotarse pronto! Lo guardaste hace {abandoned_time}.</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#d63638;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Asegurar Mi Carrito Ahora</a></p>
<p style="font-size:12px;color:#999;"><a href="{unsubscribe_link}">Cancelar suscripción</a></p>',

				'fr' => '<p>Bonjour {user_name},</p>
<p>⚠️ Votre panier sur {store_name} vous attend toujours – les articles peuvent se vendre rapidement ! Vous l\'avez sauvegardé il y a {abandoned_time}.</p>
{cart_items}
<p><strong>Total : {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#d63638;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Sécuriser Mon Panier</a></p>
<p style="font-size:12px;color:#999;"><a href="{unsubscribe_link}">Se désabonner</a></p>',

				'de' => '<p>Hallo {user_name},</p>
<p>⚠️ Ihr Warenkorb bei {store_name} wartet noch – Artikel können bald ausverkauft sein! Sie haben ihn vor {abandoned_time} gespeichert.</p>
{cart_items}
<p><strong>Gesamt: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#d63638;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Meinen Warenkorb Sichern</a></p>
<p style="font-size:12px;color:#999;"><a href="{unsubscribe_link}">Abmelden</a></p>',
			),

			'reminder-3' => array(
				'en' => '<p>Hi {user_name},</p>
<p>🎁 Last chance! We saved a special discount just for you.</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p style="font-size:20px;font-weight:bold;border:2px dashed #46b450;display:inline-block;padding:10px 20px;">Use code: {discount_code} for {discount_amount} off!</p>
<p><a href="{recovery_link}" style="background:#46b450;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Claim My Discount</a></p>
<p style="font-size:12px;color:#999;">Discount code expires in 72 hours. | <a href="{unsubscribe_link}">Unsubscribe</a></p>',

				'es' => '<p>Hola {user_name},</p>
<p>🎁 ¡Última oportunidad! Guardamos un descuento especial solo para ti.</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p style="font-size:20px;font-weight:bold;border:2px dashed #46b450;display:inline-block;padding:10px 20px;">Usa el código: {discount_code} para {discount_amount} de descuento!</p>
<p><a href="{recovery_link}" style="background:#46b450;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Reclamar Mi Descuento</a></p>
<p style="font-size:12px;color:#999;">El código caduca en 72 horas. | <a href="{unsubscribe_link}">Cancelar suscripción</a></p>',

				'fr' => '<p>Bonjour {user_name},</p>
<p>🎁 Dernière chance ! Nous avons réservé une remise spéciale rien que pour vous.</p>
{cart_items}
<p><strong>Total : {cart_total}</strong></p>
<p style="font-size:20px;font-weight:bold;border:2px dashed #46b450;display:inline-block;padding:10px 20px;">Utilisez le code : {discount_code} pour {discount_amount} de réduction !</p>
<p><a href="{recovery_link}" style="background:#46b450;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Réclamer Ma Remise</a></p>
<p style="font-size:12px;color:#999;">Le code expire dans 72 heures. | <a href="{unsubscribe_link}">Se désabonner</a></p>',

				'de' => '<p>Hallo {user_name},</p>
<p>🎁 Letzte Chance! Wir haben einen besonderen Rabatt nur für Sie gespeichert.</p>
{cart_items}
<p><strong>Gesamt: {cart_total}</strong></p>
<p style="font-size:20px;font-weight:bold;border:2px dashed #46b450;display:inline-block;padding:10px 20px;">Code verwenden: {discount_code} für {discount_amount} Rabatt!</p>
<p><a href="{recovery_link}" style="background:#46b450;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Meinen Rabatt Beanspruchen</a></p>
<p style="font-size:12px;color:#999;">Der Code läuft in 72 Stunden ab. | <a href="{unsubscribe_link}">Abmelden</a></p>',
			),
		);

		if ( isset( $defaults[ $template_id ][ $lang ] ) ) {
			return $defaults[ $template_id ][ $lang ];
		}

		// Fall back to English.
		if ( isset( $defaults[ $template_id ]['en'] ) ) {
			return $defaults[ $template_id ]['en'];
		}

		return '';
	}
}
