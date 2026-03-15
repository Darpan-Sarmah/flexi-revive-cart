<?php
/**
 * Abandoned carts list table.
 *
 * @package FlexiReviveCart
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class FRC_Admin_Carts
 *
 * Extends WP_List_Table to display abandoned carts.
 */
class FRC_Admin_Carts extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Cart', 'flexi-revive-cart' ),
			'plural'   => __( 'Carts', 'flexi-revive-cart' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Render the page.
	 */
	public function render() {
		// Handle CSV export before any output is generated.
		if ( isset( $_GET['frc_export_csv'] ) && frc_is_pro_licensed() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'frc_export_csv' ) ) {
				$this->handle_csv_export();
				exit;
			}
		}

		$this->handle_bulk_actions();
		$this->prepare_items();

		?>
		<div class="wrap frc-wrap">
			<h1>
				<?php esc_html_e( 'Abandoned Carts', 'flexi-revive-cart' ); ?>
				<?php if ( frc_is_pro_licensed() ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=frc-carts&frc_export_csv=1' . ( isset( $_GET['status'] ) ? '&status=' . sanitize_key( wp_unslash( $_GET['status'] ) ) : '' ) ), 'frc_export_csv' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Export CSV', 'flexi-revive-cart' ); ?>
				</a>
				<?php endif; ?>
			</h1>

			<form method="get">
				<input type="hidden" name="page" value="frc-carts" />
				<?php
				$this->search_box( __( 'Search Carts', 'flexi-revive-cart' ), 'frc_cart_search' );
				$this->render_filters();
				$this->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Stream all abandoned cart records as a CSV download.
	 *
	 * Only reachable when `frc_is_pro_licensed()` returns true (the render()
	 * method checks this before calling here).  The Pro add-on can extend the
	 * column list via the `frc_export_csv_columns` filter and each row's values
	 * via the `frc_export_csv_row` filter.
	 */
	private function handle_csv_export() {
		global $wpdb;

		/**
		 * Filters the column headers used in the CSV export.
		 *
		 * Pro can add or reorder columns (e.g. 'recovery_channel', 'discount_code').
		 *
		 * @param array $columns Associative array of column_key => column_label.
		 */
		$columns = apply_filters(
			'frc_export_csv_columns',
			array(
				'id'           => __( 'ID', 'flexi-revive-cart' ),
				'user_email'   => __( 'Customer Email', 'flexi-revive-cart' ),
				'cart_total'   => __( 'Cart Total', 'flexi-revive-cart' ),
				'currency'     => __( 'Currency', 'flexi-revive-cart' ),
				'status'       => __( 'Status', 'flexi-revive-cart' ),
				'emails_sent'  => __( 'Emails Sent', 'flexi-revive-cart' ),
				'abandoned_at' => __( 'Abandoned At', 'flexi-revive-cart' ),
				'recovered_at' => __( 'Recovered At', 'flexi-revive-cart' ),
			)
		);

		// Build WHERE clause from query-string filters (same as prepare_items).
		// $where is only ever appended with literal SQL fragments containing %s placeholders;
		// all user-supplied values go through $params and are handled by $wpdb->prepare().
		$where  = '1=1';
		$params = array();

		$status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $status ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY id DESC", $params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY id DESC" );
		}

		// sanitize_file_name() removes any characters that could be used for header injection.
		$filename = sanitize_file_name( 'frc-abandoned-carts-' . gmdate( 'Y-m-d' ) . '.csv' );

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// UTF-8 BOM so Excel opens the file correctly.
		fwrite( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		// Header row.
		fputcsv( $output, array_values( $columns ) );

		foreach ( $rows as $row ) {
			$values = array();
			foreach ( array_keys( $columns ) as $col ) {
				$values[ $col ] = isset( $row->$col ) ? $row->$col : '';
			}

			/**
			 * Filters a single CSV row before it is written.
			 *
			 * Pro can add extra columns that were registered via frc_export_csv_columns.
			 *
			 * @param array  $values Associative array of column_key => value.
			 * @param object $row    The full database row.
			 */
			$values = apply_filters( 'frc_export_csv_row', $values, $row );

			fputcsv( $output, array_values( $values ) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Render filter dropdowns.
	 */
	private function render_filters() {
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="frc-filter-bar">
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'flexi-revive-cart' ); ?></option>
				<option value="abandoned" <?php selected( $status, 'abandoned' ); ?>><?php esc_html_e( 'Abandoned', 'flexi-revive-cart' ); ?></option>
				<option value="recovered" <?php selected( $status, 'recovered' ); ?>><?php esc_html_e( 'Recovered', 'flexi-revive-cart' ); ?></option>
				<option value="converted" <?php selected( $status, 'converted' ); ?>><?php esc_html_e( 'Converted', 'flexi-revive-cart' ); ?></option>
				<option value="expired" <?php selected( $status, 'expired' ); ?>><?php esc_html_e( 'Expired', 'flexi-revive-cart' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'flexi-revive-cart' ), 'action', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Define table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'id'            => __( 'ID', 'flexi-revive-cart' ),
			'user_email'    => __( 'Customer Email', 'flexi-revive-cart' ),
			'cart_total'    => __( 'Cart Total', 'flexi-revive-cart' ),
			'items_count'   => __( 'Items', 'flexi-revive-cart' ),
			'status'        => __( 'Status', 'flexi-revive-cart' ),
			'abandoned_at'  => __( 'Abandoned', 'flexi-revive-cart' ),
			'emails_sent'   => __( 'Emails Sent', 'flexi-revive-cart' ),
			'actions'       => __( 'Actions', 'flexi-revive-cart' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'id'           => array( 'id', false ),
			'cart_total'   => array( 'cart_total', false ),
			'abandoned_at' => array( 'abandoned_at', true ),
		);
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete'          => __( 'Delete', 'flexi-revive-cart' ),
			'resend_reminder' => __( 'Resend Reminder', 'flexi-revive-cart' ),
		);
	}

	/**
	 * Process bulk actions.
	 */
	private function handle_bulk_actions() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-carts' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$action   = $this->current_action();
		$cart_ids = isset( $_REQUEST['cart_ids'] ) ? array_map( 'absint', wp_unslash( $_REQUEST['cart_ids'] ) ) : array();

		if ( empty( $cart_ids ) ) {
			return;
		}

		global $wpdb;

		if ( 'delete' === $action ) {
			$count = 0;
			foreach ( $cart_ids as $id ) {
				$result = $wpdb->delete( $wpdb->prefix . 'frc_abandoned_carts', array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				if ( $result ) {
					$count++;
				}
			}
			add_action( 'admin_notices', function () use ( $count ) {
				echo '<div class="notice notice-success is-dismissible"><p>' .
					esc_html( sprintf(
						/* translators: %d: number of carts deleted */
						_n( '%d cart deleted.', '%d carts deleted.', $count, 'flexi-revive-cart' ),
						$count
					) ) .
					'</p></div>';
			} );
		} elseif ( 'resend_reminder' === $action ) {
			$sent          = 0;
			$max_stage     = min( (int) get_option( 'frc_num_reminders', 3 ), apply_filters( 'frc_max_reminders', 3 ) );
			$email_manager = new FRC_Email_Manager();
			foreach ( $cart_ids as $id ) {
				$cart = FRC_Helpers::get_cart_by_id( $id );
				if ( ! $cart ) {
					continue;
				}
				$stage = min( (int) $cart->emails_sent + 1, $max_stage );
				if ( $email_manager->send_reminder( $cart, $stage ) ) {
					$sent++;
				}
			}
			add_action( 'admin_notices', function () use ( $sent ) {
				echo '<div class="notice notice-success is-dismissible"><p>' .
					esc_html( sprintf(
						/* translators: %d: number of reminders sent */
						_n( '%d reminder sent.', '%d reminders sent.', $sent, 'flexi-revive-cart' ),
						$sent
					) ) .
					'</p></div>';
			} );
		}
	}

	/**
	 * Populate the items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$where  = '1=1';
		$params = array();

		// Status filter.
		$status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $status ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		// Search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $search ) {
			$where   .= ' AND user_email LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Order.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'id'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $orderby ) {
			$orderby = 'id';
		}
		$order   = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$allowed_orderby = array( 'id', 'cart_total', 'abandoned_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}

		// Total count.
		$count_params = $params;
		if ( ! empty( $count_params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where}", $count_params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where}" );
		}

		// Fetch items.
		$params[] = $per_page;
		$params[] = $offset;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}frc_abandoned_carts WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", $params ) );

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
	}

	/**
	 * Checkbox column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="cart_ids[]" value="' . esc_attr( $item->id ) . '" />';
	}

	/**
	 * ID column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_id( $item ) {
		return '#' . esc_html( $item->id );
	}

	/**
	 * Email column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_user_email( $item ) {
		return esc_html( $item->user_email );
	}

	/**
	 * Cart total column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_cart_total( $item ) {
		return wp_kses_post( FRC_Helpers::format_currency( $item->cart_total, $item->currency ) );
	}

	/**
	 * Items count column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_items_count( $item ) {
		$items = FRC_Helpers::unserialize_cart( $item->cart_contents );
		return esc_html( count( $items ) );
	}

	/**
	 * Status column with badge.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_status( $item ) {
		$classes = array(
			'abandoned' => 'frc-badge-abandoned',
			'recovered' => 'frc-badge-recovered',
			'converted' => 'frc-badge-recovered',
			'expired'   => 'frc-badge-expired',
		);
		$class = isset( $classes[ $item->status ] ) ? $classes[ $item->status ] : '';
		return '<span class="frc-badge ' . esc_attr( $class ) . '">' . esc_html( ucfirst( $item->status ) ) . '</span>';
	}

	/**
	 * Abandoned at column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_abandoned_at( $item ) {
		return esc_html( $item->abandoned_at ) . '<br><small>' . esc_html( FRC_Helpers::time_ago( $item->abandoned_at ) ) . '</small>';
	}

	/**
	 * Emails sent column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_emails_sent( $item ) {
		return esc_html( $item->emails_sent );
	}

	/**
	 * Actions column.
	 *
	 * @param object $item Row data.
	 * @return string
	 */
	public function column_actions( $item ) {
		$nonce = wp_create_nonce( 'frc_admin_nonce' );
		$html  = '<button type="button" class="button button-small frc-resend-reminder" data-id="' . esc_attr( $item->id ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Resend', 'flexi-revive-cart' ) . '</button> ';
		$html .= '<button type="button" class="button button-small frc-delete-cart" data-id="' . esc_attr( $item->id ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Delete', 'flexi-revive-cart' ) . '</button>';
		return $html;
	}

	/**
	 * Default column handler.
	 *
	 * @param object $item        Row data.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '–';
	}
}
