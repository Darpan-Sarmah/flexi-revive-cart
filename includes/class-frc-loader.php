<?php
/**
 * Plugin loader / bootstrap class.
 *
 * Orchestrates all plugin components and registers actions/filters.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Loader
 *
 * Loads all plugin includes and initialises the plugin.
 */
class FRC_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Registered filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Constructor – load all required files.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
	}

	/**
	 * Load all dependency files.
	 */
	private function load_dependencies() {
		// Core helpers.
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-helpers.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-compliance.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-cart-tracker.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-cart-recovery.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-email-templates.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-email-manager.php';
		require_once FRC_PLUGIN_DIR . 'includes/class-frc-cron-manager.php';

		// Pro features.
		if ( FRC_PRO_ACTIVE ) {
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-discount-manager.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-guest-capture.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-sms-manager.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-push-manager.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-ab-testing.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-browse-abandonment.php';
			// Dashboard class is needed both in admin and via REST API.
			require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-dashboard.php';
			require_once FRC_PLUGIN_DIR . 'includes/class-frc-rest-api.php';
		}

		// Admin.
		if ( is_admin() ) {
			require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin.php';
			if ( ! class_exists( 'FRC_Admin_Dashboard' ) ) {
				require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-dashboard.php';
			}
			require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-settings.php';
			require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-carts.php';

			if ( FRC_PRO_ACTIVE ) {
				require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-email-editor.php';
				require_once FRC_PLUGIN_DIR . 'admin/class-frc-admin-ab-results.php';
			}
		}

		// Public-facing.
		require_once FRC_PLUGIN_DIR . 'public/class-frc-public.php';
	}

	/**
	 * Instantiate and wire up all plugin components.
	 */
	private function init_components() {
		// Core.
		new FRC_Cart_Tracker();
		new FRC_Cart_Recovery();
		new FRC_Cron_Manager();
		new FRC_Compliance();

		// Pro features.
		if ( FRC_PRO_ACTIVE ) {
			new FRC_Guest_Capture();
			new FRC_Browse_Abandonment();
			new FRC_REST_API();
		}

		// Admin.
		if ( is_admin() ) {
			new FRC_Admin();
		}

		// Public.
		new FRC_Public();
	}

	/**
	 * Add an action to the collection.
	 *
	 * @param string $hook          The WordPress hook.
	 * @param object $component     The component instance.
	 * @param string $callback      Method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Add a filter to the collection.
	 *
	 * @param string $hook          The WordPress hook.
	 * @param object $component     The component instance.
	 * @param string $callback      Method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Register all collected hooks with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				array( $filter['component'], $filter['callback'] ),
				$filter['priority'],
				$filter['accepted_args']
			);
		}
		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				array( $action['component'], $action['callback'] ),
				$action['priority'],
				$action['accepted_args']
			);
		}
	}
}
