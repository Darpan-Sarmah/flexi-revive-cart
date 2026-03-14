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
			'frc_data_retention_days'       => 90,
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
