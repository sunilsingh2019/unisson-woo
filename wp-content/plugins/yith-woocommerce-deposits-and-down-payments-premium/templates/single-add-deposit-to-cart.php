<?php
/**
 * Add deposit to cart (single product)
 *
 * @author YITH
 * @package YITH\Deposits\Templates
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCDP' ) ) {
	exit;
} // Exit if accessed directly
?>

<div id="yith-wcdp-add-deposit-to-cart" class="yith-wcdp">
	<?php do_action( 'yith_wcdp_before_add_deposit_to_cart', $product, isset( $variation ) ? $variation : false ); ?>
	<div class="yith-wcdp-single-add-to-cart-fields" data-deposit-type="<?php echo isset( $deposit_type ) ? esc_attr( $deposit_type ) : ''; ?>" data-deposit-amount="<?php echo isset( $deposit_amount ) ? esc_attr( $deposit_amount ) : ''; ?>" data-deposit-rate="<?php echo isset( $deposit_rate ) ? esc_attr( $deposit_rate ) : ''; ?>" >
		<?php if ( ! $deposit_forced ) : ?>
			<label>
				<input type="radio" name="payment_type" value="full" <?php checked( ! $default_deposit ); ?> /> <?php echo esc_html( apply_filters( 'yith_wcdp_pay_full_amount_label', __( 'Pay full amount', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?>
				<span class="full-price">( <?php echo isset( $variation ) ? $variation->get_price_html() : $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> )</span>
			</label><br>
			<label>
				<input type="radio" name="payment_type" value="deposit" <?php checked( $default_deposit ); ?> /> <?php echo esc_html( apply_filters( 'yith_wcdp_pay_deposit_label', __( 'Pay deposit', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?>
				<span class="deposit-price">( <?php echo apply_filters( 'yith_wcdp_single_deposit_price', wc_price( $deposit_value ), $deposit_value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> )</span>
			</label><br>

		<?php else : ?>
			<?php
			// translators: 1. Deposit value.
			echo wp_kses_post( apply_filters( 'yith_wcdp_deposit_only_message', sprintf( __( 'This action will let you pay a deposit of <span class="deposit-price">%s</span> for this product', 'yith-woocommerce-deposits-and-down-payments' ), wc_price( $deposit_value ) ), $deposit_value ) );
			?>
		<?php endif; ?>
	</div>

	<?php if ( apply_filters( 'yith_wcdp_virtual_on_deposit', true, null ) && $needs_shipping && $show_shipping_form ) : ?>
		<div class="yith-wcdp-deposit-shipping <?php echo ( $deposit_forced ) ? 'forced' : ''; ?>">
			<small><?php echo esc_html( apply_filters( 'yith_wcdp_deposit_needs_shipping_text', __( 'Please, select a shipping method for delivery of your product when balance is paid', 'yith-woocommerce-deposits-and-down-payments' ) ) ); ?></small>
			<div class="yith-wcdp-shipping-form">
				<table><?php wc_cart_totals_shipping_html(); ?></table>
				<?php yith_wcdp_get_template( 'shipping-calculator.php' ); ?>
			</div>
			<div class="clear"></div>
		</div>
	<?php endif; ?>
	<?php do_action( 'yith_wcdp_after_add_deposit_to_cart', $product, isset( $variation ) ? $variation : false ); ?>
</div>
