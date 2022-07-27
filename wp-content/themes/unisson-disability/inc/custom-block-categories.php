<?php
function aipm_block_categories( $categories ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'custom_modules',
                'title' => __('Custom Modules','custom_modules'),
            ),
            array(
                'slug' => 'overview_modules',
                'title' => __('Overview Page Modules','overview_modules'),
            ),
            array(
                'slug' => 'general_modules',
                'title' => __('General Page Modules','general_modules'),
            ),
        )
    );
}
add_filter('block_categories', 'aipm_block_categories', 2, 1 );
