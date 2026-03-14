<?php
/**
 * Fired during plugin activation.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Activator
 *
 * Creates database tables and sets default options on plugin activation.
 */
class FRC_Activator {

	/**
	 * Run activation routines.
	 */
	public static function activate() {
		self::create_tables();
		self::set_default_options();
		self::schedule_cron();
		flush_rewrite_rules();
	}

	/**
	 * Run database schema upgrades (called on version change).
	 * Uses dbDelta so it is safe to run even when tables already exist.
	 */
	public static function upgrade_db() {
		self::create_tables();
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Abandoned carts table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_abandoned_carts (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			user_email VARCHAR(200) DEFAULT '',
			session_key VARCHAR(100) DEFAULT '',
			cart_contents LONGTEXT NOT NULL,
			cart_total DECIMAL(10,2) DEFAULT 0.00,
			currency VARCHAR(10) DEFAULT 'USD',
			ip_address VARCHAR(45) DEFAULT '',
			user_agent TEXT DEFAULT '',
			language VARCHAR(10) DEFAULT 'en',
			status ENUM('abandoned','recovered','converted','expired') DEFAULT 'abandoned',
			recovery_token VARCHAR(64) NOT NULL,
			discount_code VARCHAR(50) DEFAULT '',
			recovered_order_id BIGINT(20) UNSIGNED DEFAULT 0,
			recovery_channel VARCHAR(20) DEFAULT '',
			emails_sent INT(3) DEFAULT 0,
			sms_sent INT(3) DEFAULT 0,
			push_sent INT(3) DEFAULT 0,
			last_reminder_at DATETIME DEFAULT NULL,
			abandoned_at DATETIME NOT NULL,
			recovered_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			consent_given TINYINT(1) DEFAULT 0,
			opted_out TINYINT(1) DEFAULT 0,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY user_email (user_email),
			KEY session_key (session_key),
			KEY status (status),
			KEY recovery_token (recovery_token),
			KEY abandoned_at (abandoned_at)
		) ENGINE=InnoDB {$charset_collate};";

		// Email / notification logs table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_email_logs (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			cart_id BIGINT(20) UNSIGNED NOT NULL,
			email_to VARCHAR(200) NOT NULL,
			email_subject VARCHAR(255) NOT NULL,
			email_body LONGTEXT NOT NULL,
			template_id VARCHAR(50) DEFAULT '',
			ab_variant VARCHAR(10) DEFAULT '',
			channel ENUM('email','sms','whatsapp','push') DEFAULT 'email',
			status ENUM('sent','delivered','opened','clicked','bounced','failed') DEFAULT 'sent',
			opened_at DATETIME DEFAULT NULL,
			clicked_at DATETIME DEFAULT NULL,
			sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY cart_id (cart_id),
			KEY status (status),
			KEY channel (channel)
		) ENGINE=InnoDB {$charset_collate};";

		// A/B tests table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_ab_tests (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			test_name VARCHAR(200) NOT NULL,
			test_type ENUM('subject_line','timing','content','channel') DEFAULT 'subject_line',
			variant_a TEXT NOT NULL,
			variant_b TEXT NOT NULL,
			variant_a_sent INT DEFAULT 0,
			variant_a_opened INT DEFAULT 0,
			variant_a_clicked INT DEFAULT 0,
			variant_a_recovered INT DEFAULT 0,
			variant_b_sent INT DEFAULT 0,
			variant_b_opened INT DEFAULT 0,
			variant_b_clicked INT DEFAULT 0,
			variant_b_recovered INT DEFAULT 0,
			status ENUM('active','paused','completed') DEFAULT 'active',
			winner VARCHAR(10) DEFAULT '',
			started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ended_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id)
		) ENGINE=InnoDB {$charset_collate};";

		// Browse events table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_browse_events (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			user_email VARCHAR(200) DEFAULT '',
			session_key VARCHAR(100) DEFAULT '',
			product_id BIGINT(20) UNSIGNED NOT NULL,
			product_name VARCHAR(255) NOT NULL,
			product_url VARCHAR(500) DEFAULT '',
			viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			followup_sent TINYINT(1) DEFAULT 0,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY session_key (session_key),
			KEY product_id (product_id)
		) ENGINE=InnoDB {$charset_collate};";

		// WhatsApp bulk campaigns table.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_whatsapp_campaigns (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_name VARCHAR(200) NOT NULL,
			message_body LONGTEXT NOT NULL,
			recipients INT(11) DEFAULT 0,
			sent INT(11) DEFAULT 0,
			delivered INT(11) DEFAULT 0,
			read_count INT(11) DEFAULT 0,
			clicks INT(11) DEFAULT 0,
			status ENUM('sending','completed','failed') DEFAULT 'sending',
			sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sent_at (sent_at),
			KEY status (status)
		) ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'frc_db_version', FRC_VERSION );
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		$defaults = array(
			'frc_enable_tracking'           => '1',
			'frc_abandonment_timeout'       => 60,
			'frc_auto_delete_days'          => 90,
			'frc_enable_email_reminders'    => '1',
			'frc_num_reminders'             => 3,
			'frc_reminder_interval'         => 1,
			'frc_reminder_type'             => 'friendly',
			'frc_from_name'                 => get_bloginfo( 'name' ),
			'frc_from_email'                => get_option( 'admin_email' ),
			'frc_email_subjects'            => array(
				__( "You left something behind!", 'flexi-revive-cart' ),
				__( "Your cart is waiting – items may sell out!", 'flexi-revive-cart' ),
				__( "Here's a special offer to complete your purchase!", 'flexi-revive-cart' ),
			),
			'frc_backend_language'          => 'en',
			'frc_frontend_language'         => 'en',
			'frc_enable_sms'                => '0',
			'frc_sms_provider'              => 'twilio',
			'frc_enable_push'               => '0',
			'frc_enable_whatsapp'           => '0',
			'frc_whatsapp_provider'         => 'twilio',
			'frc_whatsapp_from'             => '',
			'frc_enable_guest_capture'      => '0',
			'frc_enable_exit_intent'        => '0',
			'frc_enable_auto_discounts'     => '0',
			'frc_discount_percentage'       => 10,
			'frc_coupon_expiry_hours'       => 72,
			'frc_data_retention_days'       => 90,
			'frc_popup_delay_seconds'       => 30,
			'frc_popup_message'             => __( 'Wait! Don\'t leave your cart behind.', 'flexi-revive-cart' ),
			'frc_popup_button_text'         => __( 'Save My Cart', 'flexi-revive-cart' ),
			'frc_browse_followup_hours'     => 2,
		);

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				update_option( $option, $value );
			}
		}
	}

	/**
	 * Schedule the recurring abandoned-cart check cron event.
	 */
	private static function schedule_cron() {
		// Register the custom interval immediately so wp_schedule_event() accepts it.
		if ( ! isset( wp_get_schedules()['frc_15_minutes'] ) ) {
			add_filter(
				'cron_schedules',
				function ( $schedules ) {
					$schedules['frc_15_minutes'] = array(
						'interval' => 900,
						'display'  => __( 'Every 15 Minutes (FRC)', 'flexi-revive-cart' ),
					);
					return $schedules;
				}
			);
		}

		if ( ! wp_next_scheduled( 'frc_check_abandoned_carts' ) ) {
			wp_schedule_event( time(), 'frc_15_minutes', 'frc_check_abandoned_carts' );
		}
		if ( ! wp_next_scheduled( 'frc_cleanup_old_carts' ) ) {
			wp_schedule_event( time(), 'daily', 'frc_cleanup_old_carts' );
		}
	}
}
