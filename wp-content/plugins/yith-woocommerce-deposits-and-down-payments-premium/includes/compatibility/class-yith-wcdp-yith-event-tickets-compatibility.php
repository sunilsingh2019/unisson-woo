<?php
/**
 * Compatibility class with Event Tickets
 *
 * @author  YITH
 * @package YITH\Deposits\Classes\Compatibilities
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_YITH_Event_Tickets_Compatibility' ) ) {
	/**
	 * Deposit - Event ticket compatibility
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_YITH_Event_Tickets_Compatibility {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_YITH_Event_Tickets_Compatibility
		 * @since 1.0.5
		 */
		protected static $instance;

		/**
		 * Constructor method
		 *
		 * @since 1.0.5
		 */
		public function __construct() {
			add_action( 'yith_wcdp_ticket-event_add_to_cart', array( $this, 'print_single_add_deposit_to_cart_template' ) );
			add_filter( 'yith_wcevti_process_tickets_for_item', array( $this, 'skip_tickets_on_deposit' ), 10, 4 );
		}

		/**
		 * Print template for Deposit Add to Cart on single event page
		 *
		 * @return void
		 * @since 1.0.5
		 */
		public function print_single_add_deposit_to_cart_template() {
			add_action( 'woocommerce_before_add_to_cart_button', array( YITH_WCDP_Frontend_Premium(), 'print_single_add_deposit_to_cart_template' ) );
		}

		/**
		 * Skip ticket creation for deposit orders
		 *
		 * @param bool          $process       Whether to process current item.
		 * @param WC_Order_Item $order_item    Order item.
		 * @param int           $order_item_id Order item id.
		 * @param WC_Order      $order         Order.
		 *
		 * @return bool Whether to process item or not.
		 */
		public function skip_tickets_on_deposit( $process, $order_item, $order_item_id, $order ) {
			return ! $order_item->get_meta( '_deposit', true );
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_YITH_Event_Tickets_Compatibility
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
