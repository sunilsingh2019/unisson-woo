<?php 

function email_order_custom_field($item_id, $item, $order, $plain_text) {
$tour_start_date = get_field('tour_start_date', $item->get_product_id());
$tour_end_date = get_field('tour_end_date', $item->get_product_id());

echo '<br><small><strong>Activity Costs</strong></small><br>';
echo '<small><strong>Support Costs(Not included online payment)</strong></small><br>';


echo '<small>Date: ' . $tour_start_date .' - '. $tour_end_date .'</small><br>';

// Get the product categories for this item
$terms = wp_get_post_terms( $item->get_product_id(), 'location' );

$parent_categories = $child_categories = $parent_categories_ids = $child_categories_ids = array();

foreach( $terms as $category ) {
    if( $category->parent == 0 ) {
        $parent_categories[] = $category->name;
        $parent_categories_ids[] = $category->term_id;
    }else{
        $child_categories[] = $category->name;
        $child_categories_ids[] = $category->term_id;
    }
}

echo '<small>Location: ' .implode(', ', $parent_categories).' - '.implode(', ', $child_categories).'</small>';

        
}   
add_action("woocommerce_order_item_meta_end", "email_order_custom_field", 1, 4);


//add variation description to email notification

add_filter( 'woocommerce_order_item_name', 'display_product_title_as_link', 10, 2 );
	function display_product_title_as_link( $item_name, $item ) {

		$_product = get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		
		$link = get_permalink( $_product->id );

		$_var_description ='';

		if ( $item['variation_id'] ) {
			$_var_description = $_product->get_variation_description();
		}

		return '<a href="'. $link .'"  rel="nofollow">'. $item_name .'</a><br>'. '<small>' . $_var_description . '</small>' ;
	}