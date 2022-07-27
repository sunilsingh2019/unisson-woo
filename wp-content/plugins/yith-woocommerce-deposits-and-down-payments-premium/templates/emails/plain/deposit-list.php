<?php
/**
 * Deposit list (plain)
 *
 * @author YITH
 * @package YITH\Deposits\Templates\Emails
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

$items     = $parent_order->get_items( 'line_item' );
$suborders = isset( $suborder ) ? (array) $suborder : YITH_WCDP_Suborders()->get_suborder( $parent_order->get_id() );

if ( ! empty( $suborders ) ) :
	foreach ( $suborders as $suborder ) :
		if ( ! $suborder instanceof WC_Order ) {
			$suborder = wc_get_order( $suborder );
		}

		if ( ! $suborder || $suborder->has_status( array( 'completed', 'processing' ) ) ) {
			continue;
		}

		$suborder_items = $suborder->get_items( 'line_item' );
		$suborder_names = array();

		foreach ( $suborder_items as $suborder_item ) {
			$suborder_names[] = $suborder_item['name'];
		}

		echo esc_html( implode( ' | ', $suborder_names ) ) . ' - ' . esc_html( sprintf( get_woocommerce_price_format(), '', $suborder->get_total() ) ) . "\n";
		echo esc_html__( 'Payment url:', 'yith-woocommerce-deposits-and-down-payments' ) . ' ' . esc_url( $suborder->get_checkout_payment_url() ) . "\n\n";
	endforeach;
endif;

