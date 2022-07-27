<?php

// Display on cart & checkout pages
function filter_woocommerce_get_item_data( $item_data, $cart_item ) {   
    // Compare
    if ( $cart_item['data']->get_type() == 'variation' ) {
        // Get the variable product description
        $description = $cart_item['data']->get_variation_description();
    }       
        
    // Isset & NOT empty
    if ( isset ( $description ) && ! empty( $description ) ) {
        $item_data[] = array(
            'key'     => __( 'NOTE', 'woocommerce' ),
            'value'   => $description,
            'display' => $description,
        );
    }
    
    return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'filter_woocommerce_get_item_data', 10, 2 );