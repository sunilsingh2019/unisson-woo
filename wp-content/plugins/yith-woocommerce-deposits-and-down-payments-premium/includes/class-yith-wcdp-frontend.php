<?php
/**
 * Frontend class
 *
 * @author  YITH
 * @package YITH\Deposits\Classes
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCDP_Frontend' ) ) {
	/**
	 * WooCommerce Deposits and Down Payments Frontend
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Frontend {
		/**
		 * Single instance of the class
		 *
		 * @var \YITH_WCDP_Frontend
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Template of single product page "Add deposit to cart"
		 *
		 * @var string
		 * @since 1.0.0
		 */
		protected $single_product_add_deposit;

		/**
		 * Grand totals to show on cart and checkout for current cart/deposit configuration
		 *
		 * @var array
		 */
		protected $grand_totals;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// update add to cart for deposit.
			add_action( 'template_redirect', array( $this, 'add_single_add_deposit_button' ) );
			add_action( 'woocommerce_before_shop_loop_item', array( $this, 'add_loop_add_deposit_button' ) );
			add_action( 'woocommerce_after_shop_loop_item', array( $this, 'remove_loop_add_deposit_button' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// update frontend to show deposit/balance amount.
			add_filter( 'woocommerce_get_item_data', array( $this, 'add_deposit_item_data' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_name', array( $this, 'filter_cart_deposit_product_name' ), 10, 2 );
			add_filter( 'woocommerce_order_item_name', array( $this, 'filter_order_deposit_product_name' ), 10, 2 );
			add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'filter_cart_total' ), 10, 2 );
			add_action( 'woocommerce_review_order_before_order_total', array( $this, 'print_balance_totals' ) );

			// update my-account page.
			add_filter( 'woocommerce_my_account_my_orders_query', array( $this, 'filter_my_account_my_orders_query' ) );
			add_filter( 'woocommerce_get_formatted_order_total', array( $this, 'filter_my_account_my_orders_total' ), 10, 2 );
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'filter_my_account_my_orders_actions' ), 10, 2 );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'print_full_amount_payments_orders' ), 10, 1 );
			add_filter( 'woocommerce_order_get_status', array( $this, 'filter_order_status' ), 10, 2 );
			add_filter( 'wc_order_statuses', array( $this, 'filter_order_status_labels' ) );
		}

		/* === GENERAL FRONTEND METHODS === */

		/**
		 * Enqueue frontend assets
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			global $post;

			$path   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'unminified/' : '';
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			// include js required.
			$template_name = 'deposit-and-down-payments.js';
			$locations     = array(
				trailingslashit( WC()->template_path() ) . 'yith-wcdp/' . $template_name,
				trailingslashit( WC()->template_path() ) . $template_name,
				'yith-wcdp/' . $template_name,
				$template_name,
			);

			$template_js = locate_template( $locations );

			if ( ! $template_js ) {
				$template_js = YITH_WCDP_URL . 'assets/js/' . $path . 'yith-wcdp' . $suffix . '.js';
			} else {
				$search      = array( get_stylesheet_directory(), get_template_directory() );
				$replace     = array( get_stylesheet_directory_uri(), get_template_directory_uri() );
				$template_js = str_replace( $search, $replace, $template_js );
			}

			wp_register_script(
				'yith-wcdp',
				apply_filters( 'yith_wcdp_enqueue_frontend_script_template_js', $template_js ),
				array(
					'jquery',
					'wc-add-to-cart-variation',
					'jquery-blockui',
					'selectWoo',
					'wc-country-select',
					'wc-address-i18n',
					'accounting',
				),
				YITH_WCDP::YITH_WCDP_VERSION,
				true
			);

			$template_name = 'deposit-and-down-payments.css';
			$locations     = array(
				trailingslashit( WC()->template_path() ) . 'yith-wcdp/' . $template_name,
				trailingslashit( WC()->template_path() ) . $template_name,
				'yith-wcdp/' . $template_name,
				$template_name,
			);

			$template_css = locate_template( $locations );

			if ( ! $template_css ) {
				$template_css = YITH_WCDP_URL . 'assets/css/yith-wcdp.css';
			} else {
				$search       = array( get_stylesheet_directory(), get_template_directory() );
				$replace      = array( get_stylesheet_directory_uri(), get_template_directory_uri() );
				$template_css = str_replace( $search, $replace, $template_css );
			}

			wp_register_style( 'yith-wcdp', $template_css, array( 'select2' ), YITH_WCDP::YITH_WCDP_VERSION );

			do_action( 'yith_wcdp_enqueue_frontend_script' );

			if ( is_product() || ( is_object( $post ) && isset( $post->post_content ) && false !== strpos( $post->post_content, '[product_page' ) ) ) {
				wp_enqueue_script( 'yith-wcdp' );
				wp_localize_script(
					'yith-wcdp',
					'yith_wcdp',
					array(
						'ajax_url'            => admin_url( 'admin-ajax.php' ),
						'actions'             => array(
							'calculate_shipping' => array(
								'name'  => 'yith_wcdp_calculate_shipping',
								'nonce' => wp_create_nonce( 'calculate-shipping' ),
							),
							'change_location'    => array(
								'name'  => 'yith_wcdp_change_location',
								'nonce' => wp_create_nonce( 'change-location' ),
							),
						),
						'currency_format'     => array(
							'symbol'    => get_woocommerce_currency_symbol(),
							'decimal'   => esc_attr( wc_get_price_decimal_separator() ),
							'thousand'  => esc_attr( wc_get_price_thousand_separator() ),
							'precision' => wc_get_price_decimals(),
							'format'    => esc_attr(
								str_replace(
									array( '%1$s', '%2$s' ),
									array(
										'%s',
										'%v',
									),
									get_woocommerce_price_format()
								)
							),
						),
						'variations_handling' => defined( 'YITH_WCDP_PREMIUM_INIT' ) && YITH_WCDP_PREMIUM_INIT,
						'ajax_variations'     => defined( 'YITH_WCDP_PREMIUM_INIT' ) && YITH_WCDP_PREMIUM_INIT && 'yes' === get_option( 'yith_wcdp_general_enable_ajax_variation', 'no' ),
					)
				);
			}

			do_action( 'yith_wcdp_enqueue_frontend_style' );

			if ( is_product() || is_cart() || is_checkout() ) {
				wp_enqueue_style( 'yith-wcdp' );
			}
		}

		/**
		 * Add "Add deposit" option to single product page
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_single_add_deposit_button() {
			global $post;

			if ( ! is_product() ) {
				return;
			}

			// retrieve product and init add deposit template.
			$product                          = wc_get_product( $post->ID );
			$this->single_product_add_deposit = $this->print_single_add_deposit_to_cart_field( false );

			if ( $product->is_type( 'variable' ) ) {
				add_action(
					'woocommerce_before_single_variation',
					array(
						$this,
						'print_single_add_deposit_to_cart_template',
					)
				);
			} elseif ( $product->is_type( 'simple' ) ) {
				add_action(
					'woocommerce_before_add_to_cart_button',
					array(
						$this,
						'print_single_add_deposit_to_cart_template',
					)
				);
			} else {
				do_action( "yith_wcdp_{$product->get_type()}_add_to_cart", $product );
			}
		}

		/**
		 * Print fields before single add to cart, to let user add to cart deposit
		 *
		 * @param bool $echo Wheter to return template, or echo it.
		 *
		 * @return string Return template if param $echo is set to true
		 * @since 1.0.0
		 */
		public function print_single_add_deposit_to_cart_field( $echo = true ) {
			global $post;
			$template = '';

			if ( ! is_product() ) {
				return $template;
			}

			// retrieve product.
			$product = wc_get_product( $post->ID );

			// can the product be purchased?
			if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				return $template;
			}

			// is deposit enabled?
			$deposit_enabled = YITH_WCDP()->is_deposit_enabled_on_product( $product->get_id() );

			if ( ! $deposit_enabled ) {
				return $template;
			}

			// product options.
			$deposit_forced = YITH_WCDP()->is_deposit_mandatory( $product->get_id() );
			$deposit_amount = YITH_WCDP()->get_deposit_amount();
			$deposit_value  = min( YITH_WCDP()->get_deposit( $product->get_id() ), $product->get_price() );

			try {
				$support_cart = YITH_WCDP()->get_support_cart();
				$support_cart->add_to_cart( $product->get_id(), 1 );
				$support_cart->calculate_shipping();
			} catch ( Exception $e ) {
				return $template;
			}

			$deposit_shipping         = get_option( 'yith_wcdp_general_deposit_shipping', 'let_user_choose' );
			$let_user_choose_shipping = 'let_user_choose' === $deposit_shipping;

			$args = array(
				'product'            => $product,
				'deposit_enabled'    => $deposit_enabled,
				'default_deposit'    => true,
				'deposit_forced'     => $deposit_forced,
				'deposit_type'       => 'amount',
				'deposit_amount'     => $deposit_amount,
				'deposit_rate'       => 0,
				'deposit_value'      => $deposit_value,
				'needs_shipping'     => $support_cart->needs_shipping(),
				'show_shipping_form' => $let_user_choose_shipping,
			);

			ob_start();

			yith_wcdp_get_template( 'single-add-deposit-to-cart.php', $args );

			$template = ob_get_clean();

			if ( $echo ) {
				// template is already escaped.
				echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			return $template;
		}

		/**
		 * Print template of single add deposit to cart
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_single_add_deposit_to_cart_template() {
			// template is already escaped.
			echo $this->single_product_add_deposit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Add "Add deposit to cart" loop button, for each product in the loop
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function add_loop_add_deposit_button() {
			global $product;

			if ( ! apply_filters( 'yith_wcdp_show_add_to_cart_in_loop', $product->is_purchasable() && $product->is_in_stock() ) ) {
				return;
			}

			$deposit_enabled = YITH_WCDP()->is_deposit_enabled_on_product();
			$deposit_forced  = YITH_WCDP()->is_deposit_mandatory( $product->get_id() );

			if ( apply_filters( 'yith_wcdp_has_action_after_shop_loop_item', true ) && ! has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ) ) {
				add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
			}

			if ( $deposit_enabled ) {
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'print_loop_add_deposit_to_cart_template' ), 15 );

				if ( $deposit_forced ) {
					remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
				}
			}
		}

		/**
		 * Remove "Add deposit to cart" button after single loop item processed (so next product may not show it)
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function remove_loop_add_deposit_button() {
			remove_action( 'woocommerce_after_shop_loop_item', array( $this, 'print_loop_add_deposit_to_cart_template' ), 15 );
		}

		/**
		 * Prints template of loop add deposit to cart
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_loop_add_deposit_to_cart_template() {
			global $product;

			if ( ! $product ) {
				return;
			}

			$args = array(
				'product_url' => esc_url( $product->get_permalink() . '#yith-wcdp-add-deposit-to-cart' ),
			);

			yith_wcdp_get_template( 'loop-add-deposit-to-cart', $args );
		}

		/* === CART/CHECKOUT FRONTEND METHODS === */

		/**
		 * Adds item data to be shown on cart/checkout pages (this data won't be stored anywhere)
		 *
		 * @param array $data      Data to show on templates.
		 * @param array $cart_item Current cart item.
		 *
		 * @return mixed Filtered array of cart item data
		 * @since 1.0.0
		 */
		public function add_deposit_item_data( $data, $cart_item ) {

			if ( isset( $cart_item['deposit'] ) && $cart_item['deposit'] ) {

				$full_amount_item = array(
					'display' => wc_price(
						yith_wcdp_get_price_to_display(
							$cart_item['data'],
							array(
								'qty'   => intval( $cart_item['quantity'] ),
								'price' => $cart_item['deposit_value'] + $cart_item['deposit_balance'],
							)
						)
					),
					'name'    => apply_filters( 'yith_wcdp_full_price_filter', __( 'Full price', 'yith-woocommerce-deposits-and-down-payments' ) ),
					'value'   => $cart_item['deposit_value'] + $cart_item['deposit_balance'] * intval( $cart_item['quantity'] ),
				);

				$full_amount_item = apply_filters( 'yith_wcdp_full_amount_item', $full_amount_item, $cart_item, $data );

				$balance_item = array(
					'display' => wc_price(
						yith_wcdp_get_price_to_display(
							$cart_item['data'],
							array(
								'qty'   => intval( $cart_item['quantity'] ),
								'price' => $cart_item['deposit_balance'],
							)
						)
					),
					'name'    => apply_filters( 'yith_wcdp_balance_filter', __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ) ),
					'value'   => $cart_item['deposit_balance'] * intval( $cart_item['quantity'] ),
				);
				$balance_item = apply_filters( 'yith_wcdp_balance_item', $balance_item, $cart_item, $data );

				if ( ! in_array( $full_amount_item, $data, true ) ) {
					$data[] = $full_amount_item;
				}

				if ( ! in_array( $balance_item, $data, true ) ) {
					$data[] = $balance_item;
				}
			}

			return $data;
		}

		/**
		 * Filter product name on cart and checkout views, to show deposit label
		 *
		 * @param string $product_name Original product name.
		 * @param array  $cart_item    Cart item object.
		 *
		 * @return string Filtered product name
		 * @since 1.0.0
		 */
		public function filter_cart_deposit_product_name( $product_name, $cart_item ) {
			if ( isset( $cart_item['deposit'] ) && $cart_item['deposit'] ) {
				$deposit_label = apply_filters( 'yith_wcdp_deposit_label', __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ) );
				$product_name  = str_replace( $deposit_label . ': ', '', $product_name );

				if ( is_cart() ) {
					$product_name = preg_replace( '^(<a.*>)(.*)(<\/a>)$^', sprintf( '$1%s: $2$3', $deposit_label ), $product_name );
				} elseif ( is_checkout() ) {
					$product_name = apply_filters( 'yith_wcdp_deposit_label', __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ) . ': ' . $product_name;
				}
			}

			return $product_name;
		}

		/**
		 * Filter product name on review-order view, to show deposit label
		 *
		 * @param string $product_name  Original product name.
		 * @param array  $order_item    Order item object.
		 *
		 * @return string Filtered product name
		 * @since 1.0.0
		 */
		public function filter_order_deposit_product_name( $product_name, $order_item ) {
			$deposit_label      = apply_filters( 'yith_wcdp_deposit_label', __( 'Deposit', 'yith-woocommerce-deposits-and-down-payments' ) );
			$full_payment_label = apply_filters( 'yith_wcdp_full_payment_label', __( 'Full payment', 'yith-woocommerce-deposits-and-down-payments' ) );

			$product_name = str_replace( $deposit_label . ': ', '', $product_name );
			$product_name = str_replace( $full_payment_label . ': ', '', $product_name );

			if ( is_array( $order_item ) ) {
				if ( isset( $order_item['deposit'] ) && $order_item['deposit'] ) {
					$product_name = preg_replace( '^(<a.*>)(.*)(<\/a>)$^', sprintf( '$1%s: $2$3', $deposit_label ), $product_name );
				} elseif ( isset( $order_item['full_payment'] ) && $order_item['full_payment'] ) {
					$product_name = preg_replace( '^(<a.*>)(.*)(<\/a>)$^', sprintf( '$1%s: $2$3', $full_payment_label ), $product_name );
				}
			}

			return $product_name;
		}

		/**
		 * Update cart total in the case deposits are added to cart
		 *
		 * @param string $total_html Cart total.
		 *
		 * @return string Filtered cart total
		 * @since 1.1.3
		 */
		public function filter_cart_total( $total_html ) {
			$totals = $this->get_grand_totals();

			// extract variables needed.
			list( $total, $has_deposits, $packages ) = yith_plugin_fw_extract( $totals, 'total', 'has_deposits', 'packages' );

			if ( $total && $has_deposits ) {
				$total_html .= apply_filters( 'yith_wcdp_show_cart_total_html', sprintf( ' (%s <strong>%s</strong>)', __( 'of', 'yith-woocommerce-deposits-and-down-payments' ), wc_price( $total ) ), WC()->cart, $total_html, $total, $packages );
			}

			return $total_html;
		}

		/**
		 * Filter order status label for partially paid orders
		 *
		 * @param string   $status Current status.
		 * @param WC_Order $order  Current order.
		 *
		 * @return string Filtered status
		 * @since 1.0.0
		 */
		public function filter_order_status( $status, $order ) {
			if ( is_account_page() && $order->get_meta( '_has_deposit' ) && in_array( $status, array( 'completed', 'processing' ), true ) ) {
				$suborders = YITH_WCDP_Suborders()->get_suborder( $order->get_id() );

				if ( $suborders ) {
					foreach ( $suborders as $suborder_id ) {
						$suborder = wc_get_order( $suborder_id );
						if ( ! $suborder->has_status( array( 'completed', 'processing' ) ) ) {
							$status = apply_filters( 'yith_wcdp_partially_paid_status', 'partially-paid', $suborder );
						}
					}
				}
			}

			return $status;
		}

		/**
		 * Filter order status labels to print "Partially paid" status
		 *
		 * @param array $labels Current available labels.
		 *
		 * @return array Filtered labels
		 * @since 1.0.0
		 */
		public function filter_order_status_labels( $labels ) {
			$labels['wc-partially-paid'] = apply_filters( 'yith_wcdp_partially_paid_status_label', __( 'Partially Paid', 'yith-woocommerce-deposits-and-down-payments' ) );

			return $labels;
		}

		/**
		 * Print balance lines at checkout
		 *
		 * @param string $context Context for the template (checkout/email).
		 *
		 * @return void
		 * @since 1.3.6
		 */
		public function print_balance_totals( $context = '' ) {
			$totals = $this->get_grand_totals();

			if ( ! $totals['has_deposits'] || ! $totals['total'] ) {
				return;
			}

			?>
			<tr class="balance-shipping-total">
				<th <?php echo 'email' === $context ? 'class="td" colspan="2"' : ''; ?>>
					<?php
					// translators: 1. Balance.
					echo esc_html( apply_filters( 'yith_wcdp_balance_subtotal', sprintf( __( '%s subtotal', 'yith-woocommerce-deposits-and-down-payments' ), apply_filters( 'yith_wcdp_balance_filter', __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ) ) ) ) );
					?>
				</th>
				<td <?php echo 'email' === $context ? 'class="td"' : ''; ?> >
					<?php echo wc_price( $totals['balance'] ); // phpcs:ignore ?>
				</td>
			</tr>

			<?php
			if ( ! empty( $totals['balance_shipping'] ) ) :
				?>
				<tr class="balance-shipping-total">
					<th <?php echo 'email' === $context ? 'class="td" colspan="2"' : ''; ?>>
						<?php
						// translators: 1. Balance.
						echo esc_html( sprintf( __( '%s shipping', 'yith-woocommerce-deposits' ), apply_filters( 'yith_wcdp_balance_filter', __( 'Balance', 'yith-woocommerce-deposits-and-down-payments' ) ) ) );
						?>
					</th>
					<td <?php echo 'email' === $context ? 'class="td"' : ''; ?> >
						<?php echo wc_price( $totals['balance_shipping'] ); // phpcs:ignore
						?>
					</td>
				</tr>
				<?php
			endif;
		}

		/* === MY ACCOUNT FRONTEND METHODS === */

		/**
		 * Filter "Recent orders" my-account section query
		 *
		 * @param array $query_vars Array of query var.
		 *
		 * @return array Filtered query var
		 * @since  1.0.0
		 */
		public function filter_my_account_my_orders_query( $query_vars ) {
			$child_orders_ids = array();
			$child_orders     = YITH_WCDP_Suborders()->get_child_orders();

			if ( ! empty( $child_orders ) ) {
				$child_orders_ids = wp_list_pluck( $child_orders, 'ID' );
			}

			$query_vars['exclude'] = $child_orders_ids;

			return $query_vars;
		}

		/**
		 * Filter order total price html, to show deposit info
		 *
		 * @param string   $total_html Original HTML for order total.
		 * @param WC_Order $order      Current order.
		 *
		 * @return string Filtered total HTML
		 * @since 1.0.0
		 */
		public function filter_my_account_my_orders_total( $total_html, $order ) {
			$suborders = false;

			if ( $order->get_meta( '_has_deposit' ) ) {
				$total_html = wc_price( $order->get_total() );

				$total     = $order->get_total();
				$suborders = $order->get_meta( '_full_payment_orders' );
				if ( ! empty( $suborders ) ) {
					foreach ( $suborders as $suborder_id ) {
						$suborder = wc_get_order( $suborder_id );

						if ( ! $suborder ) {
							continue;
						}

						$total += $suborder->get_total();
					}
				}

				$total_html .= sprintf( ' (%s <strong>%s</strong>)', __( 'of', 'yith-woocommerce-deposits-and-down-payments' ), wc_price( $total ) );
			}

			return apply_filters( 'yith_wcdp_show_total_html', $total_html, $order, $suborders );
		}

		/**
		 * Filter order total price html, to show deposit info
		 *
		 * @param array    $actions Availabl order actions.
		 * @param WC_Order $order   Current order.
		 *
		 * @return array Filtered array of available order actions.
		 * @since 1.0.0
		 */
		public function filter_my_account_my_orders_actions( $actions, $order ) {
			if ( $order->get_meta( '_has_deposit' ) ) {
				$actions['view_full_amount_payments'] = array(
					'url'  => $order->get_view_order_url() . '#yith_wcdp_deposits_details',
					'name' => __( 'View Full Payment', 'yith-woocommerce-deposits-and-down-payments' ),
				);
			}

			return $actions;
		}

		/**
		 * Prints full amount payments for an order (order-detail view)
		 *
		 * @param WC_Order $order Current order.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function print_full_amount_payments_orders( $order ) {
			if ( $order->get_meta( '_has_deposit' ) ) {
				$deposits  = array();
				$suborders = YITH_WCDP_Suborders()->get_suborder( $order->get_id() );

				foreach ( $suborders as $suborder_id ) {
					$suborder = wc_get_order( $suborder_id );

					$product_list = array();
					$items        = $suborder->get_items( 'line_item' );

					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							/**
							 * Every single product item of the order
							 *
							 * @var $item \WC_Order_Item_Product
							 */
							$product        = $item->get_product();
							$product_list[] = sprintf( '<a href="%s">%s</a>', $product->get_permalink(), $item->get_name() );
						}
					}

					$actions = array();

					if ( $suborder->needs_payment() ) {
						$actions['pay'] = array(
							'url'  => $suborder->get_checkout_payment_url(),
							'name' => __( 'Pay', 'woocommerce' ),
						);
					}

					if ( $suborder->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_cancel', array( 'pending', 'failed' ), $suborder ) ) ) {
						$actions['cancel'] = array(
							'url'  => $suborder->get_cancel_order_url( wc_get_page_permalink( 'myaccount' ) ),
							'name' => __( 'Cancel', 'woocommerce' ),
						);
					}

					$actions['view'] = array(
						'url'  => $suborder->get_view_order_url(),
						'name' => __( 'View', 'woocommerce' ),
					);

					$actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $suborder );

					$deposits[] = array(
						'suborder_id'       => $suborder_id,
						'suborder_view_url' => $suborder->get_view_order_url(),
						'product_list'      => $product_list,
						'order_status'      => $suborder->get_status(),
						'order_paid'        => $suborder->has_status( array( 'processing', 'completed' ) ) ? $suborder->get_total() : 0,
						'order_subtotal'    => $suborder->get_subtotal(),
						'order_discount'    => $suborder->get_total_discount(),
						'order_shipping'    => $suborder->get_shipping_total(),
						'order_taxes'       => array_sum( wp_list_pluck( $suborder->get_tax_totals(), 'amount' ) ),
						'order_to_pay'      => $suborder->has_status( array( 'processing', 'completed' ) ) ? 0 : $suborder->get_total(),
						'actions'           => $actions,
					);
				}

				$args = array(
					'order'    => $order,
					'order_id' => $order->get_id(),
					'deposits' => $deposits,
				);

				yith_wcdp_get_template( 'my-deposits.php', $args );
			}
		}

		/* === UTILITY METHODS === */

		/**
		 * Retrieve totals for current cart/deposit configuration
		 *
		 * @return array Array of totals for current cart
		 * @since 1.3.6
		 */
		public function get_grand_totals() {
			if ( ! empty( $this->grand_totals ) ) {
				return $this->grand_totals;
			}

			$cart_hash          = WC()->cart->get_cart_hash();
			$this->grand_totals = WC()->session->get( 'yith_wcdp_grand_totals_' . $cart_hash );

			if ( ! empty( $this->grand_totals ) ) {
				return $this->grand_totals;
			}

			$support_cart   = YITH_WCDP()->get_support_cart();
			$main_cart      = WC()->cart;
			$cart_contents  = $main_cart->cart_contents;
			$cart_coupons   = $main_cart->get_coupons();
			$has_deposits   = false;
			$grand_total    = 0;
			$shipping_total = 0;
			$fees_total     = 0;
			$balance_type   = get_option( 'yith_wcdp_balance_type', 'multiple' );
			$packages       = array();

			/**
			 * In order to improve calculation for methods that uses actual cart,
			 * switch WC cart with our support cart
			 *
			 * @since  1.3.6
			 */
			WC()->cart = $support_cart;

			if ( ! empty( $cart_contents ) ) {
				foreach ( $cart_contents as $cart_item ) {
					if ( ! empty( $cart_item['deposit'] ) ) {
						$has_deposits = true;
					}

					if ( ! isset( $cart_item['deposit'] ) || ! $cart_item['deposit'] || ! apply_filters( 'yith_wcdp_virtual_on_deposit', true, null ) ) {
						$packages['common']   = isset( $packages['common'] ) ? $packages['common'] : array();
						$packages['common'][] = $cart_item;
					} elseif ( 'single' === $balance_type ) {
						$packages['single_balance']   = isset( $packages['single_balance'] ) ? $packages['single_balance'] : array();
						$packages['single_balance'][] = $cart_item;
					} else {
						$packages[] = array( $cart_item );
					}
				}
			}

			if ( ! empty( $packages ) ) {
				foreach ( $packages as $package ) {
					$support_cart->populate( $package );

					// apply coupons (when required and possible).
					if ( apply_filters( 'yith_wcdp_propagate_coupons', false ) && ! empty( $cart_coupons ) ) {
						foreach ( $cart_coupons as $code => $coupon ) {
							if ( apply_filters( 'yith_wcdp_propagate_coupon', true, $code ) ) {
								WC()->cart->add_discount( $code );
							}
						}
						wc_clear_notices();
					}

					$package_total    = $support_cart->get_total( 'edit' );
					$package_shipping = $support_cart->get_shipping_total() + $support_cart->get_shipping_tax();

					$grand_total    += $package_total;
					$shipping_total += $package_shipping;

					$support_cart->empty_cart();
				}
			}

			/**
			 * Switch back to default cart
			 *
			 * @since 1.3.6
			 */
			WC()->cart = $main_cart;

			// Main cart totals.
			$main_cart->calculate_totals();
			$main_cart_total    = $main_cart->get_total( 'edit' );
			$main_cart_shipping = $main_cart->get_shipping_total() + $main_cart->get_shipping_tax();
			if ( $has_deposits && $grand_total ) {
				// Coupons.
				$applied_coupons = $main_cart->get_coupon_discount_totals();

				if ( array( $applied_coupons ) && ! empty( $applied_coupons ) && ! apply_filters( 'yith_wcdp_propagate_coupons', false ) ) {

					$coupon_amount = array_sum( array_values( $applied_coupons ) );

					$coupon_tax = $main_cart->get_coupon_discount_tax_totals();

					if ( array( $coupon_tax ) && ! empty( $coupon_tax ) ) {
						$coupon_tax     = array_sum( array_values( $coupon_tax ) );
						$coupon_amount += $coupon_tax;
					}

					$grand_total -= $coupon_amount;
				}

				// Fees.
				$fees_total = $main_cart->get_fee_total();
			}

			// Balances totals.
			$balance_shipping = $shipping_total - $main_cart_shipping;
			$balance_total    = ! empty( $grand_total ) ? $grand_total - $main_cart_total - $balance_shipping : 0;

			$this->grand_totals = array(
				'total'            => $grand_total,
				'balance'          => $balance_total,
				'coupons'          => isset( $applied_coupons ) ? $applied_coupons : 0,
				'fees'             => $fees_total,
				'balance_shipping' => $balance_shipping,
				'shipping_total'   => $shipping_total,
				'has_deposits'     => $has_deposits,
				'packages'         => $packages,
			);

			WC()->session->set( 'yith_wcdp_grand_totals_' . $cart_hash, $this->grand_totals );

			return $this->grand_totals;
		}

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCDP_Frontend
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
 * Unique access to instance of YITH_WCDP_Frontend class
 *
 * @return \YITH_WCDP_Frontend
 * @since 1.0.0
 */
function YITH_WCDP_Frontend() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WCDP_Frontend::get_instance();
}
