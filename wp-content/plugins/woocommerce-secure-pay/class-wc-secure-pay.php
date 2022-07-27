<?php
/**
 * WooCommerce SecurePay Gateway Plugin
 *
 * @package FgcSecurepayApi/sdk
 */

/**
 * Plugin Name: WooCommerce SecurePay Gateway
 * Plugin URI: https://www.sydneyecommerce.com.au
 * Description: Extends WooCommerce with SecurePay payment gateway.
 * Version: 1.2.0
 * Author: Sydney Ecommerce
 * Author URI: https://www.sydneyecommerce.com.au
 * Copyright: Sydney Ecommerce
 */

require 'sdk/class-secure-pay.php';

define( '__FGC_SECURE_PAY_BASE_DIR__', plugin_dir_path( __FILE__ ) );
define( '__FGC_SECURE_PAY_STATIC__', plugin_dir_url( __FILE__ ) . 'web/' );
add_action( 'plugins_loaded', 'woocommerce_secure_pay_init', 0 );

/**
 *  Init payment method
 */
function woocommerce_secure_pay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway_CC' ) ) {
		return;
	}
	/**
	 * Localisation
	 */
	load_plugin_textdomain(
		'wc-gateway-name',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	/**
	 * Gateway class
	 */
	class WC_Secure_Pay extends WC_Payment_Gateway_CC {

		/**
		 * Test mode
		 *
		 * @var $test_mode bool
		 */
		protected $test_mode;
		/**
		 * Test mode
		 *
		 * @var $debug bool
		 */
		protected $debug;
		/**
		 * Merchant code
		 *
		 * @var $merchant_code
		 */
		protected $merchant_code;
		/**
		 * Client ID
		 *
		 * @var $client_id
		 */
		protected $client_id;
		/**
		 * Client secret
		 *
		 * @var $client_secret
		 */
		protected $client_secret;
		/**
		 * Store ID
		 *
		 * @var $store_id
		 */
		protected $store_id;
		/**
		 * Allowed card type
		 *
		 * @var array $allowed_card_types
		 */
		protected $allowed_card_types;
		/**
		 * Is captured immediately?
		 *
		 * @var $is_capture_immediately
		 */
		protected $is_capture_immediately;
		/**
		 * Error message holder
		 *
		 * @var $error_message
		 */
		protected $error_message;
		/**
		 * Transaction declined message holder
		 *
		 * @var $transaction_declined_message
		 */
		protected $transaction_declined_message;

		const PAYMENT_STATUS            = 'payment_status';
		const PAYMENT_STATUS_PAID       = 'paid';
		const PAYMENT_STATUS_AUTHORIZED = 'authorized';
		const PAYMENT_STATUS_CAPTURED   = 'captured';
		const PAYMENT_STATUS_CANCELLED  = 'cancelled';
		const PAYMENT_STATUS_REFUNDED   = 'refunded';
		const PAYMENT_LOG               = 'payment_log';

		const SECUREPAYAPI_NONCE = 'fgc_securepay_nonce';

		/**
		 * WC_Secure_Pay constructor.
		 */
		public function __construct() {
			$this->id                 = 'fgc_secure_pay';
			$this->icon               = __FGC_SECURE_PAY_STATIC__ . '/img/logo.png';
			$this->has_fields         = true;
			$this->method_title       = __( 'SecurePay', 'woocommerce' );
			$this->method_description = __( 'Australia Post SecurePay Payment Gateway', 'woocommerce' );

			$this->supports = array(
				'products',
				'refunds',
				'tokenization',
			);

			$this->init_form_fields();

			$this->init_settings();
			$this->title                        = $this->get_option( 'title' );
			$this->description                  = $this->get_option( 'description' );
			$this->store_id                     = $this->get_option( 'store_id' );
			$this->test_mode                    = 'yes' === $this->get_option( 'testmode' );
			$this->debug                        = 'yes' === $this->get_option( 'debug' );
			$this->merchant_code                = $this->get_option( 'merchant_code' );
			$this->client_id                    = $this->get_option( 'client_id' );
			$this->client_secret                = $this->get_option( 'client_secret' );
			$this->allowed_card_types           = $this->get_option( 'allowed_card_types' );
			$this->is_capture_immediately       = 'yes' === $this->get_option( 'capture' );
			$this->error_message                = __(
				'We had a problem processing your order, please try again. If the problem persists, please contact support.',
				'woocommerce'
			);
			$this->transaction_declined_message = __( 'Your transaction was declined: ', 'woocommerce' );

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				array(
					$this,
					'process_admin_options',
				)
			);
			add_action(
				'woocommerce_order_item_add_action_buttons',
				array(
					$this,
					'action_woocommerce_order_item_add_action_buttons',
				),
				10,
				1
			);
			add_action( 'save_post', array( $this, 'handle_pre_auth_payment_submit' ), 10, 3 );
			wp_register_style(
				'securepay-form-css',
				esc_url_raw( __FGC_SECURE_PAY_STATIC__ ) . 'css/secure-pay.css',
				array(),
				'1.0.0'
			);
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$payment_token_index      = "wc-{$this->id}-payment-token";
			$new_payment_method_index = "wc-{$this->id}-new-payment-method";
			$securepay_token_index    = 'securePayToken';
			if ( isset( $_POST[ self::SECUREPAYAPI_NONCE ] ) ) {
				$order         = wc_get_order( $order_id );
				$payment_token = null;
				if ( isset( $_POST[ $payment_token_index ] ) ) {
					$payment_token = sanitize_text_field( wp_unslash( $_POST[ $payment_token_index ] ) );
				}

				$is_payment_success = false;
				if ( null === $payment_token || 'new' === $payment_token ) {
					if ( isset( $_POST[ $securepay_token_index ] ) ) {
						$is_save_credit_card = false;
						if ( isset( $_POST[ $new_payment_method_index ] ) ) {
							$is_save_credit_card = (bool) $_POST[ $new_payment_method_index ];
						}
						$is_payment_success = $this->charge_tokenized_card(
							$order,
							sanitize_text_field( wp_unslash( $_POST[ $securepay_token_index ] ) ),
							$is_save_credit_card
						);
					}
				} else {
					$token              = sanitize_text_field( wp_unslash( $_POST[ $payment_token_index ] ) );
					$is_payment_success = $this->charge_saved_token( $order, $token );
				}
				if ( $is_payment_success ) {
					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);
				} else {
					return array(
						'result'   => 'fail',
						'redirect' => '',
					);
				}
			} else {
				$this->debug_log( 'Error', 'nonce not received' );
				$this->debug_log( 'Raw POST data', $_POST );
			}

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		/**
		 * Custom error logging function
		 *
		 * @param string $message Message.
		 * @param mixed  $object  Any object.
		 */
		private function debug_log( $message, $object = array() ) {
			if ( $this->debug ) {
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				$logger  = wc_get_logger();
				$bt      = debug_backtrace();
				$caller  = array_shift( $bt );
				$message = '[' . $caller['file'] . ' (' . $caller['line'] . ')] ' . $message . ' ';
				if ( is_string( $object ) || is_numeric( $object ) ) {
					$message .= $object;
				} else {
					$log_object = wp_json_encode( $object );
					if ( ! is_string( $log_object ) && ! is_numeric( $log_object ) ) {
						$log_object = print_r( $log_object, 1 );
					}
					$message .= $log_object;
				}
				$logger->debug( $message, array( 'source' => 'wc-securepay' ) );
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}

		/**
		 * Form include function
		 */
		public function form() {
			wp_enqueue_style( 'securepay-form-css' );
			$securepay_data = array(
				'id'               => $this->id,
				'clientId'         => $this->client_id,
				'merchantCode'     => $this->merchant_code,
				'allowedCardTypes' => wp_json_encode( $this->allowed_card_types ),
			);
			wp_localize_script( 'securepay-form-js', 'SecurepayData', $securepay_data );
			wp_enqueue_script( 'securepay-form-js' );
			include __DIR__ . '/web/templates/secure-pay-cc.phtml';
		}

		/**
		 * Add order note
		 * Add 'log' object to order meta, these logs used for generating SecurePay details table
		 *
		 * @param WC_Order $order             Woocommerce Order.
		 * @param string   $tag               Tag.
		 * @param array    $secure_pay_return Array returned from Secure_Pay functions.
		 */
		private function add_order_payment_log( &$order, $tag, $secure_pay_return ) {
			$order->add_order_note(
				"SecurePay: Payment $tag" . ( isset( $secure_pay_return['bankTransactionId'] )
					? ", transaction id: {$secure_pay_return['bankTransactionId']}." : '.' )
			);
			$payment_log = $order->get_meta( self::PAYMENT_LOG );
			if ( ! is_array( $payment_log ) ) {
				$payment_log = array();
			}
			if ( isset( $secure_pay_return['createdAt'] ) ) {
				$payment_log[] = array(
					$tag => array(
						'created_at'          => strtotime( $secure_pay_return['createdAt'] ),
						'bank_transaction_id' => $secure_pay_return['bankTransactionId'],
					),
				);
			} else {
				$payment_log[] = array(
					$tag => array(
						'created_at' => strtotime( 'now' ),
					),
				);
			}
			$order->add_meta_data( self::PAYMENT_LOG, $payment_log, true );
		}

		/**
		 * Charge newly tokenized card
		 *
		 * @param WC_Order $order               Woocommerce Order.
		 * @param string   $token               Card token.
		 * @param bool     $is_save_credit_card Need to save card after payment success.
		 *
		 * @return bool True if transaction success, otherwise false
		 */
		private function charge_tokenized_card( $order, $token, $is_save_credit_card ) {
			$scope = array( Secure_Pay::SCOPE_PAYMENT_WRITE );
			if ( $is_save_credit_card ) {
				$scope = array_merge( $scope, array( Secure_Pay::SCOPE_PAYMENT_INSTRUMENTS_WRITE ) );
			}
			$secure_pay  = new Secure_Pay( $this->store_id );
			$init_result = $secure_pay->init_with_authentication(
				$this->client_id,
				$this->client_secret,
				$this->merchant_code,
				$scope,
				! $this->test_mode
			);
			if ( false === $init_result['error'] ) {
				if ( $this->is_capture_immediately ) {
					$create_payment_result = $secure_pay->create_payment(
						$token,
						WC_Geolocation::get_ip_address(),
						$order->get_total() * 100,
						$order->get_id()
					);
				} else {
					$create_payment_result = $secure_pay->create_pre_auth_payment(
						$token,
						WC_Geolocation::get_ip_address(),
						$order->get_total() * 100,
						$order->get_id()
					);
				}
				if ( $create_payment_result['error'] ) {
					wc_add_notice(
						isset( $create_payment_result['gatewayResponseMessage'] )
							? $this->transaction_declined_message . $create_payment_result['gatewayResponseMessage']
							: $this->error_message,
						'error'
					);
					$order->add_order_note(
						sprintf(
							'Payment Failed: %s - %s',
							$create_payment_result['errorCode'],
							$create_payment_result['errorDetail']
						)
					);
					$order->save();
					$this->debug_log( 'Error creating payment', wp_json_encode( $create_payment_result ) );

					return false;
				} else {
					wc_empty_cart();
					if ( $this->is_capture_immediately ) {
						$order->payment_complete();
						$order->add_meta_data( self::PAYMENT_STATUS, self::PAYMENT_STATUS_PAID, true );
						$this->add_order_payment_log( $order, self::PAYMENT_STATUS_PAID, $create_payment_result );
						$order->save_meta_data();
						$order->save();
					} else {
						$order->set_status( 'on-hold' );
						$order->add_meta_data( self::PAYMENT_STATUS, self::PAYMENT_STATUS_AUTHORIZED, true );
						$this->add_order_payment_log( $order, self::PAYMENT_STATUS_AUTHORIZED, $create_payment_result );
						$order->save_meta_data();
						$order->save();
					}
					if ( $is_save_credit_card ) {
						$create_instrument_result = $secure_pay->create_payment_instrument(
							$order->get_customer_id(),
							$token,
							WC_Geolocation::get_ip_address()
						);
						if ( false === $create_instrument_result['error'] ) {
							$method_token = new WC_Payment_Token_CC();
							$method_token->set_token( $create_instrument_result['token'] );
							$method_token->set_card_type( $create_instrument_result['scheme'] );
							$method_token->set_last4( $create_instrument_result['last4'] );
							$method_token->set_expiry_month( $create_instrument_result['expiryMonth'] );
							$method_token->set_expiry_year( '20' . $create_instrument_result['expiryYear'] );
							$method_token->set_user_id( $order->get_customer_id() );
							$method_token->set_gateway_id( $this->id );
							$method_token->save();
							$this->debug_log( 'New payment instrument saved successfully' );
						} else {
							$this->debug_log(
								'Error saving instrument for later use',
								wp_json_encode( $create_instrument_result )
							);
						}
					}
					$this->debug_log( 'Payment successful' );

					return true;
				}
			} else {
				wc_add_notice(
					isset( $init_result['gatewayResponseMessage'] ) ? $this->transaction_declined_message .
						$init_result['gatewayResponseMessage'] : $this->error_message,
					'error'
				);
				$this->debug_log( 'Error initializing payment', wp_json_encode( $init_result ) );

				return false;
			}
		}

		/**
		 * Charge saved card token
		 *
		 * @param WC_Order $order Woocommerce Order.
		 * @param string   $token Card token.
		 *
		 * @return bool True if transaction success, otherwise false
		 */
		private function charge_saved_token( $order, $token ) {
			$secure_pay  = new Secure_Pay( $this->store_id );
			$init_result = $secure_pay->init_with_authentication(
				$this->client_id,
				$this->client_secret,
				$this->merchant_code,
				array( Secure_Pay::SCOPE_PAYMENT_INSTRUMENTS_WRITE ),
				! $this->test_mode
			);
			if ( false === $init_result['error'] ) {
				$create_payment_result = $secure_pay->create_payment_from_instrument(
					$order->get_customer_id(),
					$token,
					WC_Geolocation::get_ip_address(),
					$order->get_total() * 100,
					$order->get_id()
				);

				if ( ! ( $create_payment_result['error'] ) ) {
					wc_empty_cart();
					$order->payment_complete();
					$order->add_meta_data( self::PAYMENT_STATUS, self::PAYMENT_STATUS_PAID, true );
					$this->add_order_payment_log( $order, self::PAYMENT_STATUS_PAID, $create_payment_result );
					$order->save_meta_data();
					$order->save();
					$this->debug_log( 'Payment successful' );

					return true;
				} else {
					wc_add_notice(
						isset( $create_payment_result['gatewayResponseMessage'] )
							? $this->transaction_declined_message . $create_payment_result['gatewayResponseMessage']
							: $this->error_message,
						'error'
					);
					$order->add_order_note(
						sprintf(
							'Payment Failed: %s - %s',
							$create_payment_result['errorCode'],
							$create_payment_result['errorDetail']
						)
					);
					$order->save();
					$this->debug_log( 'Error creating payment', $create_payment_result );
				}
			} else {
				wc_add_notice(
					isset( $init_result['gatewayResponseMessage'] ) ? $this->transaction_declined_message .
						$init_result['gatewayResponseMessage'] : $this->error_message,
					'error'
				);
				$this->debug_log( 'Error initialing payment', $init_result );
			}

			return false;
		}

		/**
		 * Get list of saved card token on SecurePay gateway server
		 *
		 * @param Secure_Pay $secure_pay  Initialized SecurePay Object.
		 * @param string     $customer_id Customer Id.
		 *
		 * @return array
		 */
		private function get_payment_instruments( $secure_pay, $customer_id ) {
			$payment_instruments        = array();
			$payment_instruments_result =
				$secure_pay->list_payment_instrument( $customer_id, WC_Geolocation::get_ip_address() );
			if ( false === $payment_instruments_result['error'] ) {
				$payment_instruments = $payment_instruments_result['paymentInstruments'];
			} else {
				$this->debug_log( 'Error getting payment instrument list from gateway', $payment_instruments_result );
			}

			return $payment_instruments;
		}

		/**
		 * Process refund request from admin
		 *
		 * @param int    $order_id Order Id.
		 * @param null   $amount   Amount.
		 * @param string $reason   Resason to refund.
		 *
		 * @return bool|WP_Error
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			if ( isset( $amount ) ) {
				$order       = wc_get_order( $order_id );
				$secure_pay  = new Secure_Pay( $this->store_id );
				$init_result = $secure_pay->init_with_authentication(
					$this->client_id,
					$this->client_secret,
					$this->merchant_code,
					array( Secure_Pay::SCOPE_PAYMENT_WRITE ),
					! $this->test_mode
				);

				if ( true === $init_result['error'] ) {
					$this->debug_log( 'Error initialing refund', $init_result );

					return new WP_Error( 'Init error', $init_result['errorDetail'] );
				}
				$refund_result =
					$secure_pay->refund_payment( $order->get_customer_ip_address(), $amount * 100, $order_id );
				if ( $refund_result['error'] ) {
					$this->debug_log( 'Error making refund', $refund_result );

					return new WP_Error( 'Refund error', $refund_result['errorDetail'] );
				}
				$order->add_meta_data( self::PAYMENT_STATUS_REFUNDED, self::PAYMENT_STATUS_REFUNDED, true );
				$this->add_order_payment_log( $order, self::PAYMENT_STATUS_REFUNDED, $refund_result );
				$order->payment_complete();
				$order->save();
				$this->debug_log( 'Refund successfully' );

				return true;
			}
			$this->debug_log( 'Refund amount is not set' );

			return false;
		}

		/**
		 * Handle payment method request from My Account section
		 *
		 * @return array
		 */
		public function add_payment_method() {
			if ( isset( $_POST['securePayToken'], $_POST[ self::SECUREPAYAPI_NONCE ] ) &&
				wp_verify_nonce( sanitize_key( $_POST[ self::SECUREPAYAPI_NONCE ] ), self::SECUREPAYAPI_NONCE )
			) {
				$securepay_token = sanitize_text_field( wp_unslash( $_POST['securePayToken'] ) );
				if ( $securepay_token ) {
					$secure_pay  = new Secure_Pay( $this->store_id );
					$init_result = $secure_pay->init_with_authentication(
						$this->client_id,
						$this->client_secret,
						$this->merchant_code,
						array(
							Secure_Pay::SCOPE_PAYMENT_INSTRUMENTS_WRITE,
							Secure_Pay::SCOPE_PAYMENT_INSTRUMENTS_READ,
						),
						! $this->test_mode
					);
					if ( ! $init_result['error'] ) {
						$user          = wp_get_current_user();
						$create_result = $secure_pay->create_payment_instrument(
							$user->ID,
							$securepay_token,
							WC_Geolocation::get_ip_address()
						);
						if ( ! $create_result['error'] ) {
							$saved_tokens     = WC_Payment_Tokens::get_customer_tokens( $user->ID, $this->id );
							$local_tokens     = array();
							$duplicated_found = false;
							foreach ( $saved_tokens as $saved_token ) {
								if ( $saved_token->get_token() === $create_result['token'] ) {
									$saved_token->set_card_type( $create_result['scheme'] );
									$saved_token->set_last4( $create_result['last4'] );
									$saved_token->set_expiry_month( $create_result['expiryMonth'] );
									$saved_token->set_expiry_year( '20' . $create_result['expiryYear'] );
									$saved_token->save();
									wc_add_notice(
										__( 'Duplicated token found, payment method will be updated.', 'woocommerce' )
									);
									$duplicated_found = true;
								}
								$local_tokens[] = $saved_token->get_token();
							}
							if ( ! $duplicated_found ) {
								$method_token = new WC_Payment_Token_CC();
								$method_token->set_token( $create_result['token'] );
								$method_token->set_card_type( $create_result['scheme'] );
								$method_token->set_last4( $create_result['last4'] );
								$method_token->set_expiry_month( $create_result['expiryMonth'] );
								$method_token->set_expiry_year( '20' . $create_result['expiryYear'] );
								$method_token->set_user_id( $user->ID );
								$method_token->set_gateway_id( $this->id );
								$method_token->save();
								$local_tokens[] = $method_token->get_token();
							}
							$gateway_tokens = array();
							$instruments    = $this->get_payment_instruments( $secure_pay, $user->ID );
							foreach ( $instruments as $instrument ) {
								if ( ! in_array( $instrument['token'], $local_tokens, true ) ) {
									$secure_pay->delete_payment_instrument(
										$user->ID,
										$instrument['token'],
										WC_Geolocation::get_ip_address()
									);
								} else {
									$gateway_tokens[] = $instrument['token'];
								}
							}

							foreach ( $saved_tokens as $saved_token ) {
								if ( ! in_array( $saved_token->get_token(), $gateway_tokens, true ) ) {
									WC_Payment_Tokens::delete( $saved_token->get_id() );
								}
							}
							$this->debug_log( 'Add payment instrument successfully' );

							return array(
								'result'   => 'success',
								'redirect' => wc_get_endpoint_url( 'payment-methods' ),
							);
						} else {
							$this->debug_log( 'Error creating payment instrument', $create_result );
						}
					} else {
						$this->debug_log( 'Error initializing create payment instrument', $init_result );
					}
				} else {
					$this->debug_log( 'Securepay token not found in request', $_POST );
				}
			} else {
				$this->debug_log( 'Failed to parse request', $_POST );
			}

			return array(
				'result'   => 'fail',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}

		/**
		 * Generate saved card token html
		 *
		 * @param WC_Payment_Token $token Woocommerce Payment token.
		 *
		 * @return mixed|string|void
		 */
		public function get_saved_payment_method_option_html( $token ) {
			$html = sprintf(
				'<li class="woocommerce-SavedPaymentMethods-token">
				<input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%5$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
				<label for="wc-%1$s-payment-token-%2$s">%3$s</label>
			</li>',
				esc_attr( $this->id ),
				esc_attr( $token->get_id() ),
				esc_html( $token->get_display_name() ),
				checked( $token->is_default(), true, false ),
				esc_attr( $token->get_token() )
			);

			return apply_filters(
				'woocommerce_payment_gateway_get_saved_payment_method_option_html',
				$html,
				$token,
				$this
			);
		}

		/**
		 * Init payment gateway setup fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'title'              => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'SecurePay',
					'desc_tip'    => true,
				),
				'description'        => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Checkout using Credit Card by SecurePay',
				),
				'capture'            => array(
					'title'       => __( 'Capture', 'woocommerce' ),
					'label'       => __( 'Capture charge immediately', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __(
						'Whether or not to immediately capture the charge. When unchecked, the charge issues an authorization and will need to be captured later.',
						'woocommerce'
					),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'allowed_card_types' => array(
					'title'   => 'Allowed Card Types',
					'context' => 'normal',
					'css'     => 'height: 6em',
					'type'    => 'multiselect',
					'default' => array(
						'visa',
						'mastercard',
					),
					'class'   => 'wc-enhanced-select',
					'options' => array(
						'visa'       => 'Visa',
						'mastercard' => 'MasterCard',
						'amex'       => 'American Express',
						'diners'     => 'Diners Club',
					),
				),
				'account'            => array(
					'title'       => 'Account',
					'type'        => 'title',
					'description' => '',
				),
				'store_id'           => array(
					'title'       => 'Store Unique Identifier',
					'type'        => 'text',
					'description' => "In case multiple stores using the same merchant account, this identifier prevent customer id and order id collision.\nWARNING: This field should be set only once. Changing this makes any previous transaction inaccessible.",
					'default'     => 'store_uid',
				),
				'merchant_code'      => array(
					'title' => 'Merchant Code',
					'type'  => 'text',
				),
				'client_id'          => array(
					'title' => 'Client ID',
					'type'  => 'text',
				),
				'client_secret'      => array(
					'title' => 'Client Secret',
					'type'  => 'password',
				),
				'testing'            => array(
					'title'       => 'Testing',
					'type'        => 'title',
					'description' => '',
				),
				'testmode'           => array(
					'title'       => 'Test Mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'debug'              => array(
					'title'       => 'Debug',
					'label'       => 'Enable Debug Logging',
					'type'        => 'checkbox',
					'description' => 'You can view the log in <code>WooCommerce > Status > Logs</code>, log name <code>wc-securepay-<i>[Y-m-d]</i>-<i>[hashcode]</i>.log</code>. The log file can be found in <code><i>&lt;wordpress root&gt;</i>/wp-content/uploads/wc-logs/</code> folder with the same name.',
					'default'     => 'yes',
				),
			);
		}

		/**
		 * Add capture and cancel payment buttons for authorized orders in admin
		 *
		 * @param WC_Order $order Woocommerce Order.
		 */
		public function action_woocommerce_order_item_add_action_buttons( $order ) {
			$payment_status = $order->get_meta( self::PAYMENT_STATUS );
			if ( self::PAYMENT_STATUS_AUTHORIZED === $payment_status ) {
				include __DIR__ . '/web/templates/pre-auth-action-buttons.phtml';
			}
		}

		/**
		 * Handle admin order form submission to determine capture or cancel action
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post.
		 * @param bool    $update  Update.
		 */
		public function handle_pre_auth_payment_submit( $post_id, $post, $update ) {
			$slug = 'shop_order';
			if ( is_admin() ) {
				// If this isn't a 'woocommerce order' post, don't update it.
				if ( $slug !== $post->post_type ) {
					return;
				}
				/**
				 * Woocommerce Order
				 *
				 * @var WC_Order $order
				 */
				$order = wc_get_order( $post_id );

				if ( isset( $_POST[ self::SECUREPAYAPI_NONCE ], $_POST['authorize_action'] ) && wp_verify_nonce(
					sanitize_key( $_POST[ self::SECUREPAYAPI_NONCE ] ),
					self::SECUREPAYAPI_NONCE
				)
				) {
					$authorize_action = sanitize_text_field( wp_unslash( $_POST['authorize_action'] ) );
					$secure_pay       = new Secure_Pay( $this->store_id );
					$init_result      = $secure_pay->init_with_authentication(
						$this->client_id,
						$this->client_secret,
						$this->merchant_code,
						array( Secure_Pay::SCOPE_PAYMENT_WRITE ),
						! $this->test_mode
					);
					if ( false === $init_result['error'] ) {
						if ( 'capture' === $authorize_action ) {
							$capture_result = $secure_pay->capture_pre_auth_payment(
								$order->get_customer_ip_address(),
								$order->get_id(),
								$order->get_total() * 100
							);
							if ( ! $capture_result['error'] ) {
								$order->payment_complete();
								$order->add_meta_data( self::PAYMENT_STATUS, self::PAYMENT_STATUS_CAPTURED, true );
								$this->add_order_payment_log( $order, self::PAYMENT_STATUS_CAPTURED, $capture_result );
								$order->save();
								$this->debug_log( 'Capture payment successfully' );
							} else {
								$order->add_order_note(
									sprintf(
										'Payment Failed: %s - %s',
										$capture_result['errorCode'],
										$capture_result['errorDetail']
									)
								);
								$order->save();
								$this->debug_log( 'Error capturing result', $capture_result );
							}
						} else {
							$cancel_result = $secure_pay->cancel_pre_auth_payment(
								$order->get_customer_ip_address(),
								$order->get_id()
							);
							if ( ! $cancel_result['error'] ) {
								$order->set_status( 'cancelled' );
								$order->add_meta_data( self::PAYMENT_STATUS, self::PAYMENT_STATUS_CANCELLED, true );
								$this->add_order_payment_log( $order, self::PAYMENT_STATUS_CANCELLED, $cancel_result );
								$order->save();
								$this->debug_log( 'Cancel payment successfully' );
							} else {
								$order->add_order_note(
									sprintf(
										'Payment Failed: %s - %s',
										$cancel_result['errorCode'],
										$cancel_result['errorDetail']
									)
								);
								$order->save();
								$this->debug_log( 'Error cancel result', $cancel_result );
							}
						}
					} else {
						$this->debug_log( 'Error initializing Capture/Cancel action', $init_result );
					}
				} else {
					$this->debug_log( 'Error parsing request', $_POST );
				}
			}
		}
	}

	/**
	 * Add the Gateway to WooCommerce
	 *
	 * @param mixed $methods Method.
	 *
	 * @return array
	 */
	function woocommerce_add_secure_pay( $methods ) {
		$methods[] = 'WC_Secure_Pay';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_secure_pay' );
}
