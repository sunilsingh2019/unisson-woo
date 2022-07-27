<?php
/**
 * Class Name: wp_bootstrap_navwalker
 * GitHub URI: https://github.com/twittem/wp-bootstrap-navwalker
 * Description: A custom WordPress nav walker class to implement the Bootstrap 3 navigation style in a custom theme using the WordPress built in menu manager.
 * Version: 2.0.4
 * Author: Edward McIntyre - @twittem
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
class wp_bootstrap_navwalker extends Walker_Nav_Menu {
	private $ParentItemClass 			= '';	
	private $curItem;
	private $heading 					= '';
	private $megamenu 					= '';
	private $menu_grid 					= '';
	private $menu_grid_item_count 		= 0;
	private $menu_grid_close			= '';
	private $tab_menu 					= '';
	private $tab_index 					= 0;
	public $menu_tab 					=  array();
	public $main_menu_tab_heading 		=  array();
	public $menu_main_tab_index 		= array();
	public $menu_content 				= array();
	private $is_tab_menu_print 			= '';
	private $is_tab_menu_print_close 	= '';
	private $last_tab_close 			= '';
	
	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$menuItemAttrubutes = $this->curItem->classes;
		//print_r($menuItemAttrubutes);	
		
		if($depth == 0 && $this->menu_grid == 'menu-grid'){
			$output .= "\n$indent<div class=\"dropdown-menu\"><div class=\"megamenu\"><div class=\"drop-holder\"><ul>\n";
		}		
		else if ( $depth == 0 && in_array("mega-md", $menuItemAttrubutes) ) {
			$output .= "\n$indent<div class=\"dropdown-menu\"><div class=\"megamenu\"><div class=\"drop-holder\">\n";
			$this->is_tab_menu_print_close = '';
		}
		else if ( $depth == 0 && in_array("has-mega-dropdown", $menuItemAttrubutes) ) {
			$output .= "\n$indent<div class=\"dropdown-menu\"><div class=\"megamenu\"><div class=\"five-col\">\n";
		}
		else if (in_array("heading", $menuItemAttrubutes)) {
			$output .= "\n$indent<ul class=\"header-link\">\n";	
		}
		elseif( !empty($this->is_tab_menu_print_close)){
			$output .= '';					
		}
		elseif( !empty($this->is_tab_menu_print)){
			$output .= "";				
		}	
		else {	
			if($depth > 0){
				$output .= "\n$indent<div  class=\"dropdown-menu sub-dropdown-menu\"><ul role=\"menu\">\n";
			} else{
				$output .= "\n$indent<div  class=\"dropdown-menu\"><ul role=\"menu\">\n";
			}		
			
		}
	}
	
	function end_lvl(&$output, $depth = 0, $args = array() ) {
		$menuItemAttrubutes = $this->curItem->classes;
		$indent = str_repeat( "\t", $depth );
				
		if ( $depth == 0 && !empty($this->menu_grid_close) ) {
			$output .= "\n$indent</ul></div></div></div></li>\n";
			$this->menu_grid = '';
			$this->menu_grid_close = '';
		}
		else if ( $depth == 0 && !empty($this->megamenu) ) {
			$output .= "\n$indent</div></div></div></li>\n";
			$this->megamenu = '';
		}	
		else if(!empty($this->heading)) {
			$output .= '</ul></div>' . "\n"; 
			$this->heading = '';  
		}
		else if(!empty($this->is_tab_menu_print_close)){
			$output .= "\n$indent</div></div></div></li>\n";
			$this->is_tab_menu_print_close = '';
		}
		else if( !empty($this->is_tab_menu_print)){
			$output .= "";						
		}	
		else if(!empty($this->last_tab_close)){
			$output .= "";
			$this->last_tab_close = '';
		}		
		else {
		 	$output .= '</ul></div>' . "\n"; 
		}
		
	}
	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $menu_content;
		global $menu_tab;
		global $menu_main_tab_index;
		global $main_menu_tab_heading;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		/**
		 * Dividers, Headers or Disabled
		 * =============================
		 * Determine whether the item is a Divider, Header, Disabled or regular
		 * menu item. To prevent errors we use the strcasecmp() function to so a
		 * comparison that is not case sensitive. The strcasecmp() function returns
		 * a 0 if the strings are equal.
		 */
		if ( strcasecmp( $item->attr_title, 'divider' ) == 0 && $depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="divider">';
		}
		else if ( strcasecmp( $item->title, 'divider') == 0 && $depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="divider">';
		}
		else if ( strcasecmp( $item->attr_title, 'dropdown-header') == 0 && $depth === 1 ) {
			$output .= $indent . '<li role="presentation" class="dropdown-header">' . esc_attr( $item->title );
		}
		else if ( strcasecmp($item->attr_title, 'disabled' ) == 0 ) {
			$output .= $indent . '<li role="presentation" class="disabled"><a href="#">' . esc_attr( $item->title ) . '</a>';
		}
		else {
			$class_names = $value = '';
			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			$entrada_class = apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args );

			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
			if ( $args->has_children ){
				$class_names .= ' dropdown child-dropdown';
			}
			/*if ( in_array( 'current-menu-item', $classes ) ){
				$class_names .= ' active';
			}*/
			if ( in_array( 'menu-grid', $classes ) ) {
				$this->menu_grid = 'menu-grid';
			}
			else if( in_array( 'mega-md', $classes ) ){
				$ParentItemClass = 'mega-md';
				$this->tab_menu = 'mega-md';
				$this->is_tab_menu_print = 'waiting';
				$this->is_tab_menu_print_close = 'Not Closed';	
			}
			else if ( in_array( 'has-mega-dropdown', $classes ) ) {
				$ParentItemClass = 'has-mega-dropdown';
				$this->megamenu = 'has-mega-dropdown';
			}
			else if ( in_array( 'heading', $classes ) ) {
				$ParentItemClass = 'heading';
			}
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
			$atts = array();
			$atts['target'] = ! empty( $item->target )	? $item->target	: '';
			$atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';
			// If item has_children add atts to a.
			
			if ( $args->has_children && !in_array( 'tab-heading', $classes )  ) {
				/*$atts['href']   		= '#';
				$atts['class']			= 'dropdown-toggle';*/				
				$atts['href'] 			= ! empty( $item->url ) ? $item->url : '';
				$atts['data-toggle']   	= 'dropdown';
				$atts['aria-haspopup']	= 'true';				
				$atts['class']          = 'dropdown-toggle entradaActiveLink';
			} 			
			else {
				$atts['href'] = ! empty( $item->url ) ? $item->url : '';
			}
			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );
			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}
			
			$this->curItem = $item; 
			if ( in_array( 'heading', $classes ) ){
				$this->heading = 'heading'; 
				$output .= $indent .'<div class="column"><strong class="title sub-link-opener">'.$item->title.'</strong>';
			} 
			elseif ( in_array( 'tab-heading', $classes ) ){
				$menu_item_icon_class = get_post_meta( $item->ID , 'menu-item-icon-class', true );			
				$icon_class = (!empty($menu_item_icon_class)) ? '<span class="'.$menu_item_icon_class.'"></span>' : '';	
				
				$menu_tab[$this->tab_index] = '<a class="title" href="#tab'.$item->ID.'" aria-controls="tab'.$item->ID.'" role="tab" data-toggle="tab">'.$icon_class.$item->title.'</a>';
				$all_txt = __('All', 'entrada');
				$main_menu_tab_heading[] = '<a '.$attributes.'>'.$icon_class.$all_txt.' '.$item->title.'</a>';
				
				$this->tab_index = $this->tab_index + 1;
				$menu_main_tab_index[] = $item->ID;
				
				// check if has no child
				$children = get_posts(
					array(	'post_type' => 'nav_menu_item',
							'nopaging' => true,
							'numberposts' => 1,
							'meta_key' => '_menu_item_menu_item_parent',
							'meta_value' => $item->ID
						)
					);
				if (empty($children)) {
					$menu_content[$this->tab_index][] = '';
				}
			}
			elseif ( in_array( 'tab-menu-item', $classes ) ){
				$menu_item_icon_class = get_post_meta( $item->ID , 'menu-item-icon-class', true );	
				$icon_class = (!empty($menu_item_icon_class)) ? '<span class="'.$menu_item_icon_class.'"></span>' : '';	
				$menu_content[$this->tab_index][] = '<li><a'. $attributes .'>'.$icon_class.$item->title.'</a></li>';
				
				if ( in_array( 'last-item', $classes ) ){
					// Tab menu print here.....
					if(count($menu_tab) > 0 ) {
						$tab_menu_tab_count = 0;
						$tab_active = '';
						$output .= '<ul class="nav nav-tabs nav-hover" role="tablist">';
						foreach($menu_tab as $mt){		
											
							$tab_active = ($tab_menu_tab_count == 0) ? 'class = "active"' : '';
							$output .= '<li role="presentation" '.$tab_active.'>'.$mt.'</li>';
							$tab_menu_tab_count++;
							}
						$output .= '</ul>';											
						}
					// Tab content start here ....	
					if(count($menu_content) > 0 ) {
						$tab_menu_item_count = 0;
						$r = '';
						$output .= '<div class="tab-content">';
						foreach($menu_content as $menu_tab_content){
							
							if($menu_tab_content){
									$r = ($tab_menu_item_count == 0) ? ' active' : '';
									$output .= '<div role="tabpanel" class="tab-pane'.$r.'" id="tab'.$menu_main_tab_index[$tab_menu_item_count].'">
														<ul class="header-link">';
									$output .= '<li>'.$main_menu_tab_heading[$tab_menu_item_count].'</li>';			
									foreach($menu_tab_content as $menu_tab_item_content){
										$output .= $menu_tab_item_content;		
									}
									$output .= '</ul></div>';					
								}
							$tab_menu_item_count++;	
						}
						$output .= '</div>';
						$this->is_tab_menu_print = '';
						$this->last_tab_close = 'sfas';
						$this->is_tab_menu_print_close = 'Not Closed';
					}

					/* Empty all values before going to another menu */
					$menu_tab = array();
					$menu_main_tab_index = array();
					$main_menu_tab_heading = array();
					$menu_content = array();
						
				}
			}
			else if (isset($item->classes) && in_array("menu-grid-item", $item->classes) && $depth == 1 ) {
				
				$this->menu_grid_item_count = $this->menu_grid_item_count + 1;
				if(!empty($item->description)){
					$term_description = $item->description;
				} else{
					$term_description = term_description( $item->object_id, 'product_cat' );
				}
				
				$menu_item_img_url = get_post_meta( $item->ID, 'menu-item-img-url', true );
				$thumbnail_id = get_woocommerce_term_meta( $item->object_id, 'thumbnail_id', true );
														
				$output .= '<li>';

				$url = '';
				if(!empty($menu_item_img_url)){
					$url = $menu_item_img_url;
				} else if( $thumbnail_id != ''){
					$url = wp_get_attachment_url( $thumbnail_id );
				}
																
				if(!empty($url)) {
					$image = matthewruddy_image_resize( $url, 315, 150, true, false);
					if (array_key_exists('url', $image) && $image['url'] != '' ){
						$output .= '<div class="img-wrap">';
						$output .= '<a'. $attributes .'>'.$args->link_before;
						$output .= '<img src="' . $image['url'] . '" alt="'.$item->title.'"  height="150" width="315" >';
						
						$output .= '</a>	</div>';
					}
				}	


			
				$output .= '<a'. $attributes .'>'.$args->link_before;
				$output .=  apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after.'</a>';
				$output .= '</li>';

				if($this->menu_grid_item_count >= 4){
					$this->menu_grid_close = 'menu_grid_item'; 
				}
			} 					
			else {
				$menu_item_icon_class = get_post_meta( $item->ID, 'menu-item-icon-class', true );
				
				$output .= $indent . '<li' . $id . $class_names .'>';
			
			
				$item_output = $args->before;
				/*
				 * Glyphicons
				 * ===========
				 * Since the the menu item is NOT a Divider or Header we check the see
				 * if there is a value in the attr_title property. If the attr_title
				 * property is NOT null we apply it as the class name for the glyphicon.
				 */
				if ( ! empty( $item->attr_title ) ){
					$item_output .= '<a'. $attributes .'><span class="glyphicon ' . esc_attr( $item->attr_title ) . '"></span>&nbsp;';
				}
				else{
					$item_output .= '<a'. $attributes .'>';
				}
				if(!empty( $menu_item_icon_class )) {
					$item_output .= '<span class="'.$menu_item_icon_class.'"></span>';
				}

				$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
				$item_output .= ( $args->has_children && 0 === $depth ) ? ' <b class="icon-angle-down"></b> </a>' : '</a>';
				$item_output .= $args->after;
				$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			 
			}
		}
	}
	
	function end_el(&$output, $item, $depth = 0, $args = array() ) {
        
		$class_names = '';
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		
		if ( !in_array( 'heading', $classes ) ){
			// do nothing....
		}
		else if( !in_array( 'menu-grid-item', $classes ) ){
			// do nothing....
		}
		else{
		  	 $output .= "</li>\n";	
		}
	}
	
	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element ){
            return;
		}
        $id_field = $this->db_fields['id'];
        // Display this element.
        if ( is_object( $args[0] ) ){
           $args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
		}
        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }
	/**
	 * Menu Fallback
	 * =============
	 * If this function is assigned to the wp_nav_menu's fallback_cb variable
	 * and a manu has not been assigned to the theme location in the WordPress
	 * menu manager the function with display nothing to a non-logged in user,
	 * and will add a link to the WordPress menu manager if logged in as an admin.
	 *
	 * @param array $args passed from the wp_nav_menu function.
	 *
	 */
	public static function fallback( $args ) {
		if ( current_user_can( 'manage_options' ) ) {
			extract( $args );
			$fb_output = null;
			if ( $container ) {
				$fb_output = '<' . $container;
				if ( $container_id )
					$fb_output .= ' id="' . $container_id . '"';
				if ( $container_class )
					$fb_output .= ' class="' . $container_class . '"';
				$fb_output .= '>';
			}
			$fb_output .= '<ul';
			if ( $menu_id )
				$fb_output .= ' id="' . $menu_id . '"';
			if ( $menu_class )
				$fb_output .= ' class="' . $menu_class . '"';
			$fb_output .= '>';
			$fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">Assign a menu</a></li>';
			$fb_output .= '</ul>';
			if ( $container )
				$fb_output .= '</' . $container . '>';
			echo $fb_output;
		}
	}
}

?>