<?php
function aipm_placeholder_search_form($html) {

    $search_input_text = get_option('search-input-text');
    $search_button_text = get_option('search-button-text'); 


    //setting default value is its null
    if($search_input_text == NULL) {
        $search_input_text = "Search";
    }

    if($search_button_text == NULL) {
        $search_button_text = "Search";
    }


    $form = '<form role="search" method="get" action="' . home_url( '/' ) . '">
    <input type="text" value="' . get_search_query() . '" name="s" id="s"  placeholder="Search here...">
    <input type="hidden" name="post_type" value="product" /> 
    <button type="submit">
    <img src="' . get_template_directory_uri() . '/./images/icons/icon-search-orange.svg"
            alt=""></button>
    </form>';
    

    return $form;
}

add_filter('get_search_form','aipm_placeholder_search_form');
?>