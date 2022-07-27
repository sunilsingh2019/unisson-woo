<?php
add_action( 'init', 'custom_taxonomy_location' );
function custom_taxonomy_location()  {
$labels = array(
    'name'                       => 'Locations',
    'singular_name'              => 'Location',
    'menu_name'                  => 'Location',
    'all_items'                  => 'All Locations',
    'parent_item'                => 'Parent Location',
    'parent_item_colon'          => 'Parent Location:',
    'new_item_name'              => 'New Location Name',
    'add_new_item'               => 'Add New Location',
    'edit_item'                  => 'Edit Location',
    'update_item'                => 'Update Location',
    'separate_items_with_commas' => 'Separate Location with commas',
    'search_items'               => 'Search Location',
    'add_or_remove_items'        => 'Add or remove Location',
    'choose_from_most_used'      => 'Choose from the most used Location',
);
$args = array(
    'labels'                     => $labels,
    'hierarchical'               => true,
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => false,
);
register_taxonomy( 'location', 'product', $args );
register_taxonomy_for_object_type( 'location', 'product' );
}