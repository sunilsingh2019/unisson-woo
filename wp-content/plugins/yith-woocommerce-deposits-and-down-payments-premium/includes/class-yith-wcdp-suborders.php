<?php
/**
 * Suborder class
 *
 * @author  YITH
 * @package YITH\Deposits
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Suborders' ) ) {
	/**
	 * WooCommerce Deposits and Down Payments
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Suborders {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Suborders
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Temp storage where to store real cart during plugin elaboration that requires a custom cart
		 *
		 * @var \WC_Cart
		 * @since 1.0.0
		 */
		protected $cart;

		/**
		 * Temp storage where to store real applied coupon during plugin elaboration that requires a custom cart
		 *
		 * @var mixed
		 * @since 1.0.0
		 */
		protected $coupons;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_balance_suborder' ), 10, 2 );
			add_action( 'trashed_post', array( $this, 'trash_suborders' ) );
			add_action( 'untrashed_post', array( $this, 'untrash_suborders' ) );
			add_filter( 'yith_wcmv_get_suborder_ids', array( $this, 'remove_deposit_suborder_from_multi_vendor' ), 10, 2 );

			// sync suborders status with deposit order, when it switches to cancelled or failed.
			add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'synch_suborders_with_parent_status' ), 10, 2 );
			add_action( 'woocommerce_order_status_pending_to_failed', array( $this, 'synch_suborders_with_parent_status' ), 10, 2 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'synch_suborders_with_parent_status_failed' ), 10, 3 );

			// avoid payment gateway to reduce stock order for suborders.
			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'skip_reduce_stock_on_suborders' ), 10, 2 );
			add_filter( 'woocommerce_prevent_adjust_line_item_product_stock', array( $this, 'skip_reduce_stock_on_suborder_items' ), 10, 2 );

			// avoid WooCommerce to block suborder processing because of products out of stock (stock was already processed during deposit checkout).
			add_filter( 'woocommerce_order_item_product', array( $this, 'set_suborder_items_as_in_stock' ), 10, 2 );
		}

		/* === SUBORDER METHODS === */

		/**
		 * Create suborders during process checkout, to let user finalize all his/her deposit in a separate balance order
		 *
		 * @param int   $order_id    Processing order id.
		 * @param array $posted_data Array of data posted with checkout.
		 *
		 * @return bool Status of the operation
		 * @since 1.0.0
		 */
		public function create_balance_suborder( $order_id, $posted_data ) {

			if ( ! defined( 'YITH_WCDP_PROCESS_SUBORDERS' ) ) {
				define( 'YITH_WCDP_PROCESS_SUBORDERS', true );
			}

			do_action( 'yith_wcdp_before_suborders_create', $order_id, $posted_data );

			// retrieve order.
			$parent_order = wc_get_order( $order_id );
			$suborders    = array();

			// if no order found, exit.
			if ( ! $parent_order ) {
				return false;
			}

			// if order already process, exit.
			$suborders_meta = $parent_order->get_meta( '_full_payment_orders', true );

			if ( $suborders_meta ) {
				return false;
			}

			if ( ! $this->has_deposits( $parent_order ) ) {
				return false;
			}

			// set has_deposit meta.
			$parent_order->update_meta_data( '_has_deposit', true );

			// retrieve order items.
			$items = $parent_order->get_items( 'line_item' );

			// if no items found, exit.
			if ( empty( $items ) ) {
				return false;
			}

			// retrieve balance_type.
			$balance_type = get_option( 'yith_wcdp_balance_type', 'multiple' );

			// create a balance for each item purchased as deposit.
			if ( 'multiple' === $balance_type ) {
				foreach ( $items as $item_id => $item ) {
					// create suborder(s).
					$new_suborder_id = $this->build_suborder( $order_id, array( $item_id => $item ), $posted_data );

					// register suborder just created.
					if ( $new_suborder_id ) {
						$suborders[] = $new_suborder_id;
					}
				}
			} elseif ( 'single' === $balance_type ) { // create one balance order for all items purchased as deposit.
				// create suborder(s).
				$new_suborder_id = $this->build_suborder( $order_id, $items, $posted_data );

				// register suborder just created.
				if ( $new_suborder_id ) {
					$suborders[] = $new_suborder_id;
				}
			} elseif ( 'none' === $balance_type ) {
				$parent_order->save();
				return true;
			}

			$parent_order->update_meta_data( '_full_payment_orders', $suborders );
			$parent_order->save();

			do_action( 'yith_wcdp_after_suborders_create', $suborders, $order_id, $posted_data );

			return true;
		}

		/**
		 * Change item price, when adding it to temp cart, to let user pay only order balance
		 *
		 * @param array $cart_item_data Array of items added to temp cart.
		 *
		 * @return mixed Filtered cart item data
		 * @since 1.0.0
		 */
		public function set_item_full_amount_price( $cart_item_data ) {
			if ( ! isset( $cart_item_data['_deposit_balance'] ) ) {
				return $cart_item_data;
			}

			$product = isset( $cart_item_data['data'] ) ? $cart_item_data['data'] : false;

			if ( ! $product instanceof WC_Product ) {
				return $cart_item_data;
			}

			$product->set_price( $cart_item_data['_deposit_balance'] );
			$product->update_meta_data( 'yith_wcdp_balance', true );

			return $cart_item_data;
		}

		/**
		 * Trash suborders on parent order trashing
		 *
		 * @param int $post_id Trashed post id.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function trash_suborders( $post_id ) {
			$order = wc_get_order( $post_id );

			if ( ! $order ) {
				return;
			}

			$suborders = $this->get_suborder( $post_id );

			if ( ! $suborders ) {
				return;
			}

			foreach ( $suborders as $suborder ) {
				( method_exists( $suborder, 'delete' ) ) ? $suborder->delete() : wp_trash_post( $suborder );
			}
		}

		/**
		 * Restore suborders on parent order restoring
		 *
		 * @param int $post_id Restore post id.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function untrash_suborders( $post_id ) {
			$order = wc_get_order( $post_id );

			if ( ! $order ) {
				return;
			}

			$suborders = $this->get_suborder( $post_id );

			if ( ! $suborders ) {
				return;
			}

			foreach ( $suborders as $suborder ) {
				wp_untrash_post( $suborder );
			}
		}

		/**
		 * Let WooCommerce skip stock decreasing for suborders
		 *
		 * @param bool      $can   Whether to perform or not stock decreasing.
		 * @param \WC_Order $order Current order.
		 *
		 * @return bool Filtered \$skip value
		 */
		public function skip_reduce_stock_on_suborders( $can, $order ) {
			if ( $this->is_suborder( $order->get_id() ) ) {
				return false;
			}

			return $can;
		}

		/**
		 * Let WooCommerce skip stock decreasing for suborders
		 *
		 * @param bool           $skip  Whether to perform or not stock decreasing.
		 * @param \WC_Order_Item $item Current order.
		 *
		 * @return bool Filtered \$skip value
		 */
		public function skip_reduce_stock_on_suborder_items( $skip, $item ) {
			if ( $this->is_suborder( $item->get_order_id() ) ) {
				return true;
			}

			return $skip;
		}

		/**
		 * Set products as in stock if they're retrieved for a balance payment
		 *
		 * @param \WC_Product            $product Currently retrieved product.
		 * @param \WC_Order_Item_Product $item    Current order item.
		 *
		 * @return \WC_Product filtered product
		 */
		public function set_suborder_items_as_in_stock( $product, $item ) {
			if ( isset( $product ) && $product instanceof WC_Product ) {
				$order_id = method_exists( $item, 'get_order_id' ) ? $item->get_order_id() : false;

				if ( ( $order_id && $this->is_suborder( $order_id ) ) || isset( $item['full_payment_id'] ) ) {
					$product->set_stock_status( 'instock' );
				}
			}

			return $product;
		}

		/**
		 * Set suborders status according to parent status when there is a failure and they're not completed yet
		 *
		 * @param int       $order_id Parent order id.
		 * @param \WC_Order $order    Parent order.
		 *
		 * @return void
		 * @since 1.2.4
		 */
		public function synch_suborders_with_parent_status( $order_id, $order ) {
			$order_status = $order->get_status();
			$suborders    = $this->get_suborder( $order_id );

			if ( $suborders ) {
				foreach ( $suborders as $suborder_id ) {
					$suborder = wc_get_order( $suborder_id );

					if ( ! $suborder->has_status( array( 'pending', 'on-hold' ) ) ) {
						continue;
					}

					$suborder->set_status( $order_status, __( 'Suborder status changed to reflect parent order status change', 'yith-woocommerce-deposits-and-down-payments' ) );
					$suborder->save();
				}
			}
		}

		/**
		 * Set suborders status to pending payment when parent order status changed and old status was failed.
		 *
		 * @param int    $order_id   Parent order id.
		 * @param string $old_status Previous order status.
		 * @param string $new_status Current order status.
		 *
		 * @return void
		 * @since 1.2.4
		 */
		public function synch_suborders_with_parent_status_failed( $order_id, $old_status, $new_status ) {

			if ( 'failed' === $old_status ) {
				$suborders = $this->get_suborder( $order_id );

				if ( $suborders ) {
					$order_status = 'wc-pending';

					foreach ( $suborders as $suborder_id ) {
						$suborder = wc_get_order( $suborder_id );

						if ( $suborder->has_status( 'failed' ) ) {
							continue;
						}

						$suborder->set_status( $order_status, __( 'Suborder status changed to reflect Pending payment status because parent\'s order status changed.', 'yith-woocommerce-deposits-and-down-payments' ) );
						$suborder->save();
					}
				}
			}
		}

		/**
		 * Create a single suborder with all the items included within second parameter
		 *
		 * @param int                      $order_id    Parent order id.
		 * @param \WC_Order_Item_Product[] $items       Array of order items to be processed for the suborder.
		 * @param mixed                    $posted_data Array of data submitted by the user.
		 *
		 * @return int|bool Suborder id; false on failure
		 * @since 1.2.1
		 */
		protected function build_suborder( $order_id, $items, $posted_data ) {
			// retrieve order.
			$parent_order = wc_get_order( $order_id );

			// create support cart
			// we use an default WC_cart instead of YITH_WCDP_Support_Cart because WC()->checkout will create orders only
			// from default session cart.
			$this->create_support_cart();

			// retrieve deposit payment balance.
			$deposit_shipping_preference       = get_option( 'yith_wcdp_general_deposit_shipping', 'let_user_choose' );
			$deposit_admin_shipping_preference = get_option( 'yith_wcdp_general_deposit_shipping_admin_selection' );

			// cycle over order items.
			foreach ( $items as $item_id => $item ) {

				try {
					$deposit                 = wc_get_order_item_meta( $item_id, '_deposit' );
					$deposit_balance         = wc_get_order_item_meta( $item_id, '_deposit_balance' );
					$deposit_shipping_method = wc_get_order_item_meta( $item_id, '_deposit_shipping_method' );
					$product                 = is_object( $item ) ? $item->get_product() : $parent_order->get_product_from_item( $item );
				} catch ( Exception $e ) {
					$deposit = false;
				}

				// if not a deposit, continue.
				if ( ! $deposit ) {
					continue;
				}

				// set order item meta with deposit-related full payment order.
				try {
					wc_add_order_item_meta( $item_id, '_full_payment_id', false );
				} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// do nothing.
				}

				// skip processing for other reason.
				if ( apply_filters( 'yith_wcdp_skip_suborder_creation', false, $item_id, $item, $order_id, $parent_order, $product ) ) {
					continue;
				}

				// make sure we have no problem with stock handling.
				remove_action( 'woocommerce_checkout_order_created', 'wc_reserve_stock_for_order' );

				try {
					// if deposit, add elem to support cart (filters change price of the product to be added to the cart).
					add_filter( 'woocommerce_add_cart_item', array( $this, 'set_item_full_amount_price' ) );

					$product_id           = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
					$variation_id         = $product->is_type( 'variation' ) ? $product->get_id() : '';
					$variation_attributes = $product->is_type( 'variation' ) ? $product->get_variation_attributes() : array();

					remove_all_actions('woocommerce_before_calculate_totals');
					WC()->cart->add_to_cart(
						$product_id,
						$item['qty'],
						$variation_id,
						$variation_attributes,
						apply_filters(
							'yith_wcdp_suborder_add_cart_item_data',
							array(
								'_deposit_balance' => $deposit_balance,
							),
							$item,
							$product
						)
					);
					remove_filter( 'woocommerce_add_cart_item', array( $this, 'set_item_full_amount_price' ) );
				} catch ( Exception $e ) {
					// translators: 1. Item id 2. Product title.
					$parent_order->add_order_note( sprintf( __( 'There was an error while processing suborder for item #%$1d (%$2s)', 'yith-woocommerce-deposits-and-down-payments' ), $item_id, $product->get_title() ) );
					continue;
				}
			}

			// if no item was added to cart, proceed no further.
			if ( WC()->cart->is_empty() ) {
				$this->restore_original_cart();

				return false;
			}

			// apply coupons (when required and possible) to suborder.
			if ( apply_filters( 'yith_wcdp_propagate_coupons', false ) && ! empty( $this->coupons ) ) {
				foreach ( $this->coupons as $coupon ) {
					if ( apply_filters( 'yith_wcdp_propagate_coupon', true, $coupon ) ) {
						WC()->cart->add_discount( $coupon );
					}
				}
				wc_clear_notices();
			}

			// set shipping method for suborder.
			if ( 'let_user_choose' === $deposit_shipping_preference && $deposit_shipping_method && apply_filters( 'yith_wcdp_virtual_on_deposit', true, $parent_order ) ) {
				WC()->checkout()->shipping_methods = $deposit_shipping_method;
			} elseif ( 'admin_choose' === $deposit_shipping_preference && $deposit_admin_shipping_preference && apply_filters( 'yith_wcdp_virtual_on_deposit', true, $parent_order ) ) {
				WC()->checkout()->shipping_methods = (array) $deposit_admin_shipping_preference;
			} else {
				WC()->checkout()->shipping_methods = array();
			}

			try {
				// create suborder.
				$new_suborder_id = WC()->checkout()->create_order( $posted_data );

				if ( ! $new_suborder_id || is_wp_error( $new_suborder_id ) ) {
					return false;
				}
			} catch ( Exception $e ) {
				$parent_order->add_order_note( __( 'There was an error while processing suborder', 'yith-woocommerce-deposits-and-down-payments' ) );

				return false;
			}

			add_action( 'woocommerce_checkout_order_created', 'wc_reserve_stock_for_order' );

			// set new suborder post parent.
			$new_suborder = wc_get_order( $new_suborder_id );

			try {
				$new_suborder->set_parent_id( $order_id );
				$new_suborder->set_status( apply_filters( 'yith_wcdp_suborder_status', 'pending', $new_suborder_id, $order_id ) );
				$new_suborder->set_created_via( 'yith_wcdp_balance_order' );

				// avoid counting sale twice.
				$new_suborder->update_meta_data( '_recorded_sales', 'yes' );
				// mark order as Full payment.
				$new_suborder->update_meta_data( '_has_full_payment', true );
				// disable stock management for brand new order.
				$new_suborder->update_meta_data( '_order_stock_reduced', true );
				// add plugin version.
				$new_suborder->update_meta_data( '_yith_wcdp_version', YITH_WCDP::YITH_WCDP_VERSION );
			} catch ( Exception $e ) {
				$new_suborder->add_order_note( __( 'Failed to update balance order meta', 'yith-woocommerce-deposits' ) );
			}

			// update new suborder totals.
			$new_suborder->calculate_totals();

			// set suborder customer note (remove email notification for this action only during this call).
			add_filter( 'woocommerce_email_enabled_customer_note', '__return_false' );
			$new_suborder->add_order_note( sprintf( '%s <a href="%s">#%d</a>', __( 'This order has been created to allow payment of the balance', 'yith-woocommerce-deposits-and-down-payments' ), $parent_order->get_view_order_url(), $order_id ), apply_filters( 'yith_wcdp_suborder_note_is_customer_note', true ) );
			remove_filter( 'woocommerce_email_enabled_customer_note', '__return_false' );

			// update new suborder items.
			try {
				$new_suborder_items = $new_suborder->get_items( 'line_item' );
				if ( ! empty( $new_suborder_items ) ) {
					foreach ( $new_suborder_items as $suborder_item_id => $suborder_item ) {
						wc_add_order_item_meta( $suborder_item_id, '_deposit_id', $order_id );
						wc_add_order_item_meta( $suborder_item_id, '_full_payment', true );

						// retrieve balance_type.
						$balance_type = get_option( 'yith_wcdp_balance_type', 'multiple' );

						// create a balance for each item purchased as deposit.
						if ( 'multiple' === $balance_type ) {
							foreach ( $items as $item_id => $item ) {
								// set order item meta with deposit-related order item id.
								wc_add_order_item_meta( $suborder_item_id, '_deposit_item_id', $item_id );
							}
						}
					}
				}

				foreach ( $items as $item_id => $item ) {
					// set order item meta with deposit-related full payment order.
					wc_update_order_item_meta( $item_id, '_full_payment_id', $new_suborder_id );
				}
			} catch ( Exception $e ) {
				$new_suborder->add_order_note( __( 'There was an error while updating item meta', 'yith-woocommerce-deposits-and-down-payments' ) );

				return false;
			}

			// save the new order.
			$new_suborder->save();

			// Let plugins add meta.
			do_action( 'yith_wcdp_update_suborder_meta', $new_suborder_id );

			// empty support cart, for next suborder.
			WC()->cart->empty_cart();

			// restore original cart.
			$this->restore_original_cart();

			return $new_suborder_id;
		}

		/* === SUPPORT CART METHODS === */

		/**
		 * Create a support cart, used to temporarily replace actual cart and make shipping/tax calculation, suborders checkout
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function create_support_cart() {
			// save current cart.
			$this->cart    = WC()->session->get( 'cart' );
			$this->coupons = WC()->session->get( 'applied_coupons' );

			WC()->cart->empty_cart( true );
			WC()->cart->remove_coupons();
		}

		/**
		 * Restore original cart, saved in \YITH_WCDP_Suborders::_cart property
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function restore_original_cart() {
			// delete current cart.
			WC()->cart->empty_cart( true );
			WC()->cart->remove_coupons();

			// reload cart.
			if ( apply_filters( 'yith_wcdp_reset_cart_after_suborder_processing', true ) ) {
				/**
				 * Depending on where \YITH_WCDP_Suborders::create_support_cart() was called, \YITH_WCDP_Suborders::_cart property may be
				 * an instance of WC_Cart class, or an array of cart contents (results of a previous WC_Cart::get_cart_for_session() )
				 *
				 * Instanceof prevents Fatal Error: method called on a non-object on single product pages, while
				 * WC_Cart::get_cart_for_session() avoid cart remaining empty after restore on process checkout
				 *
				 * @since 1.0.5
				 */
				WC()->session->set( 'cart', $this->cart instanceof WC_Cart ? $this->cart->get_cart_for_session() : $this->cart );
				WC()->session->set( 'applied_coupons', $this->coupons );

				WC()->cart->get_cart_from_session();

				/**
				 * Since we're sure cart has changed, let's force calculate_totals()
				 * Under some circumstances, not calculating totals at this point could effect WC()->cart->needs_payment() later,
				 * causing checkout process to redirect directly to Thank You page, instead of processing payment
				 *
				 * This was possibly caused by change in check performed at the end of get_cart_from_session() with WC 3.2
				 * Now conditions to recalculate totals after getting it from session are different then before
				 *
				 * @since 1.1.1
				 */
				WC()->cart->calculate_totals();
			}
		}

		/* === HELPER METHODS === */

		/**
		 * Check if order identified by $order_id has suborders, and eventually returns them
		 *
		 * @param int $order_id Id of the order to check.
		 *
		 * @return mixed Array of suborders, if any
		 * @since 1.0.0
		 */
		public function get_suborder( $order_id ) {
			global $wpdb;

			$suborder_ids = array();
			$parent_ids   = (array) absint( $order_id );

			while ( ! empty( $parent_ids ) ) {

				// todo: review code once WC switches to custom tables.
				$parents_list = implode( ', ', $parent_ids );
				$parent_ids   = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} AS p 
						 LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id 
						 WHERE post_parent IN ({$parents_list}) " . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						'AND post_type=%s 
						 AND meta_key=%s 
						 AND meta_value=%s ',
						'shop_order',
						'_created_via',
						'yith_wcdp_balance_order'
					)
				);

				$suborder_ids = array_merge( $suborder_ids, $parent_ids );
			}

			return apply_filters( 'yith_wcdp_suboder', $suborder_ids, $order_id );
		}

		/**
		 * Check if order identified by $order_id has uncompleted suborders, and eventually returns them
		 *
		 * @param int $order_id Id of the order to check.
		 *
		 * @return mixed Array of uncompleted suborders, if any
		 * @since 1.4.6
		 */
		public function get_uncompleted_suborder( $order_id ) {
			$suborder_ids             = $this->get_suborder( $order_id );
			$uncompleted_suborder_ids = array();

			foreach ( $suborder_ids as $suborder_id ) {
				$order = wc_get_order( $suborder_id );

				if ( 'pending' === $order->get_status() ) {
					array_push( $uncompleted_suborder_ids, $suborder_id );
				}
			}

			return apply_filters( 'yith_wcdp_uncompleted_suborder', $uncompleted_suborder_ids, $order_id );
		}

		/**
		 * Returns post parent of a Full payment order
		 * If order is not a full payment order, it will return false
		 *
		 * @param int $order_id Order id.
		 *
		 * @return int|bool If order is full payment, and has post parent, returns parent ID; false otherwise
		 */
		public function get_parent_order( $order_id ) {
			$order            = wc_get_order( $order_id );
			$has_full_payment = $order->get_meta( '_has_full_payment' );

			if ( ! $has_full_payment ) {
				return false;
			}

			return $order->get_parent_id();

		}

		/**
		 * Check if order identified by $order is a suborder (has post_parent)
		 *
		 * @param int|WC_Order $order Id of the order to check, or order object.
		 *
		 * @return bool Whether order is a suborder or no
		 * @since 1.0.0
		 */
		public function is_suborder( $order ) {
			if ( ! $order instanceof WC_Order ) {
				$order_id = $order;
				$order    = wc_get_order( $order_id );
			}

			if ( ! $order || ! $order instanceof WC_Order ) {
				return false;
			}

			$post_parent = $order->get_parent_id();
			$created_via = $order->get_created_via();

			return apply_filters( 'yith_wcdp_is_suborder', $post_parent && 'yith_wcdp_balance_order' === $created_via, $order );
		}

		/**
		 * Check if order identified by $order contains deposits
		 *
		 * @param int|WC_Order $order Id of the order to check, or order object.
		 *
		 * @return bool Whether order contains deposit or not.
		 * @since 1.3.9
		 */
		public function has_deposits( $order ) {
			if ( ! $order instanceof WC_Order ) {
				$order_id = $order;
				$order    = wc_get_order( $order_id );
			}

			if ( ! $order ) {
				return false;
			}

			$items = $order->get_items( 'line_item' );

			if ( empty( $items ) ) {
				return false;
			}

			$has_deposits = false;

			foreach ( $items as $item ) {
				if ( $item->get_meta( '_deposit', true ) ) {
					$has_deposits = true;
					break;
				}
			}

			return apply_filters( 'yith_wcdp_order_has_deposits', $has_deposits, $order );
		}

		/**
		 * Get parent orders for current user
		 *
		 * @return WC_Order[] Array of found orders
		 * @since 1.0.0
		 */
		public function get_parent_orders() {
			$customer_orders = wc_get_orders(
				apply_filters(
					'yith_wcdp_add_parent_orders',
					array(
						'posts_per_page' => - 1,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'   => '_customer_user',
								'value' => get_current_user_id(),
							),
							array(
								'key' => '_has_deposit',
							),
						),
						'post_type'      => wc_get_order_types( 'view-orders' ),
						'post_status'    => array_keys( wc_get_order_statuses() ),
						'post_parent'    => 0,
					)
				)
			);

			return $customer_orders;
		}

		/**
		 * Get child orders for current user
		 *
		 * @return \WP_Post[] Array of found orders
		 * @since 1.0.0
		 */
		public function get_child_orders() {
			$customer_orders = get_posts(
				apply_filters(
					'yith_wcdp_add_child_orders',
					array(
						'posts_per_page' => - 1,
						'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							array(
								'key'   => '_customer_user',
								'value' => get_current_user_id(),
							),
							array(
								'key' => '_has_full_payment',
							),
						),
						'post_type'      => wc_get_order_types( 'view-orders' ),
						'post_status'    => array_keys( wc_get_order_statuses() ),
					)
				)
			);

			return $customer_orders;
		}

		/**
		 * Return an array of ids of orders that contain deposit
		 *
		 * @return array Array of order ids
		 */
		public function get_all_deposits_ids() {
			global $wpdb;

			return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_has_deposit', '1' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		/**
		 * Return an array of ids of orders that where created as balance orders
		 *
		 * @return array Array of order ids
		 */
		public function get_all_balances_ids() {
			global $wpdb;

			return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_created_via', 'yith_wcdp_balance_order' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		/* === MULTI VENDOR COMPATIBILITY === */

		/**
		 * Remove deposit ssuborders from Multi Vendor suborders list
		 *
		 * @param mixed $suborder_ids    Multi Vendor suborders.
		 * @param int   $parent_order_id Parent order id.
		 *
		 * @return mixed Array diff between Multi Vendor suborders and deposit suborders
		 * @since 1.0.4
		 */
		public function remove_deposit_suborder_from_multi_vendor( $suborder_ids, $parent_order_id ) {
			if ( $parent_order_id && $suborder_ids ) {
				$deposit_suborder_ids = $this->get_suborder( $parent_order_id );
				if ( $deposit_suborder_ids ) {
					$suborder_ids = array_diff( $suborder_ids, $deposit_suborder_ids );
				}
			}

			return $suborder_ids;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Suborders
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
 * Unique access to instance of YITH_WCDP_suborders class
 *
 * @return \YITH_WCDP_Suborders
 * @since 1.0.0
 */
function YITH_WCDP_Suborders() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Suborders::get_instance();
}
