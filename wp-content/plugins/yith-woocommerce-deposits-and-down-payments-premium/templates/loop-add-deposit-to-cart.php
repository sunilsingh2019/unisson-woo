<?php
/**
 * Add deposit to cart (loop product)
 *
 * @author YITH
 * @package YITH\Deposits\Templates
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly

global $product;
?>

<div class="yith-wcdp">
	<div class="yith-wcdp-loop-add-to-cart-fields" >
		<a href="<?php echo esc_url( $product_url ); ?>" class="button add-deposit-to-cart-button" ><?php echo esc_html( apply_filters( 'yith_wcdp_pay_deposit_label', __( 'Pay Deposit', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?></a>
	</div>
</div>
