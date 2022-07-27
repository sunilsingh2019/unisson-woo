<?php

add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment', 10, 1 );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
	global $woocommerce;
	ob_start(); ?>
<a class="header-cart-btn cart-customlocation et-cart-info" href="<?php echo wc_get_cart_url(); ?>"
    title="<?php _e( 'View your shopping cart' ); ?>">
    <div class="icon"><img src="<?php echo get_template_directory_uri();?>/./images/icons/icon-cart.svg" alt=""></div>
    <div class="num">
        <?php echo sprintf ( _n( '%d', '%d', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() ); ?>
    </div>
</a>

<?php $fragments['a.cart-customlocation'] = ob_get_clean();
	return $fragments;
}
