<?php
/**
 * Deposit Handler class
 *
 * @author  YITH
 * @package YITH\Deposits\Classes
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Deposits_Handler' ) ) {
	/**
	 * WooCommerce Deposits Handler
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Deposits_Handler {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Deposits_Handler
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Constructor method
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// print panel methods.
			add_action( 'yith_wcdp_deposits_panel', array( $this, 'print_deposits_panel' ) );

			// handle panel requests.
			add_action( 'admin_init', array( $this, 'add_role_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_update_roles_deposit', array( $this, 'update_role_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_delete_roles_deposit', array( $this, 'delete_role_deposit' ) );

			add_action( 'admin_init', array( $this, 'add_product_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_update_products_deposit', array( $this, 'update_product_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_delete_products_deposit', array( $this, 'delete_product_deposit' ) );

			add_action( 'admin_init', array( $this, 'add_category_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_update_categories_deposit', array( $this, 'update_category_deposit' ) );
			add_action( 'wp_ajax_yith_wcdp_delete_categories_deposit', array( $this, 'delete_category_deposit' ) );
		}

		/* === DEPOSIT HANDLER METHOD === */

		/**
		 * Add a user role rate
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_role_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			if ( ! isset( $_POST['yith_new_user_role_deposit'] ) ) {
				return;
			}

			$role  = isset( $_POST['yith_new_user_role_deposit']['role'] ) ? sanitize_title( wp_unslash( $_POST['yith_new_user_role_deposit']['role'] ) ) : '0';
			$value = isset( $_POST['yith_new_user_role_deposit']['value'] ) ? floatval( $_POST['yith_new_user_role_deposit']['value'] ) : 0;
			$type  = ( isset( $_POST['yith_new_user_role_deposit']['type'] ) && in_array(
				$_POST['yith_new_user_role_deposit']['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['yith_new_user_role_deposit']['type'] ) ) : 'amount';

			if ( empty( $role ) ) {
				return;
			}

			$stored_values = get_option( 'yith_wcdp_user_role_deposits', array() );

			$stored_values[ $role ] = array(
				'type'  => $type,
				'value' => $value,
			);
			update_option( 'yith_wcdp_user_role_deposits', $stored_values );
		}

		/**
		 * Update user role deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function update_role_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$role  = isset( $_POST['role_slug'] ) ? sanitize_title( wp_unslash( $_POST['role_slug'] ) ) : '';
			$value = isset( $_POST['value'] ) ? floatval( $_POST['value'] ) : 0;
			$type  = ( isset( $_POST['type'] ) && in_array(
				$_POST['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['type'] ) ) : 'amount';

			if ( empty( $role ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_user_role_deposits', array() );

			$registered_deposits[ $role ] = array(
				'type'  => $type,
				'value' => $value,
			);
			update_option( 'yith_wcdp_user_role_deposits', $registered_deposits );

			wp_send_json( $registered_deposits );
		}

		/**
		 * Delete role deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function delete_role_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$role = isset( $_POST['role_slug'] ) ? sanitize_title( wp_unslash( $_POST['role_slug'] ) ) : 0;

			if ( empty( $role ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_user_role_deposits', array() );

			if ( isset( $registered_deposits[ $role ] ) ) {
				unset( $registered_deposits[ $role ] );
			}

			update_option( 'yith_wcdp_user_role_deposits', $registered_deposits );

			wp_send_json( $registered_deposits );
		}

		/**
		 * Add a product deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_product_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			if ( ! isset( $_POST['yith_new_product_deposit'] ) ) {
				return;
			}

			$product_id = isset( $_POST['yith_new_product_deposit']['product'] ) ? intval( $_POST['yith_new_product_deposit']['product'] ) : 0;
			$value      = isset( $_POST['yith_new_product_deposit']['value'] ) ? floatval( $_POST['yith_new_product_deposit']['value'] ) : 0;
			$type       = ( isset( $_POST['yith_new_product_deposit']['type'] ) && in_array(
				$_POST['yith_new_product_deposit']['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['yith_new_product_deposit']['type'] ) ) : 'amount';

			if ( empty( $product_id ) ) {
				return;
			}

			$stored_values = get_option( 'yith_wcdp_product_deposits', array() );

			$stored_values[ $product_id ] = array(
				'type'  => $type,
				'value' => $value,
			);

			update_option( 'yith_wcdp_product_deposits', $stored_values );

			// enable deposit for product.
			if ( apply_filters( 'yith_wcdp_auto_enable_product_deposit', true, $product_id ) ) {
				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					return;
				}

				$product->update_meta_data( '_enable_deposit', 'yes' );
				$product->save();
			}
		}

		/**
		 * Update a product deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function update_product_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$product = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
			$value   = isset( $_POST['value'] ) ? floatval( $_POST['value'] ) : 0;
			$type    = ( isset( $_POST['type'] ) && in_array(
				$_POST['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['type'] ) ) : 'amount';

			if ( empty( $product ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_product_deposits', array() );

			$registered_deposits[ $product ] = array(
				'type'  => $type,
				'value' => $value,
			);

			update_option( 'yith_wcdp_product_deposits', $registered_deposits );

			wp_send_json( $registered_deposits );
		}

		/**
		 * Delete a product deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function delete_product_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$product = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

			if ( empty( $product ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_product_deposits', array() );

			if ( isset( $registered_deposits[ $product ] ) ) {
				unset( $registered_deposits[ $product ] );
			}

			update_option( 'yith_wcdp_product_deposits', $registered_deposits );

			wp_send_json( $registered_deposits );
		}

		/**
		 * Add a product category deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_category_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			if ( ! isset( $_POST['yith_new_product_category_deposit'] ) ) {
				return;
			}

			$term  = isset( $_POST['yith_new_product_category_deposit']['term'] ) ? intval( $_POST['yith_new_product_category_deposit']['term'] ) : 0;
			$value = isset( $_POST['yith_new_product_category_deposit']['value'] ) ? floatval( $_POST['yith_new_product_category_deposit']['value'] ) : 0;
			$type  = ( isset( $_POST['yith_new_product_category_deposit']['type'] ) && in_array(
				$_POST['yith_new_product_category_deposit']['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['yith_new_product_category_deposit']['type'] ) ) : 'amount';

			if ( empty( $term ) ) {
				return;
			}

			$stored_values = get_option( 'yith_wcdp_category_deposits', array() );

			$stored_values[ $term ] = array(
				'type'  => $type,
				'value' => $value,
			);

			update_option( 'yith_wcdp_category_deposits', $stored_values );

			$term_products = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'id',
							'terms'    => $term,
						),
					),
					'posts_per_page' => - 1,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $term_products ) ) {
				foreach ( $term_products as $product_id ) {
					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue;
					}

					// enable deposit for product.
					if ( apply_filters( 'yith_wcdp_auto_enable_product_deposit', true, $product_id ) ) {
						$product->update_meta_data( '_enable_deposit', 'yes' );
						$product->save();
					}
				}
			}
		}

		/**
		 * Update a category deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function update_category_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$term  = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;
			$value = isset( $_POST['value'] ) ? floatval( $_POST['value'] ) : 0;
			$type  = ( isset( $_POST['type'] ) && in_array(
				$_POST['type'],
				array(
					'amount',
					'rate',
				),
				true
			) ) ? sanitize_title( wp_unslash( $_POST['type'] ) ) : 'amount';

			if ( empty( $term ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_category_deposits', array() );

			$registered_deposits[ $term ] = array(
				'type'  => $type,
				'value' => $value,
			);

			update_option( 'yith_wcdp_category_deposits', $registered_deposits );

			wp_send_json( $registered_deposits );
		}

		/**
		 * Delete a category deposit
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function delete_category_deposit() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'deposits' ) ) {
				return;
			}

			$term = isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0;

			if ( empty( $term ) ) {
				wp_send_json( false );
			}

			$registered_deposits = get_option( 'yith_wcdp_category_deposits', array() );

			if ( isset( $registered_deposits[ $term ] ) ) {
				unset( $registered_deposits[ $term ] );
			}

			update_option( 'yith_wcdp_category_deposits', $registered_deposits );

			$term_products = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'id',
							'terms'    => $term,
						),
					),
					'posts_per_page' => - 1,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $term_products ) ) {
				foreach ( $term_products as $product_id ) {
					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue;
					}

					// enable deposit for product.
					if ( apply_filters( 'yith_wcdp_auto_disable_product_deposit', true, $product ) ) {
						$product->update_meta_data( '_enable_deposit', 'no' );
						$product->save();
					}
				}
			}

			wp_send_json( $registered_deposits );
		}

		/* === PANEL METHODS === */

		/**
		 * Print deposits panel
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_deposits_panel() {
			// define variables to use in template.
			$product_categories_raw = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
			$product_categories     = array();

			if ( $product_categories_raw ) {
				foreach ( $product_categories_raw as $cat ) {
					$product_categories[ $cat->term_id ] = esc_html( $cat->name );
				}
			}

			$role_deposits_table             = new YITH_WCDP_Role_Deposits_Table();
			$product_deposits_table          = new YITH_WCDP_Product_Deposits_Table();
			$product_category_deposits_table = new YITH_WCDP_Category_Deposits_Table();

			// prepare deposits table items.
			$role_deposits_table->prepare_items();
			$product_deposits_table->prepare_items();
			$product_category_deposits_table->prepare_items();

			// require deposit panel template.
			include YITH_WCDP_DIR . 'templates/admin/deposits-panel.php';
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Deposits_Handler
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

/**
 * Unique access to instance of YITH_WCDP_Admin_Premium class
 *
 * @return \YITH_WCDP_Deposits_Handler
 * @since 1.0.0
 */
function YITH_WCDP_Deposits_Handler() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Deposits_Handler::get_instance();
}
