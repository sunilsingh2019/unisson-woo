<?php
/**
 * Frontend class premium
 *
 * @author  YITH
 * @package YITH\Deposits\Classes
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Frontend_Premium' ) ) {
	/**
	 * WooCommerce Deposits and Down Payments Frontend Premium
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Frontend_Premium extends YITH_WCDP_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Frontend
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Template of single product page "Add deposit to cart" for each variation
		 *
		 * @var string
		 * @since 1.0.4
		 */
		protected $single_product_add_deposit_variations;

		/**
		 * Constructor.
		 *
		 * @return \YITH_WCDP_Frontend_Premium
		 * @since 1.0.0
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'yith_wcdp_before_my_deposits_table', array( $this, 'print_expired_suborders_notice' ), 10, 1 );
			add_action( 'woocommerce_available_variation', array( $this, 'add_deposit_variation_data' ), 10, 3 );

			// Labels filters.
			add_filter( 'yith_wcdp_deposit_label', array( $this, 'filter_deposit_label' ) );
			add_filter( 'yith_wcdp_pay_deposit_label', array( $this, 'filter_pay_deposit_label' ) );
			add_filter( 'yith_wcdp_pay_full_amount_label', array( $this, 'filter_pay_full_amount_label' ) );
			add_filter( 'yith_wcdp_partially_paid_status_label', array( $this, 'filter_partially_paid_status' ) );
			add_filter( 'yith_wcdp_full_price_filter', array( $this, 'filter_full_price_label' ) );
			add_filter( 'yith_wcdp_balance_filter', array( $this, 'filter_balance_label' ) );

			// expiration fallbacks for the product.
			add_filter( 'woocommerce_is_purchasable', array( $this, 'is_deposit_product_purchasable' ), 10, 2 );
			add_filter( 'woocommerce_product_is_visible', array( $this, 'is_deposit_product_visible' ), 10, 2 );

			// On location balance orders note.
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'print_on_location_notice' ), 10, 1 );

			// Additional product notes.
			add_action( 'init', array( $this, 'add_additional_product_note' ), 15 );
			add_action( 'yith_wcdp_before_add_deposit_to_cart', array( $this, 'print_additional_variation_note' ), 10, 2 );

			// Ajax handling.
			add_action( 'wp_ajax_get_deposit_template', array( $this, 'get_deposit_template_via_ajax' ) );
			add_action( 'wp_ajax_nopriv_get_deposit_template', array( $this, 'get_deposit_template_via_ajax' ) );
		}

		/* === GENERAL FRONTEND METHODS === */

		/**
		 * Add "Add deposit" option to single product page
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_single_add_deposit_button() {
			global $post;

			if ( ! is_product() && $post && apply_filters( 'yith_wcdp_deposit_post_content', false === strpos( $post->post_content, '[product_page' ), $post ) ) {
				return;
			}

			if ( ! $post ) {
				return;
			}

			if ( ! is_product() ) {
				$shortcode_matches = array();
				$post_content      = apply_filters( 'yith_wcdp_post_content', $post->post_content, $post );
				preg_match( '/.*\[product_page.*id="([0-9]*)".*\].*/', $post_content, $shortcode_matches );
				$product_id = isset( $shortcode_matches[1] ) ? $shortcode_matches[1] : false;
			} else {
				$product_id = $post->ID;
			}

			if ( ! $product_id ) {
				return;
			}

			// retrieve product and init add deposit template.
			$product = wc_get_product( $product_id );

			if ( ! apply_filters( 'yith_wcdp_add_single_deposit_button', true, $product ) ) {
				return;
			}

			// simple products.
			$this->single_product_add_deposit = $this->print_single_add_deposit_to_cart_field( false );

			// variable products.
			if ( $product->is_type( 'variable' ) ) {
				$this->single_product_add_deposit_variations = $this->print_single_add_deposit_to_cart_variations_field();
			}

			if ( $product->is_type( 'simple' ) ) {
				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'print_single_add_deposit_to_cart_template' ) );
			} else {
				do_action( "yith_wcdp_{$product->get_type()}_add_to_cart", $product );
			}
		}

		/**
		 * Print fields before single add to cart, to let user add to cart deposit
		 *
		 * @param bool $echo Whether to return template, or echo it.
		 *
		 * @return string Return template if param $echo is set to true
		 * @since 1.0.0
		 */
		public function print_single_add_deposit_to_cart_field( $echo = true ) {
			global $post;

			$template = '';

			if ( ! is_product() && $post && apply_filters( 'yith_wcdp_deposit_post_content', false === strpos( $post->post_content, '[product_page' ), $post ) ) {
				return $template;
			}

			if ( ! is_product() && $post ) {
				$shortcode_matches = array();
				$post_content      = apply_filters( 'yith_wcdp_post_content', $post->post_content, $post );
				preg_match( '/.*\[product_page.*id="([0-9]*)".*\].*/', $post_content, $shortcode_matches );
				$product_id = isset( $shortcode_matches[1] ) ? $shortcode_matches[1] : false;
			} else {
				$product_id = $post->ID;
			}

			if ( ! $product_id ) {
				return $template;
			}

			// retrieve product.
			$product = wc_get_product( $product_id );

			if ( $product instanceof WC_Product && ( ! $product->is_purchasable() || ! $product->is_in_stock() ) ) {
				return $template;
			}

			// product options.
			$deposit_enabled = YITH_WCDP_Premium()->is_deposit_enabled_on_product( $product_id );
			$deposit_forced  = YITH_WCDP_Premium()->is_deposit_mandatory( $product_id );
			$default_deposit = YITH_WCDP_Premium()->is_deposit_default( $product_id );
			$deposit_type    = YITH_WCDP_Premium()->get_deposit_type( $product_id );
			$deposit_amount  = YITH_WCDP_Premium()->get_deposit_amount( $product_id );
			$deposit_rate    = YITH_WCDP_Premium()->get_deposit_rate( $product_id );

			$deposit_value = apply_filters( 'yith_wcdp_deposist_value', min( YITH_WCDP_Premium()->get_deposit( $product->get_id(), false, 'view' ), $product->get_price() ), $product );

			if ( ! $deposit_enabled ) {
				return $template;
			}

			$skip_support_cart = apply_filters( 'yith_wcdp_skip_support_cart', false, $product );

			if ( ! $skip_support_cart ) {
				YITH_WCDP_Suborders()->create_support_cart();
				WC()->cart->add_to_cart( $product->get_id(), 1 );
				WC()->cart->calculate_shipping();
			}

			$deposit_shipping         = get_option( 'yith_wcdp_general_deposit_shipping', 'let_user_choose' );
			$let_user_choose_shipping = 'let_user_choose' === $deposit_shipping;

			$args = array(
				'product'            => $product,
				'deposit_enabled'    => $deposit_enabled,
				'default_deposit'    => $default_deposit,
				'deposit_forced'     => $deposit_forced,
				'deposit_type'       => $deposit_type,
				'deposit_amount'     => $deposit_amount,
				'deposit_rate'       => $deposit_rate,
				'deposit_value'      => $deposit_value,
				'needs_shipping'     => ! $skip_support_cart ? WC()->cart->needs_shipping() : false,
				'show_shipping_form' => ! $skip_support_cart ? $let_user_choose_shipping : false,
			);

			ob_start();

			yith_wcdp_get_template( 'single-add-deposit-to-cart.php', $args );

			$template = ob_get_clean();

			if ( ! $skip_support_cart ) {
				YITH_WCDP_Suborders()->restore_original_cart();
			}

			if ( $echo ) {
				// template is already escaped.
				echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			return $template;
		}

		/**
		 * Print fields before single add to cart, to let user add to cart deposit
		 *
		 * @param int $product_id Product id.
		 *
		 * @return array Return template if param $echo is set to true
		 * @since 1.0.0
		 */
		public function print_single_add_deposit_to_cart_variations_field( $product_id = false ) {
			global $post;
			$templates = array();

			// retrieve product.

			if ( empty( $product_id ) ) {
				if ( ! is_product() && $post ) {
					$shortcode_matches = array();
					preg_match( '/.*\[product_page.*id="([0-9]*)".*\].*/', $post->post_content, $shortcode_matches );
					$product_id = isset( $shortcode_matches[1] ) ? $shortcode_matches[1] : false;
				} else {
					$product_id = $post->ID;
				}
			}

			if ( ! $product_id ) {
				return $templates;
			}

			/**
			 * Variable product.
			 *
			 * @var $product \WC_Product_Variable
			 */
			$product    = wc_get_product( $product_id );
			$variations = $product->get_available_variations();

			if ( ! apply_filters( 'yith_wcdp_generate_add_deposit_to_cart_variations_field', true, $product_id ) ) {
				return array();
			}

			if ( ! empty( $variations ) ) {
				if ( ! apply_filters( 'yith_wcdp_disable_deposit_variation_option', false, $product_id ) ) {
					foreach ( $variations as $variation ) {
						$variation_id               = $variation['variation_id'];
						$templates[ $variation_id ] = $this->generate_variations_field( $product_id, $variation_id );
					}
				} else {
					$template = $this->print_single_add_deposit_to_cart_field( false );
					foreach ( $variations as $variation ) {
						$variation_id               = $variation['variation_id'];
						$templates[ $variation_id ] = $template;
					}
				}
			}

			return $templates;
		}

		/**
		 * Generate Add deposit to Cart template for a single variation
		 *
		 * @param int   $product_id Product id.
		 * @param int   $variation_id Variation id.
		 * @param array $attr Optional array of attributes for the add to cart.
		 *
		 * @return string Template
		 * @since 1.0.0
		 */
		public function generate_variations_field( $product_id, $variation_id, $attr = array() ) {
			$variation = wc_get_product( $variation_id );
			$product   = wc_get_product( $product_id );

			// product options.
			$deposit_enabled = YITH_WCDP_Premium()->is_deposit_enabled_on_product( $product_id, $variation_id );
			$deposit_forced  = YITH_WCDP_Premium()->is_deposit_mandatory( $product_id, $variation_id );
			$default_deposit = YITH_WCDP_Premium()->is_deposit_default( $product_id, $variation_id );
			$deposit_type    = YITH_WCDP_Premium()->get_deposit_type( $product_id, false, $variation_id );
			$deposit_amount  = YITH_WCDP_Premium()->get_deposit_amount( $product_id, false, $variation_id );
			$deposit_rate    = YITH_WCDP_Premium()->get_deposit_rate( $product_id, false, $variation_id );
			$deposit_value   = YITH_WCDP_Premium()->get_deposit( $product_id, false, 'view', false, $variation_id );

			if ( ! $deposit_enabled ) {
				return false;
			}

			if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
				return false;
			}

			try {
				$support_cart = YITH_WCDP_Premium()->get_support_cart();
				$support_cart->add_to_cart( $product_id, 1, $variation_id, $attr );
				$support_cart->calculate_shipping();
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// do nothing (shipping costs/options may be affected).
			}

			$deposit_shipping         = get_option( 'yith_wcdp_general_deposit_shipping', 'let_user_choose' );
			$let_user_choose_shipping = 'let_user_choose' === $deposit_shipping;

			$args = array(
				'product'            => $product,
				'variation'          => $variation,
				'deposit_enabled'    => $deposit_enabled,
				'default_deposit'    => $default_deposit,
				'deposit_forced'     => $deposit_forced,
				'deposit_type'       => $deposit_type,
				'deposit_amount'     => $deposit_amount,
				'deposit_rate'       => $deposit_rate,
				'deposit_value'      => $deposit_value,
				'needs_shipping'     => $support_cart->needs_shipping(),
				'show_shipping_form' => $let_user_choose_shipping,
			);

			ob_start();

			yith_wcdp_get_template( 'single-add-deposit-to-cart.php', $args );

			$return = ob_get_clean();

			$support_cart->empty_cart();

			return $return;
		}

		/**
		 * Print notice to warning user some deposits have expired
		 *
		 * @param int $order_id Current order id.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_expired_suborders_notice( $order_id ) {
			$suborders         = YITH_WCDP_Suborders()->get_suborder( $order_id );
			$expired_suborders = array();

			if ( ! empty( $suborders ) ) {
				foreach ( $suborders as $suborder_id ) {
					$suborder    = wc_get_order( $suborder_id );
					$has_expired = $suborder->get_meta( '_has_deposit_expired' );

					if ( $has_expired ) {
						$expired_suborders[] = $suborder_id;
					}
				}
			}

			if ( ! empty( $expired_suborders ) ) {
				$orders_link = '';
				$first       = true;

				foreach ( $expired_suborders as $suborder_id ) {
					if ( ! $first ) {
						$orders_link .= ', ';
					}

					// retrieve order url and append to orders_link string.
					$view_order_url = apply_filters( 'woocommerce_get_view_order_url', wc_get_endpoint_url( 'view-order', $suborder_id, wc_get_page_permalink( 'myaccount' ) ) );
					$orders_link   .= sprintf( '<a href="%s">#%d</a>', esc_url( $view_order_url ), esc_html( $suborder_id ) );

					$first = false;
				}

				// translators: 1. Link to balance order(s).
				$message = sprintf( _n( 'This order contains expired deposit; full amount order %s was consequently switched to cancelled and it cannot be completed anymore', 'This order contains expired deposit; full amount orders %s were consequently switched to cancelled and they cannot be completed anymore', count( $expired_suborders ), 'yith-woocommerce-deposits-and-down-payments' ), $orders_link );
				$message = sprintf( '<div class="woocommerce-error">%s</div>', $message );

				// output alread escaped.
				echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Print notice to let use know this full payment order should be paid on location
		 *
		 * @param WC_Order $order Current order.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_on_location_notice( $order ) {
			if ( $order->get_meta( '_has_full_payment' ) && $order->get_meta( '_full_payment_needs_manual_payment' ) && $order->has_status( 'on-hold' ) ) {
				$notice   = get_option( 'yith_wcdp_deposit_labels_pay_in_loco', '' );
				$template = '';

				if ( ! empty( $notice ) ) {
					$template .= '<div id="yith_wcdp_on_location_notice" class="yith-wcdp-on-location-notice">';
					$template .= '<h2>' . esc_html__( 'Payment options', 'yith-woocommerce-deposits-and-down-payments' ) . '</h2>';
					$template .= '<p>' . wp_kses_post( $notice ) . '</p>';
					$template .= '</div>';

					// template is already escaped.
					echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		/**
		 * Adds additional product deposit note to correct action
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_additional_product_note() {
			$position = get_option( 'yith_wcdp_deposit_labels_product_note_position', 'woocommerce_template_single_meta' );
			$action   = 'woocommerce_product_meta_end';
			$priority = 10;

			switch ( $position ) {
				case 'none':
					return;
				case 'woocommerce_template_single_title':
					$action   = 'woocommerce_single_product_summary';
					$priority = 7;
					break;
				case 'woocommerce_template_single_price':
					$action   = 'woocommerce_single_product_summary';
					$priority = 15;
					break;
				case 'woocommerce_template_single_excerpt':
					$action   = 'woocommerce_single_product_summary';
					$priority = 25;
					break;
				case 'woocommerce_template_single_add_to_cart':
					$action   = 'woocommerce_single_product_summary';
					$priority = 35;
					break;
				case 'woocommerce_template_single_sharing':
					$action   = 'woocommerce_single_product_summary';
					$priority = 55;
					break;
				case 'woocommerce_product_meta_end':
				default:
					break;
			}

			add_action( $action, array( $this, 'print_additional_product_note' ), $priority );
		}

		/**
		 * Print additional product deposit note
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_additional_product_note() {
			global $product;

			if ( ! is_product() || ! $product ) {
				return;
			}

			$enabled = YITH_WCDP()->is_deposit_enabled_on_product( $product->get_id() );

			$general_note          = get_option( 'yith_wcdp_deposit_labels_product_note', '' );
			$product_specific_note = $product->get_meta( '_product_note' );

			if ( ! $enabled || ( empty( $general_note ) && empty( $product_specific_note ) ) ) {
				return;
			}

			$note = $product_specific_note ? $product_specific_note : $general_note;

			echo wp_kses_post( apply_filters( 'yith_wcdp_deposit_print_product_note', "<div class='yith-wcdp-product-note'>{$note}</div>", $product, $general_note ) );
		}

		/**
		 * Add additional notes for variation
		 *
		 * @param WC_Product_variable  $product   Current product.
		 * @param WC_Product_Variation $variation Current variation.
		 */
		public function print_additional_variation_note( $product, $variation ) {
			if ( ! $variation ) {
				return;
			}

			$note = $variation->get_meta( '_product_note' );

			echo wp_kses_post( $note );
		}

		/**
		 * Adds "Deposit form" to each variation data
		 *
		 * @param array                $variation_data   Variation data to filter.
		 * @param WC_Product_Variable  $product_variable Variable product.
		 * @param WC_Product_Variation $variation        Current variation.
		 *
		 * @return mixed Filtered variation data
		 * @since 1.04
		 */
		public function add_deposit_variation_data( $variation_data, $product_variable, $variation ) {
			$enable_ajax_handling = get_option( 'yith_wcdp_general_enable_ajax_variation', 'no' );

			if ( 'yes' === $enable_ajax_handling || ! apply_filters( 'yith_wcdp_generate_add_deposit_to_cart_variations_field', true, $product_variable->get_id() ) ) {
				return $variation_data;
			}

			if ( isset( $this->single_product_add_deposit_variations[ $variation->get_id() ] ) ) {
				$deposit_template = $this->single_product_add_deposit_variations[ $variation->get_id() ];
			} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$deposit_template = $this->generate_variations_field( $product_variable->get_id(), $variation->get_id() );
			}

			$variation_data = array_merge(
				$variation_data,
				isset( $deposit_template ) ? array(
					'add_deposit_to_cart' => $deposit_template,
				) : array()
			);

			return $variation_data;
		}

		/* === LABELS METHODS === */

		/**
		 * Filter Deposit label
		 *
		 * @param string $label Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_deposit_label( $label ) {
			return get_option( 'yith_wcdp_deposit_labels_deposit', $label );
		}

		/**
		 * Filter Pay Deposit label
		 *
		 * @param string $label Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_pay_deposit_label( $label ) {
			return get_option( 'yith_wcdp_deposit_labels_pay_deposit', $label );
		}

		/**
		 * Filter Pay Full Amount label
		 *
		 * @param string $label Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_pay_full_amount_label( $label ) {
			return get_option( 'yith_wcdp_deposit_labels_pay_full_amount', $label );
		}

		/**
		 * Filter Partially Paid status
		 *
		 * @param string $status Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_partially_paid_status( $status ) {
			return get_option( 'yith_wcdp_deposit_labels_partially_paid_status', $status );
		}

		/**
		 * Filter Full Price label
		 *
		 * @param string $label Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_full_price_label( $label ) {
			return get_option( 'yith_wcdp_deposit_labels_full_price_label', $label );
		}

		/**
		 * Filter Balance label
		 *
		 * @param string $label Original label.
		 *
		 * @return string Filtered label
		 * @since 1.0.0
		 */
		public function filter_balance_label( $label ) {
			return get_option( 'yith_wcdp_deposit_labels_balance_label', $label );
		}

		/* === EXPIRATION FALLBACK METHODS === */

		/**
		 * Filters is purchasable for a specific product, to make it no longer available after expiration
		 *
		 * @param bool       $is_purchasable Whether current product is purchasable.
		 * @param WC_Product $product        Current product.
		 *
		 * @return bool Filtered value
		 */
		public function is_deposit_product_purchasable( $is_purchasable, $product ) {
			$product_id = $product->get_id();

			$deposit_expiration_product_fallback         = $product->get_meta( '_deposit_expiration_product_fallback', true );
			$deposit_expiration_product_fallback_general = get_option( 'yith_wcdp_deposits_expiration_product_fallback', 'disable_deposit' );

			if ( empty( $deposit_expiration_product_fallback ) || 'default' === $deposit_expiration_product_fallback ) {
				$deposit_expiration_product_fallback = $deposit_expiration_product_fallback_general;
			}

			if ( 'item_not_purchasable' === $deposit_expiration_product_fallback ) {
				$deposit_expired = YITH_WCDP_Premium()->is_deposit_expired_for_product( $product_id );

				return ! $deposit_expired ? $is_purchasable : false;
			}

			return $is_purchasable;
		}

		/**
		 * Filters is visible for a specific product, to make it no longer available after expiration
		 *
		 * @param bool $is_visible Whether current product is purchasable.
		 * @param int  $product_id Current product id.
		 *
		 * @return bool Filtered value
		 */
		public function is_deposit_product_visible( $is_visible, $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return $is_visible;
			}

			$deposit_expiration_product_fallback         = $product->get_meta( '_deposit_expiration_product_fallback', true );
			$deposit_expiration_product_fallback_general = get_option( 'yith_wcdp_deposits_expiration_product_fallback', 'disable_deposit' );

			if ( 'default' === $deposit_expiration_product_fallback ) {
				$deposit_expiration_product_fallback = $deposit_expiration_product_fallback_general;
			}

			if ( 'hide_item' === $deposit_expiration_product_fallback ) {
				$deposit_expired = YITH_WCDP_Premium()->is_deposit_expired_for_product( $product_id );

				return ! $deposit_expired ? $is_visible : 'hidden';
			}

			return $is_visible;
		}

		/* === AJAX METHODS === */

		/**
		 * Return Add deposit to Cart template via Ajax call
		 *
		 * @return void
		 */
		public function get_deposit_template_via_ajax() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$variation_id   = isset( $_GET['variation_id'] ) ? intval( $_GET['variation_id'] ) : false;
			$variation_attr = isset( $_GET['variation_attr'] ) ? array_map( 'wc_clean', $_GET['variation_attr'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			// phpcs:disable WordPress.Security.NonceVerification.Recommended

			if ( ! $variation_id ) {
				die;
			}

			/**
			 * Product variation
			 *
			 * @var $product \WC_Product_Variation
			 */
			$product    = wc_get_product( $variation_id );
			$product_id = $product->get_parent_id();

			if ( isset( $this->single_product_add_deposit_variations[ $variation_id ] ) ) {
				$template = $this->single_product_add_deposit_variations[ $variation_id ];
			} else {
				$template = $this->generate_variations_field( $product_id, $variation_id, $variation_attr );
			}

			// template is already escaped.
			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			die;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Frontend_Premium
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
 * Unique access to instance of YITH_WCDP_Frontend_Premium class
 *
 * @return \YITH_WCDP_Frontend_Premium
 * @since 1.0.0
 */
function YITH_WCDP_Frontend_Premium() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Frontend_Premium::get_instance();
}
