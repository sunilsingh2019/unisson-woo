<?php
/* WooCommerce: The Code Below Removes Checkout Fields */
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
function custom_override_checkout_fields( $fields ) {

unset($fields['billing']['billing_company']);
unset($fields['billing']['billing_country']);
unset($fields['order']['order_comments']);


$fields['billing']['billing_first_name']['priority'] = 1;
$fields['billing']['billing_last_name']['priority'] = 2;
$fields['billing']['billing_email']['priority'] = 3;
$fields['billing']['billing_phone']['priority'] = 4;

$fields['billing']['billing_address_1']['priority'] = 5;
$fields['billing']['billing_address_2']['priority'] = 6;
$fields['billing']['billing_city']['priority'] = 7;
$fields['billing']['billing_state']['priority'] = 8;
$fields['billing']['billing_postcode']['priority'] = 9;


return $fields;
}

//reorder


add_filter( 'woocommerce_default_address_fields', 'custom_override_default_locale_fields' );
function custom_override_default_locale_fields( $fields ) {
    $fields['state']['priority'] = 9;
    $fields['address_1']['priority'] = 5;
    $fields['address_2']['priority'] = 6;
    return $fields;
}

add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

