<?php
/**
 * SecurePay api wrapper class for php
 *
 * @package FgcSecurepayApi/sdk
 */

/**
 * Every wrapper functions return an array after execution completed, which is structured bellow:
 *  - Function executed successfully:
 *      array(
 *          'error' => false,
 *          'other_fields' => 'number of fields and value vary depends on each function'
 *      )
 *  - If any error occurs:
 *      array(
 *          'error' => true,
 *          'error_code' => 'string code of error',
 *          'error_detail' => 'error in detail'
 *      )
 *      Common errors:
 *      +-------------------------+--------------------------------------------------+
 *      | Error code              | Cause                                            |
 *      +-------------------------+--------------------------------------------------+
 *      | curl_error              | An error happens in execution of cURL functions  |
 *      +-------------------------+--------------------------------------------------+
 *      | bearer_token_not_set    | Possibility of initialize functions never called |
 *      | merchant_code_not_set   |                                                  |
 *      +-------------------------+--------------------------------------------------+
 *      | invalid_server_response | Response from server can't be parsed properly    |
 *      +-------------------------+--------------------------------------------------+
 *      If any error is returned form SecurePay API, it will be return by respective functions.
 */
class Secure_Pay {
	const URL_SANDBOX_SCRIPT = 'https://payments-stest.npe.auspost.zone/v3/ui/client/securepay-ui.js';
	const URL_LIVE_SCRIPT    = 'https://payments.auspost.net.au/v3/ui/client/securepay-ui.min.js';

	const URL_SANDBOX_API = 'https://payments-stest.npe.auspost.zone/v2/';
	const URL_LIVE_API    = 'https://payments.auspost.net.au/v2/';

	const SCOPE_PAYMENT_READ              = 'https://api.payments.auspost.com.au/payhive/payments/read';
	const SCOPE_PAYMENT_WRITE             = 'https://api.payments.auspost.com.au/payhive/payments/write';
	const SCOPE_PAYMENT_INSTRUMENTS_READ  = 'https://api.payments.auspost.com.au/payhive/payment-instruments/read';
	const SCOPE_PAYMENT_INSTRUMENTS_WRITE = 'https://api.payments.auspost.com.au/payhive/payment-instruments/write';

	const ERROR_CURL                     = 'curl_error';
	const ERROR_BEARER_TOKEN_NOT_SET     = 'bearer_token_not_set';
	const ERROR_MERCHANT_CODE_NOT_SET    = 'merchant_code_not_set';
	const ERROR_INVALID_RESPONSE         = 'invalid_server_response';
	const ERROR_INVALID_RESPONSE_DETAILS = 'Invalid response from gateway sever.';

	/**
	 * Bearer token
	 *
	 * @var $bearer_token string
	 */
	private $bearer_token;

	/**
	 * Api Url
	 *
	 * @var $api_url string
	 */
	private $api_url;

	/**
	 * Merchant code
	 *
	 * @var $merchant_code string
	 */
	private $merchant_code;

	/**
	 * Store ID
	 *
	 * @var $store_id string
	 */
	private $store_id;

	/**
	 * Secure_Pay constructor.
	 *
	 * @param string $store_id Store ID.
	 */
	public function __construct( $store_id ) {
		$this->store_id = substr( $store_id, 0, 24 );
	}

	/**
	 * Init SecurePay object with pre-authorized information
	 *
	 * @param string $bearer_token  Authenticated bearer token.
	 * @param string $merchant_code Account's merchant code.
	 * @param bool   $is_live       Is used for live transactions.
	 *
	 * @return array
	 */
	public function init_with_saved_token( $bearer_token, $merchant_code, $is_live ) {
		$this->bearer_token  = $bearer_token;
		$this->merchant_code = $merchant_code;
		$this->api_url       = $is_live ? $this::URL_LIVE_API : $this::URL_SANDBOX_API;

		return array( 'error' => false );
	}

	/**
	 * Init SecurePay object using api provided by payment gateway, request is sent to gateway to retrieve
	 * authentication token https://auspost.com.au/payments/docs/securepay/#authentication
	 *
	 * @param string $client_id     Client ID.
	 * @param string $client_secret Account's client secret.
	 * @param string $merchant_code Account's merchant code.
	 * @param array  $scope         Scope.
	 * @param bool   $is_live       Is used for live transactions.
	 *
	 * @return array
	 */
	public function init_with_authentication( $client_id, $client_secret, $merchant_code, $scope, $is_live ) {
		$this->merchant_code = $merchant_code;
		$auth_url            = $is_live ? 'https://welcome.api2.auspost.com.au/oauth/token'
			: 'https://welcome.api2.sandbox.auspost.com.au/oauth/token';
		$this->api_url       = $is_live ? self::URL_LIVE_API : self::URL_SANDBOX_API;

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
			'Content-Type'  => 'application/x-www-form-urlencoded',
		);

