<?php
/**
 * REST API endpoints. (Pro feature)
 *
 * @package FlexiReviveCartPro
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRC_Pro_REST_API
 */
class FRC_Pro_REST_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const API_NAMESPACE = 'flexi-revive-cart/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::API_NAMESPACE,
			'/carts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_carts' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status'   => array( 'type' => 'string', 'enum' => array( 'abandoned', 'recovered', 'converted', 'expired' ) ),
					'date_from' => array( 'type' => 'string' ),
					'date_to'  => array( 'type' => 'string' ),
					'per_page' => array( 'type' => 'integer', 'default' => 20, 'maximum' => 100 ),
					'page'     => array( 'type' => 'integer', 'default' => 1 ),
				),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/carts/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cart' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array( 'id' => array( 'type' => 'integer', 'required' => true ) ),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/carts/(?P<id>\d+)/recover',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trigger_recovery' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array( 'id' => array( 'type' => 'integer', 'required' => true ) ),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::API_NAMESPACE,
			'/ab-tests',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ab_tests' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You do not have permission to access this endpoint.', 'flexi-revive-cart-pro' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * GET /carts
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_carts( $request ) {
		global $wpdb;

		$status    = $request->get_param( 'status' );
		$date_from = $request->get_param( 'date_from' );
		$date_to   = $request->get_param( 'date_to' );
		$per_page  = (int) $request->get_param( 'per_page' );
		$page      = (int) $request->get_param( 'page' );
		$offset    = ( $page - 1 ) * $per_page;

		$where = '1=1';
		$args  = array();

		if ( $status ) {
			$where .= ' AND status = %s';
			$args[] = $status;
		}
		if ( $date_from ) {
			$where .= ' AND abandoned_at >= %s';
			$args[] = sanitize_text_field( $date_from );
		}
		if ( $date_to ) {
			$where .= ' AND abandoned_at <= %s';
			$args[] = sanitize_text_field( $date_to );
		}

		$args[] = $per_page;
		$args[] = $offset;

		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
			$args
		);

		$carts = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return rest_ensure_response( $carts );
	}

	/**
	 * GET /carts/{id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_cart( $request ) {
		$cart = FRC_Helpers::get_cart_by_id( $request->get_param( 'id' ) );
		if ( ! $cart ) {
			return new WP_Error( 'not_found', __( 'Cart not found.', 'flexi-revive-cart-pro' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $cart );
	}

	/**
	 * POST /carts/{id}/recover
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function trigger_recovery( $request ) {
		$cart = FRC_Helpers::get_cart_by_id( $request->get_param( 'id' ) );
		if ( ! $cart ) {
			return new WP_Error( 'not_found', __( 'Cart not found.', 'flexi-revive-cart-pro' ), array( 'status' => 404 ) );
		}

		$email_manager = new FRC_Email_Manager();
		$stage         = min( (int) $cart->emails_sent + 1, 3 );
		$sent          = $email_manager->send_reminder( $cart, $stage );

		return rest_ensure_response( array(
			'success' => $sent,
			'message' => $sent ? __( 'Reminder sent.', 'flexi-revive-cart-pro' ) : __( 'Failed to send.', 'flexi-revive-cart-pro' ),
		) );
	}

	/**
	 * GET /analytics
	 *
	 * @return WP_REST_Response
	 */
	public function get_analytics() {
		if ( class_exists( 'FRC_Admin_Dashboard' ) ) {
			$dashboard = new FRC_Admin_Dashboard();
			$stats     = $dashboard->get_stats();
			return rest_ensure_response( $stats );
		}
		return rest_ensure_response( array() );
	}

	/**
	 * GET /ab-tests
	 *
	 * @return WP_REST_Response
	 */
	public function get_ab_tests() {
		$ab = new FRC_Pro_AB_Testing();
		return rest_ensure_response( $ab->get_results() );
	}
}
