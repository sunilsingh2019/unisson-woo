<?php
/**
 * Base compatibility class
 *
 * @author  YITH
 * @package YITH\Deposits\Classes\Compatibilities
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'YITH_WCDP_Compatibility' ) ) {
	/**
	 * Class that offers basic features for compatibility with other plugins
	 *
	 * @since 1.0.0
	 */
	class YITH_WCDP_Compatibility {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WCDP_Compatibility
		 */
		protected static $instance;

		/**
		 * Constructor method
		 * Loads compatibility classes when related plugin is active.
		 */
		public function __construct() {

			if ( defined( 'YITH_YWPI_INIT' ) ) {
				require_once 'class-yith-wcdp-yith-pdf-invoice-compatibility.php';
				YITH_WCDP_YITH_PDF_Invoice_Compatibility::get_instance();
			}

			if ( defined( 'YITH_YWDPD_PREMIUM' ) ) {
				require_once 'class-yith-wcdp-yith-dynamic-pricing-and-discounts-compatibility.php';
				YITH_WCDP_YITH_Dynamic_Pricing_And_Discounts_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WCEVTI_INIT' ) ) {
				require_once 'class-yith-wcdp-yith-event-tickets-compatibility.php';
				YITH_WCDP_YITH_Event_Tickets_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WCPO_INIT' ) ) {
				require_once 'class-yith-wcdp-yith-pre-order-compatibility.php';
				YITH_WCDP_YITH_Pre_Order_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WCP_PREMIUM' ) ) {
				require_once 'class-yith-wcdp-yith-composite-products-compatibility.php';
				YITH_WCDP_YITH_Composite_Products_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WAPO_PREMIUM' ) ) {
				require_once 'class-yith-wcdp-yith-advanced-product-options-compatibility.php';
				YITH_WCDP_YITH_Advanced_Product_Options_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WPV_PREMIUM' ) ) {
				require_once 'class-yith-wcdp-yith-multi-vendor-compatibility.php';
				YITH_WCDP_YITH_Multi_Vendor_Compatibility::get_instance();
			}

			if ( defined( 'YITH_YWGC_INIT' ) ) {
				require_once 'class-yith-wcdp-yith-gift-cards-compatibility.php';
				YITH_WCDP_YITH_Gift_Cards_Compatibility::get_instance();
			}

			if ( defined( 'YITH_WCPB_INIT' ) && version_compare( YITH_WCPB_VERSION, '1.3.0', '>=' ) ) {
				require_once 'class-yith-wcdp-yith-products-bundle-compatibility.php';
				YITH_WCDP_YITH_Products_Bundle_Compatibility::get_instance();
			}
		}

		/**
		 * Get single instance of the class
		 *
		 * @return YITH_WCDP_Compatibility Snique access
		 * @since  1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}

if ( ! function_exists( 'YITH_WCDP_Compatibility' ) ) {
	/**
	 * Single access to YITH_WCDP_Compatibility unique instance.
	 *
	 * @return YITH_WCDP_Compatibility
	 */
	function YITH_WCDP_Compatibility() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		return YITH_WCDP_Compatibility::get_instance();
	}
}
