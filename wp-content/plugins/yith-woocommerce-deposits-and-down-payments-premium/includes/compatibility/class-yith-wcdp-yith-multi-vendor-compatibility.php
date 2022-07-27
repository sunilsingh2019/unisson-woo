<?php
/**
 * Compatibility class with Multi Vendor
 *
 * @author  YITH
 * @package YITH\Deposits\Classes\Compatibilities
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_YITH_Multi_Vendor_Compatibility' ) ) {
	/**
	 * Deposit - Multi vendor compatibility
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_YITH_Multi_Vendor_Compatibility {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_YITH_Multi_Vendor_Compatibility
		 * @since 1.0.5
		 */
		protected static $instance;

		/**
		 * Constructor method
		 *
		 * @return \YITH_WCDP_YITH_Multi_Vendor_Compatibility
		 */
		public function __construct() {
			// admin order view handling.
			add_filter( 'request', array( $this, 'filter_order_list' ), 15, 1 );

			// filter suborders.
			add_filter( 'yith_wcdp_suboder', array( $this, 'get_suborder' ), 10, 2 );
		}

		/* === ORDER VIEW METHODS === */

		/**
		 * Only show parent orders
		 *
		 * @param array $query Query arguments.
		 *
		 * @return array Modified request
		 * @since  1.0.0
		 */
		public function filter_order_list( $query ) {
			global $typenow, $wpdb;

			$vendor = yith_get_vendor( 'current', 'user' );

			if ( ! $vendor->is_valid() || ! $vendor->has_limited_access() ) {
				return $query;
			}

			// retrieve balance orders.
			$balance_ids = YITH_WCDP_Suborders()->get_all_balances_ids();

			if ( 'shop_order' === $typenow ) {
				$query['post_parent__not_in'] = $balance_ids;
			}

			return $query;
		}

		/**
		 * Check if order identified by $order_id has suborders, and eventually returns them
		 *
		 * @param array $suborders Array of suborders.
		 * @param int   $order_id  Id of the order to check.
		 *
		 * @return mixed Array of suborders, if any
		 * @since 1.0.0
		 */
		public function get_suborder( $suborders, $order_id ) {

			$vendor = yith_get_vendor( 'current', 'user' );

			if ( ! $vendor->is_valid() || ! $vendor->has_limited_access() ) {
				return $suborders;
			}

			$parent = wp_get_post_parent_id( $order_id );

			if ( ! $parent ) {
				return $suborders;
			}

			remove_filter( 'yith_wcdp_suboder', array( $this, 'get_suborder' ) );
			$parent_suborders = YITH_WCDP_Suborders()->get_suborder( $parent );

			if ( empty( $parent_suborders ) ) {
				return $suborders;
			}

			$suborders = array();

			foreach ( $parent_suborders as $id ) {
				$suborders = array_merge( $suborders, YITH_Orders::get_suborder( $id ) );
			}

			add_filter( 'yith_wcdp_suboder', array( $this, 'get_suborder' ), 10, 2 );

			$vendor_suborder_ids = array_merge( $vendor->get_orders( 'suborder' ), $vendor->get_orders( 'quote' ) );

			$suborders = array_intersect( $suborders, $vendor_suborder_ids );

			return $suborders;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_YITH_Multi_Vendor_Compatibility
		 * @since 1.0.5
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}
