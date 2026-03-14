<?php
/**
 * Pro add-on activation: creates Pro-specific database tables.
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_Activator
 */
class FRC_Pro_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		update_option( 'frc_pro_db_version', FRC_PRO_PLUGIN_VERSION );
	}

	/**
	 * Create Pro-specific database tables.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// A/B testing table.
		$sql_ab = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_ab_tests (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			test_name VARCHAR(200) NOT NULL DEFAULT '',
			test_type VARCHAR(50) NOT NULL DEFAULT 'subject_line',
			variant_a TEXT NOT NULL,
			variant_b TEXT NOT NULL,
			variant_a_sent INT(11) DEFAULT 0,
			variant_b_sent INT(11) DEFAULT 0,
			variant_a_opened INT(11) DEFAULT 0,
			variant_b_opened INT(11) DEFAULT 0,
			variant_a_clicked INT(11) DEFAULT 0,
			variant_b_clicked INT(11) DEFAULT 0,
			variant_a_recovered INT(11) DEFAULT 0,
			variant_b_recovered INT(11) DEFAULT 0,
			winner VARCHAR(2) DEFAULT '',
			status VARCHAR(20) DEFAULT 'active',
			started_at DATETIME DEFAULT NULL,
			ended_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_ab );

		// WhatsApp campaigns table.
		$sql_wa = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_whatsapp_campaigns (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_name VARCHAR(200) NOT NULL DEFAULT '',
			message_body TEXT NOT NULL,
			recipients INT(11) DEFAULT 0,
			sent INT(11) DEFAULT 0,
			delivered INT(11) DEFAULT 0,
			read_count INT(11) DEFAULT 0,
			clicks INT(11) DEFAULT 0,
			status VARCHAR(20) DEFAULT 'pending',
			sent_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";
		dbDelta( $sql_wa );

		// Browse abandonment events table.
		$sql_browse = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}frc_browse_events (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			user_email VARCHAR(200) DEFAULT '',
			session_key VARCHAR(100) DEFAULT '',
			product_id BIGINT(20) UNSIGNED NOT NULL,
			product_name VARCHAR(200) DEFAULT '',
			product_url TEXT DEFAULT '',
			viewed_at DATETIME NOT NULL,
			followup_sent TINYINT(1) DEFAULT 0,
			PRIMARY KEY (id),
			KEY user_email (user_email),
			KEY product_id (product_id),
			KEY followup_sent (followup_sent)
		) $charset_collate;";
		dbDelta( $sql_browse );
	}

	/**
	 * Run database upgrades when the Pro version changes.
	 */
	public static function upgrade_db() {
		self::create_tables();
		update_option( 'frc_pro_db_version', FRC_PRO_PLUGIN_VERSION );
	}
}
