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
		$templates = array(
			'reminder-1' => array(
				'id'       => 'reminder-1',
				'name'     => __( 'Friendly Reminder', 'flexi-revive-cart' ),
				'file'     => 'emails/reminder-1.php',
				'stage'    => 1,
			),
		);

		/**
		 * Filters the available email templates.
		 *
		 * Pro can add additional templates (e.g. reminder-2, reminder-3).
		 *
		 * @param array $templates Associative array of template definitions.
		 */
		return apply_filters( 'frc_email_templates', $templates );
	}

	/**
	 * Map a reminder type string to a template ID.
	 *
	 * @param string $type Reminder type (e.g. 'friendly'). Pro can add more via filter.
	 * @return string Template ID (e.g. 'reminder-1').
	 */
	public static function get_template_id_for_type( $type ) {
		$map = array(
			'friendly' => 'reminder-1',
		);

		/**
		 * Filters the reminder type to template ID map.
		 *
		 * Pro can add additional mappings (e.g. 'urgency' => 'reminder-2').
		 *
		 * @param array $map Associative array of type => template_id.
		 */
		$map = apply_filters( 'frc_template_type_map', $map );

		return isset( $map[ $type ] ) ? $map[ $type ] : 'reminder-1';
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
		$lang        = self::validate_lang( $lang );
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
			'en'    => __( 'English', 'flexi-revive-cart' ),
			'es'    => __( 'Spanish', 'flexi-revive-cart' ),
			'fr'    => __( 'French', 'flexi-revive-cart' ),
			'de'    => __( 'German', 'flexi-revive-cart' ),
			'it'    => __( 'Italian', 'flexi-revive-cart' ),
			'hi'    => __( 'Hindi', 'flexi-revive-cart' ),
			'pt_BR' => __( 'Portuguese (Brazil)', 'flexi-revive-cart' ),
			'zh_CN' => __( 'Chinese (Simplified)', 'flexi-revive-cart' ),
			'ar'    => __( 'Arabic', 'flexi-revive-cart' ),
			'ja'    => __( 'Japanese', 'flexi-revive-cart' ),
		);
	}

	/**
	 * Return array of supported language codes.
	 *
	 * @return array
	 */
	public static function get_supported_language_codes() {
		return array_keys( self::get_supported_languages() );
	}

	/**
	 * Validate a language code, returning 'en' if unsupported.
	 *
	 * @param string $lang Language code.
	 * @return string Valid language code.
	 */
	public static function validate_lang( $lang ) {
		$supported = self::get_supported_language_codes();
		return in_array( $lang, $supported, true ) ? $lang : 'en';
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
		$lang    = self::validate_lang( $lang );
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
		$lang = self::validate_lang( $lang );

		$defaults = array(
			'reminder-1' => array(
				'en'    => __( 'Hi {user_name}, you left something behind at {store_name}!', 'flexi-revive-cart' ),
				'es'    => __( 'Hola {user_name}, dejaste algo en {store_name}!', 'flexi-revive-cart' ),
				'fr'    => __( 'Bonjour {user_name}, vous avez oublié quelque chose chez {store_name} !', 'flexi-revive-cart' ),
				'de'    => __( 'Hallo {user_name}, Sie haben etwas bei {store_name} vergessen!', 'flexi-revive-cart' ),
				'it'    => __( 'Ciao {user_name}, hai dimenticato qualcosa su {store_name}!', 'flexi-revive-cart' ),
				'hi'    => __( 'नमस्ते {user_name}, आपने {store_name} पर कुछ छोड़ दिया!', 'flexi-revive-cart' ),
				'pt_BR' => __( 'Olá {user_name}, você esqueceu algo na {store_name}!', 'flexi-revive-cart' ),
				'zh_CN' => __( '{user_name}，您在 {store_name} 有未完成的订单！', 'flexi-revive-cart' ),
				'ar'    => __( 'مرحباً {user_name}، لقد تركت شيئاً في {store_name}!', 'flexi-revive-cart' ),
				'ja'    => __( '{user_name}様、{store_name}にお忘れ物があります！', 'flexi-revive-cart' ),
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
		$lang = self::validate_lang( $lang );

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

				'it' => '<p>Ciao {user_name},</p>
<p>Hai lasciato degli articoli nel carrello su {store_name}. Non preoccuparti, li abbiamo salvati per te!</p>
{cart_items}
<p><strong>Totale: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Completa l\'acquisto</a></p>
<p style="font-size:12px;color:#999;">Il tuo carrello sarà salvato per 72 ore. | <a href="{unsubscribe_link}">Annulla iscrizione</a></p>',

				'hi' => '<p>नमस्ते {user_name},</p>
<p>आपने {store_name} पर अपनी कार्ट में कुछ आइटम छोड़ दिए हैं। चिंता न करें – हमने उन्हें आपके लिए सुरक्षित रखा है!</p>
{cart_items}
<p><strong>कुल: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">खरीदारी पूरी करें</a></p>
<p style="font-size:12px;color:#999;">आपकी कार्ट 72 घंटे तक सुरक्षित रहेगी। | <a href="{unsubscribe_link}">सदस्यता रद्द करें</a></p>',

				'pt_BR' => '<p>Olá {user_name},</p>
<p>Você deixou alguns itens no carrinho da {store_name}. Não se preocupe, nós os salvamos para você!</p>
{cart_items}
<p><strong>Total: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">Finalizar Compra</a></p>
<p style="font-size:12px;color:#999;">Seu carrinho será salvo por 72 horas. | <a href="{unsubscribe_link}">Cancelar inscrição</a></p>',

				'zh_CN' => '<p>{user_name}，您好！</p>
<p>您在 {store_name} 的购物车中有未结算的商品。别担心，我们已经为您保存了！</p>
{cart_items}
<p><strong>合计：{cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">完成购买</a></p>
<p style="font-size:12px;color:#999;">您的购物车将保存72小时。 | <a href="{unsubscribe_link}">取消订阅</a></p>',

				'ar' => '<p>مرحباً {user_name}،</p>
<p>لقد تركت بعض المنتجات في سلة التسوق على {store_name}. لا تقلق – لقد حفظناها لك!</p>
{cart_items}
<p><strong>المجموع: {cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">أكمل عملية الشراء</a></p>
<p style="font-size:12px;color:#999;">سيتم حفظ سلة التسوق لمدة 72 ساعة. | <a href="{unsubscribe_link}">إلغاء الاشتراك</a></p>',

				'ja' => '<p>{user_name}様、こんにちは。</p>
<p>{store_name}のカートに商品が残っています。ご安心ください。お客様のために保存しております。</p>
{cart_items}
<p><strong>合計：{cart_total}</strong></p>
<p><a href="{recovery_link}" style="background:#7f54b3;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;display:inline-block;">購入を完了する</a></p>
<p style="font-size:12px;color:#999;">カートは72時間保存されます。 | <a href="{unsubscribe_link}">配信停止</a></p>',
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
	 * Return the legacy default email subjects array (used as fallback for backward compatibility).
	 *
	 * @return array Indexed array of default subject strings.
	 */
	public static function get_legacy_default_subjects() {
		return array(
			__( 'You left something behind!', 'flexi-revive-cart' ),
		);
	}

	/**
	 * Return allowed placeholders for a given template type.
	 *
	 * Free version only supports base placeholders for the friendly reminder.
	 * Pro can extend this via the frc_allowed_placeholders filter.
	 *
	 * @param string $template_id Template ID (e.g. 'reminder-1').
	 * @return array List of allowed placeholder names (without braces).
	 */
	public static function get_allowed_placeholders( $template_id ) {
		$base = array(
			'user_name',
			'cart_items',
			'cart_total',
			'recovery_link',
			'cart_link',
			'store_name',
			'abandoned_time',
			'unsubscribe_link',
			'tracking_pixel',
		);

		/**
		 * Filters the allowed placeholders for a template.
		 *
		 * Pro can add discount and other placeholders for additional templates.
		 *
		 * @param array  $placeholders List of allowed placeholder names.
		 * @param string $template_id  The template ID.
		 */
		return apply_filters( 'frc_allowed_placeholders', $base, $template_id );
	}

	/**
	 * Return the list of Pro-only placeholder names (without braces).
	 *
	 * These placeholders are used by the email editor UI to indicate
	 * which placeholders require the Pro add-on.
	 *
	 * @return array
	 */
	public static function get_pro_only_placeholders() {
		return array( 'discount_code', 'discount_amount', 'discount_expiry', 'cart_expiry', 'low_stock_alert' );
	}

	/**
	 * Validate and sanitize placeholders in email content based on template type.
	 *
	 * In Free version, Pro-only placeholders are replaced with empty strings.
	 *
	 * @param string $content     Email body or subject.
	 * @param string $template_id Template ID.
	 * @return string Sanitized content.
	 */
	public static function validate_placeholders( $content, $template_id ) {
		/**
		 * Filters the list of Pro-only placeholders.
		 *
		 * Pro can return an empty array to allow all placeholders.
		 *
		 * @param array $placeholders List of Pro-only placeholder names.
		 */
		$pro_placeholders = apply_filters( 'frc_pro_only_placeholders', self::get_pro_only_placeholders() );
		if ( ! empty( $pro_placeholders ) ) {
			foreach ( $pro_placeholders as $placeholder ) {
				$content = str_replace( '{' . $placeholder . '}', '', $content );
			}
		}

		return $content;
	}

	/**
	 * Log a missing translation or placeholder issue for debugging.
	 *
	 * @param string $message Debug message.
	 */
	public static function log_missing_translation( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'FRC Translation: ' . $message );
		}
	}
}
