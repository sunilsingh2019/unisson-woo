<?php 
//creating custom blocks
add_action('acf/init', 'aipm_acf_init');

function aipm_acf_init() {
	// check function exists
	if( function_exists('acf_register_block') ) {

		acf_register_block(array(
			'name'				=> 'hero-slider-module',
			'title'				=> __('Hero Slider Module'),
			'description'		=> __('Hero Slider Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'hero image', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-slider-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

		acf_register_block(array(
			'name'				=> 'shop-module',
			'title'				=> __('Shop Module'),
			'description'		=> __('Shop Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'hero image', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));
		acf_register_block(array(
			'name'				=> 'shop-module-ajax',
			'title'				=> __('Shop Module Ajax'),
			'description'		=> __('Shop Module Ajax'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'hero image', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

		acf_register_block(array(
			'name'				=> 'cta-module',
			'title'				=> __('CTA Module'),
			'description'		=> __('CTA Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'hero image', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

		acf_register_block(array(
			'name'				=> 'breadcrumbs-module',
			'title'				=> __('breadcrumbs Module'),
			'description'		=> __('Breadcrumbs Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'breadcrumbs', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

		acf_register_block(array(
			'name'				=> 'register-module',
			'title'				=> __('Register Module'),
			'description'		=> __('Register Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'register', 'header', 'banner' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

		acf_register_block(array(
			'name'				=> 'free-text-module',
			'title'				=> __('Free Text Module'),
			'description'		=> __('Free Text Module'),
			'render_callback'	=> 'aipm_acf_module_template_block_render_callback',
			'icon'              => 'superhero',
			'keywords'          => array( 'free text', 'text', 'content' ),
			'mode'				=> 'edit',
			'category'			=> 'custom_modules',
			'example' => array(
				'attributes' => array(
					'mode' => 'preview', // Important!
					'data' => array(
						'image' => '<img src="' . get_template_directory_uri() . '/images/module-preview/hero-header-module.png' . '" style="width:100%; height:auto;">'
					),
				),
			),
		));

	

	}
}

function aipm_acf_module_template_block_render_callback($block, $content = '', $is_preview = false) {
	$slug = str_replace('acf/', '', $block['name']);
	if( file_exists( get_theme_file_path("/template-parts/block/content-{$slug}.php") ) ) {
		include( get_theme_file_path("/template-parts/block/content-{$slug}.php") );
	}

	if ( $is_preview && ! empty( $block['data'] ) ) {
		echo $block['data']['image'];
		return;
   } else {
		if ( $block ) :
			 $template = $block['render_template'];
			 $template = str_replace( '.php', '', $template );
			 get_template_part( '/' . $template );
		endif;
   }
}