		$audience = 'https://api.payments.auspost.com.au';

		$result = wp_remote_post(
			$auth_url,
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => 'grant_type=client_credentials&audience=' . rawurlencode( $audience ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );
		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['error'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => $result_array['error'],
				'errorDetail' => $result_array['error_description'],
				'rawResponse' => $result_array,
			);
		}
		$this->bearer_token = $result_array['access_token'];

		return array( 'error' => false );
	}

	/**
	 * Create payment
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-create-payment
	 *
	 * @param string      $payment_token   Payment Token.
	 * @param string      $ip_address      IP Address.
	 * @param int         $amount          Amount.
	 * @param string|null $order_id        Order Id.
	 * @param string|null $idempotency_key Idemptotency Key.
	 *
	 * @return array
	 */
	public function create_payment( $payment_token, $ip_address, $amount, $order_id = null, $idempotency_key = null ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}
		$post_fields = array(
			'amount'       => $amount,
			'merchantCode' => $this->merchant_code,
			'token'        => $payment_token,
			'ip'           => $ip_address,
		);
		if ( isset( $order_id ) ) {
			$post_fields['orderId'] = $this->store_uniquify( $order_id );
		}
		$headers                 = array();
		$headers['Content-Type'] = 'application/json';
		if ( isset( $idempotency_key ) ) {
			$headers['Idempotency-Key'] = $this->store_uniquify( $idempotency_key );
		}
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$result                   = wp_remote_post(
			$this->api_url . 'payments',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );
		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}
		if ( ! isset( $result_array['status'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => $result_array,
				'rawResponse' => $result_array,
			);
		}
		if ( 'paid' !== $result_array['status'] ) {
			return array(
				'error'                  => true,
				'errorCode'              => 'payment_status_' . $result_array['status'],
				'errorDetail'            => $result_array['gatewayResponseCode'] . ' - ' .
					$result_array['gatewayResponseMessage'],
				'gatewayResponseCode'    => $result_array['gatewayResponseCode'],
				'gatewayResponseMessage' => $result_array['gatewayResponseMessage'],
				'rawResponse'            => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Make params unique to store code
	 *
	 * @param string $string Parameter.
	 *
	 * @return string
	 */
	private function store_uniquify( $string ) {
		if ( $this->is_raw_string( $string ) ) {
			return $this->store_id . '_' . $string;
		}

		return $string;
	}

	/**
	 * Check if string is uniquified.
	 *
	 * @param string $string String to check.
	 *
	 * @return bool
	 */
	private function is_raw_string( $string ) {
		return substr( $string, 0, strlen( $this->store_id ) ) !== $this->store_id;
	}

	/**
	 * Authorize payment
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-create-payment
	 *
	 * @param string      $payment_token Payment Token.
	 * @param string      $ip_address    IP address.
	 * @param int         $amount        Amount.
	 * @param string|null $order_id      Order ID.
	 *
	 * @return array
	 */
	public function create_pre_auth_payment( $payment_token, $ip_address, $amount, $order_id = null ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}
		$post_fields = array(
			'amount'       => $amount,
			'merchantCode' => $this->merchant_code,
			'token'        => $payment_token,
			'ip'           => $ip_address,
		);
		if ( isset( $order_id ) ) {
			$post_fields['orderId'] = $this->store_uniquify( $order_id );
		}

		$headers                  = array();
		$headers['Content-Type']  = 'application/json';
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;

		$result = wp_remote_post(
			$this->api_url . 'payments/preauths',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );
		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}
		if ( ! isset( $result_array['status'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => $result_array,
				'rawResponse' => $result_array,
			);
		}
		if ( 'paid' !== $result_array['status'] ) {
			return array(
				'error'                  => true,
				'errorCode'              => 'payment_status_' . $result_array['status'],
				'errorDetail'            => $result_array['gatewayResponseCode'] . ' - ' .
					$result_array['gatewayResponseMessage'],
				'gatewayResponseCode'    => $result_array['gatewayResponseCode'],
				'gatewayResponseMessage' => $result_array['gatewayResponseMessage'],
				'rawResponse'            => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Cancel payment authorized previously
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-create-payment
	 *
	 * @param string $ip_address IP Address.
	 * @param string $order_id   Order Id.
	 *
	 * @return array
	 */
	public function cancel_pre_auth_payment( $ip_address, $order_id ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}
		$post_fields = array(
			'merchantCode' => $this->merchant_code,
			'ip'           => $ip_address,
		);

		$headers                           = array();
		$headers['Content-Type']           = 'application/json';
		$headers['Authorization: Bearer '] = $this->bearer_token;
		$result                            = wp_remote_post(
			$this->api_url . 'payments/preauths/' . $this->store_uniquify( $order_id ) . '/cancel',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Capture payment authorized previously
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-create-capture-payment
	 *
	 * @param string $ip_address IP Address.
	 * @param string $order_id   Order ID.
	 * @param int    $amount     Amount.
	 *
	 * @return array
	 */
	public function capture_pre_auth_payment( $ip_address, $order_id, $amount ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}

		$post_fields = array(
			'merchantCode' => $this->merchant_code,
			'amount'       => $amount,
			'ip'           => $ip_address,
		);

		$headers                  = array();
		$headers['Content-Type']  = 'application/json';
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;

		$result = wp_remote_post(
			$this->api_url . 'payments/preauths/' . $this->store_uniquify( $order_id ) . '/capture',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}
		if ( ! isset( $result_array['status'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => $result_array,
				'rawResponse' => $result_array,
			);
		}
		if ( 'paid' !== $result_array['status'] ) {
			return array(
				'error'                  => true,
				'errorCode'              => 'payment_status_' . $result_array['status'],
				'errorDetail'            => $result_array['gatewayResponseCode'] . ' - ' .
					$result_array['gatewayResponseMessage'],
				'gatewayResponseCode'    => $result_array['gatewayResponseCode'],
				'gatewayResponseMessage' => $result_array['gatewayResponseMessage'],
				'rawResponse'            => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Refund captured payment
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-refund-payment
	 *
	 * @param string      $ip_address      IP Address.
	 * @param int         $amount          Amount.
	 * @param string      $order_id        Order ID.
	 * @param string|null $idempotency_key Idem Key.
	 *
	 * @return array
	 */
	public function refund_payment( $ip_address, $amount, $order_id, $idempotency_key = null ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}
		$post_fields = array(
			'amount'       => $amount,
			'merchantCode' => $this->merchant_code,
			'ip'           => $ip_address,
		);

		$headers                 = array();
		$headers['Content-Type'] = 'application/json';
		if ( isset( $idempotency_key ) ) {
			$headers['Idempotency-Key'] = $this->store_uniquify( $idempotency_key );
		}
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$result                   = wp_remote_post(
			$this->api_url . 'orders/' . $this->store_uniquify( $order_id ) . '/refunds',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}
		if ( ! isset( $result_array['status'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => $result_array,
				'rawResponse' => $result_array,
			);
		}
		if ( 'paid' !== $result_array['status'] ) {
			return array(
				'error'                  => true,
				'errorCode'              => 'payment_status_' . $result_array['status'],
				'errorDetail'            => $result_array['gatewayResponseCode'] . ' - ' .
					$result_array['gatewayResponseMessage'],
				'gatewayResponseCode'    => $result_array['gatewayResponseCode'],
				'gatewayResponseMessage' => $result_array['gatewayResponseMessage'],
				'rawResponse'            => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Create payment instrument for specified user account
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-create-payment-instrument
	 *
	 * @param string $customer_code Customer Code.
	 * @param string $payment_token Payment Token.
	 * @param string $ip_address    IP Address.
	 *
	 * @return array
	 */
	public function create_payment_instrument( $customer_code, $payment_token, $ip_address ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}

		$headers                  = array();
		$headers['Content-Type']  = 'application/json';
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$headers['token']         = $payment_token;
		$headers['ip']            = $ip_address;
		$result                   = wp_remote_post(
			$this->api_url . 'customers/' . $this->store_uniquify( $customer_code ) . '/payment-instruments/token',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Create payment using customer's saved payment instrument
	 *
	 * @param string      $customer_code   Customer Code.
	 * @param string      $payment_token   Payment Token.
	 * @param string      $ip_address      IP Address.
	 * @param int         $amount          Amount.
	 * @param string|null $order_id        Order ID.
	 * @param string|null $idempotency_key Idem key.
	 *
	 * @return array|mixed
	 */
	public function create_payment_from_instrument(
		$customer_code,
		$payment_token,
		$ip_address,
		$amount,
		$order_id = null,
		$idempotency_key = null
	) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}

		$post_fields = array(
			'amount'       => $amount,
			'merchantCode' => $this->merchant_code,
			'token'        => $payment_token,
			'ip'           => $ip_address,
		);
		if ( isset( $order_id ) ) {
			$post_fields['orderId'] = $this->store_uniquify( $order_id );
		}

		$headers                 = array();
		$headers['Content-Type'] = 'application/json';
		if ( isset( $idempotency_key ) ) {
			$headers['Idempotency-Key'] = $this->store_uniquify( $idempotency_key );
		}
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$result                   = wp_remote_post(
			$this->api_url . 'customers/' . $this->store_uniquify( $customer_code ) . '/payments',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'body'        => wp_json_encode( $post_fields ),
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );
		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}
		if ( ! isset( $result_array['status'] ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => $result_array,
				'rawResponse' => $result_array,
			);
		}
		if ( 'paid' !== $result_array['status'] ) {
			return array(
				'error'                  => true,
				'errorCode'              => 'payment_status_' . $result_array['status'],
				'errorDetail'            => $result_array['gatewayResponseCode'] . ' - ' .
					$result_array['gatewayResponseMessage'],
				'gatewayResponseCode'    => $result_array['gatewayResponseCode'],
				'gatewayResponseMessage' => $result_array['gatewayResponseMessage'],
				'rawResponse'            => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Retrieve list of payment instruments of an user account
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-payment-instruments
	 *
	 * @param string $customer_code Customer Code.
	 * @param string $ip_address    IP Address.
	 *
	 * @return array
	 */
	public function list_payment_instrument( $customer_code, $ip_address ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}

		$headers                  = array();
		$headers['Content-Type']  = 'application/json';
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$headers['ip']            = $ip_address;
		$result                   = wp_remote_post(
			$this->api_url . 'customers/' . $this->store_uniquify( $customer_code ) . '/payment-instruments',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Delete payment instrument of an user account
	 * https://auspost.com.au/payments/docs/securepay/#card-payments-rest-api-delete-payment-instrument
	 *
	 * @param string $customer_code Customer Code.
	 * @param string $payment_token Payment Token.
	 * @param string $ip_address    IP Address.
	 *
	 * @return array
	 */
	public function delete_payment_instrument( $customer_code, $payment_token, $ip_address ) {
		if ( ! isset( $this->merchant_code ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_MERCHANT_CODE_NOT_SET,
				'errorDetail' => 'Merchant code is not set. Have you called init function?',
			);
		} elseif ( ! isset( $this->bearer_token ) ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_BEARER_TOKEN_NOT_SET,
				'errorDetail' => 'Bearer token is not set. Have you called init function?',
			);
		}

		$headers                  = array();
		$headers['Content-Type']  = 'application/json';
		$headers['Authorization'] = 'Bearer ' . $this->bearer_token;
		$headers['token']         = $payment_token;
		$headers['ip']            = $ip_address;
		$result                   = wp_remote_post(
			$this->api_url . 'customers/' . $this->store_uniquify( $customer_code ) . '/payment-instruments/token',
			array(
				'method'      => 'POST',
				'headers'     => $headers,
				'httpversion' => '1.0',
				'sslverify'   => false,
			)
		);

		/**
		 * An error occurred.
		 *
		 * @var WP_Error $result
		 */

		if ( $result instanceof WP_Error ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_CURL,
				'errorDetail' => $result->get_error_message(),
			);
		}

		$result_array = json_decode( $result['body'], true );

		if ( null === $result_array ) {
			return array(
				'error'       => true,
				'errorCode'   => self::ERROR_INVALID_RESPONSE,
				'errorDetail' => self::ERROR_INVALID_RESPONSE_DETAILS,
			);
		}
		if ( isset( $result_array['errors'] ) ) {
			$error         = $result_array['errors'][0];
			$error_message = $error['detail'];
			if ( isset( $error['source']['pointer'] ) ) {
				$error_message = $error['source']['pointer'] . ' ' . $error_message;
			}

			return array(
				'error'       => true,
				'errorCode'   => $error['code'],
				'errorDetail' => $error_message,
				'rawResponse' => $result_array,
			);
		}

		$result_array['error'] = false;

		return $result_array;
	}

	/**
	 * Get bearer token
	 *
	 * @return string
	 */
	public function get_bearer_token() {
		return $this->bearer_token;
	}

	/**
	 * Get merchant code
	 *
	 * @return string
	 */
	public function get_merchant_code() {
		return $this->merchant_code;
	}
}
