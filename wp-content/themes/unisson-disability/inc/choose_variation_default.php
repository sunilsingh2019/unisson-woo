<?php



add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'filter_dropdown_option_html', 12, 2 );
function filter_dropdown_option_html( $html, $args ) {
	$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' );
	$show_option_none_html = '<option value="">'.esc_html( $show_option_none_text ).'</option>';
	$html = str_replace($show_option_none_html, '', $html);
	return $html;
}
add_filter('woocommerce_reset_variations_link', '__return_empty_string');
add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );