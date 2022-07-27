<?php
/**
 * Compatibility class with Dynamic Pricing and Discounts
 *
 * @author  YITH
 * @package YITH\Deposits\Classes\Compatibilities
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_YITH_Dynamic_Pricing_And_Discounts_Compatibility' ) ) {
	/**
	 * Deposit - Dynamic Pricing compatibility
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_YITH_Dynamic_Pricing_And_Discounts_Compatibility {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_YITH_Dynamic_Pricing_And_Discounts_Compatibility
		 * @since 1.0.5
		 */
		protected static $instance;

		/**
		 * Constructor.
		 *
		 * @since 1.0.5
		 */
		public function __construct() {
			add_filter( 'yith_wcdp_process_cart_item_product_change', '__return_false', 10 );
			if ( version_compare( YITH_YWDPD_VERSION, '2.4.0', '>' ) ) {
				add_action( 'init', array( $this, 'init_integration' ), 99 );
			} else {
				add_filter( 'yith_wcdp_product_price_for_deposit_operation', array( $this, 'change_base_product_price' ), 20, 2 );
			}
			add_filter( 'woocommerce_cart_item_price', array( $this, 'replace_cart_item_price' ), 300, 3 );
			add_action( 'woocommerce_add_to_cart', array( $this, 'process_cart_item_product_change' ), 300 );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'process_cart_item_product_change' ), 300 );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'process_cart_item_product_change' ), 300 );
		}

		/**
		 * Init the integration with Dyanmic 3.0
		 *
		 * @author YITH
		 */
		public function init_integration() {
			add_filter( 'ywdpd_skip_cart_check', array( $this, 'skip_cart_check' ), 20, 2 );
			add_action( 'yith_wcdp_after_add_to_support_cart', array( $this, 'fix_product_price' ), 20, 6 );
			add_filter( 'yith_wcdp_deposist_value', array( $this, 'change_deposit_value' ), 10, 2 );
		}

		public function change_deposit_value( $deposit_amount, $product ){

			$price = $product->get_price();
			$dynamic_price = ywdpd_dynamic_pricing_discounts()->get_frontend_manager()->get_dynamic_price( $price, $product );

			return min( YITH_WCDP_Premium()->get_deposit( $product->get_id(), $dynamic_price, 'view' ), $price );
		}
		public function fix_product_price( $product_id, $quantity, $variation_id, $variation, $old_cart_item_data, $new_cart_item ) {

			if ( isset( $old_cart_item_data['ywdpd_discounts']['price_adjusted'] ) ) {

				$new_cart_item['data']->set_price( $old_cart_item_data['ywdpd_discounts']['price_adjusted'] );
			}
		}

		/**
		 * Skip the check if is a Support cart
		 *
		 * @param bool $skip Skip the check.
		 * @param WC_Cart $cart The cart object.
		 *
		 * @author YITH
		 */
		public function skip_cart_check( $skip, $cart ) {
			$skip = $cart instanceof YITH_WCDP_Support_Cart;

			return $skip;
		}


		/**
		 * Execute deposit calculations after Dynamic discounts
		 *
		 * @return void
		 * @since 1.0.5
		 */
		public function process_cart_item_product_change() {

			$cart = WC()->cart;

			if ( $cart instanceof YITH_WCDP_Support_Cart ) {
				return;
			}

			if ( ! $cart->is_empty() ) {

				foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {

					if ( ! isset( $cart_item['deposit'] ) || ! $cart_item['deposit'] ) {
						continue;
					}
					/**
					 * Product object for current cart item
					 *
					 * @var $product \WC_Product
					 */
					$product = $cart_item['data'];
					$price   = wc_get_product( $product->get_id() )->get_price();


					if ( isset( $cart_item['ywdpd_discounts'] ) ) {
						if ( version_compare( YITH_YWDPD_VERSION, '2.4.0', '<=' ) ) {
							foreach ( $cart_item['ywdpd_discounts'] as $discount ) {
								if ( isset( $discount['status'] ) && 'applied' === $discount['status'] ) {

									$price = $discount['current_price'];
								}

								if ( 'bulk' === $discount['discount_mode'] ) {
									break;
								}
							}
						} else {
							$price = $cart_item['ywdpd_discounts']['price_adjusted'];
						}

					}
					$product_id = ! $product->is_type( 'variation' ) ? $product->get_id() : $product->get_parent_id();

					$variation_id    = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : false;
					$deposit_value   = apply_filters( 'yith_wcdp_deposit_value', YITH_WCDP_Premium()->get_deposit( $product_id, $price, 'edit', false, $variation_id ), $product_id, $variation_id, $cart_item );
					$deposit_balance = apply_filters( 'yith_wcdp_deposit_balance', max( $price - $deposit_value, 0 ), $product_id, $variation_id, $cart_item );
					$product->set_price( $deposit_value );
					$product->update_meta_data( 'yith_wcdp_deposit', true );

					if ( apply_filters( 'yith_wcdp_virtual_on_deposit', true, null ) ) {
						$product->set_virtual( true );
					}

					$cart_item['deposit_value']   = $deposit_value;
					$cart_item['deposit_balance'] = $deposit_balance;

				}
			}
		}

		/**
		 * Updates cart item price, according to both Deposit and Dynamic rules
		 *
		 * @param string $price_html Html for the cart item price.
		 * @param array $cart_item Cart item.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return string Filtered html for the price.
		 */
		public function replace_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
			$cart = WC()->cart;

			if ( $cart instanceof YITH_WCDP_Support_Cart ) {
				return $price_html;
			}

			if ( isset( $cart_item['deposit_value'] ) && isset( $cart_item['ywdpd_discounts']) ) {
				$product = $cart_item['data'];
				$price   = $product->get_price();

				if ( version_compare( YITH_YWDPD_VERSION, '2.4.0', '<=' ) ) {
					if ( YITH_WCDP_Premium()->is_deposit_enabled_on_product( $product ) ) {
						$price = YITH_WCDP_Premium()->get_deposit( $product->get_id(), $price );
						WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $price );
					}

					$old_price = $price;

					if ( isset( $cart_item['ywdpd_discounts'] ) ) {

						foreach ( $cart_item['ywdpd_discounts'] as $discount ) {
							if ( isset( $discount['status'] ) && 'applied' === $discount['status'] ) {

								$old_price = $discount['current_price'];
							}

							if ( 'bulk' === $discount['discount_mode'] ) {
								break;
							}
						}
					}
				} else {
					if ( isset( $cart_item['ywdpd_discounts'] ) ) {

						$old_price = $cart_item['ywdpd_discounts']['price_adjusted'];
						if ( YITH_WCDP_Premium()->is_deposit_enabled_on_product( $product ) ) {
							$price = YITH_WCDP_Premium()->get_deposit( $product->get_id(), $old_price );
							WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $price );
						}

					}
				}

				if ( $old_price !== $price ) {
					$price_html = '<del>' . wc_price( $old_price ) . '</del> ' . wc_price( $price );
				}
			}

			return $price_html;
		}

		/**
		 * Filters price used as a base for deposit calculation
		 *
		 * @param float $price Price used for calculation.
		 * @param WC_Product $product Product object.
		 *
		 * @return float Filtered price.
		 */
		public function change_base_product_price( $price, $product ) {

			if ( function_exists( 'YITH_WC_Dynamic_Pricing' ) && is_product() ) {

				$price = YITH_WC_Dynamic_Pricing()->get_discount_price( $price, $product );

			}

			return $price;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_YITH_Dynamic_Pricing_And_Discounts_Compatibility
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

