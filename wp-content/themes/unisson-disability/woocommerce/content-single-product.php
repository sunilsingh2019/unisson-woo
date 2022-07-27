<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>



<div class="col-lg-6 productsec--col-media">
    <div class="productsec--media">
        <div class="productsec--media-primary">
            <div class="productsec--media-primary-slider">
                <?php $attachment_ids = $product->get_gallery_attachment_ids(); ?>
                <?php  foreach( $attachment_ids as $attachment_id ) {
      										  $image_link =wp_get_attachment_url( $attachment_id ); ?>
                <div class="item">
                    <div class="media-item">
                        <?php  echo '<img src="' . $image_link . '">';?>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="slider-navwrap">
                <span class="slidenav-prev"></span>
                <span class="slidenav-next"></span>
                <div class="num">1/4</div>
            </div>
        </div>
        <div class="productsec--media-secondary dis-md">
            <div class="productsec--media-secondary-slider">
                <?php  foreach( $attachment_ids as $attachment_id ) {
      				 $image_link =wp_get_attachment_url( $attachment_id ); ?>
                <div class="item">
                    <div class="media-item">
                        <?php  echo '<img src="' . $image_link . '">';?>
                    </div>
                </div>
                <?php } ?>
            </div>
            <!-- <div class="slider-navwrap">
                <span class="slidenav-prev"></span>
                <span class="slidenav-next"></span>
                <div class="num">1/4</div>
            </div> -->
        </div>
    </div>
</div>
<div class="col-lg-6 productsec--col-text">
    <div class="productsec--text">
        <h6 class="heading-location">
            <?php 
            global $product;
            $id = $product->get_id();
              $locations = array();
              $locations_name = array();
              $location_term_list = wp_get_post_terms($id, 'location', array("fields" => "all"));
              foreach($location_term_list as $location_term_single) {
              array_push($locations, $location_term_single->slug);
              array_push($locations_name, $location_term_single->name);
              }
              //location tags merge
              $locations_data = array_merge($locations);
              $locations_name = array_merge($locations_name);
            ?>
            <?php print implode(' - ',$locations_name) ;
            //var_dump($locations_name);
            ?>
        </h6>
        <?php 
        // If the WC_product Object is not defined globally
            if ( ! is_a( $product, 'WC_Product' ) ) {
                $product = wc_get_product( get_the_id() );
            }

        
        ?>

        <h2 class="heading-title"><?php echo $product->get_name(); ?></h2>
        <?php
        $start_date = get_field('tour_start_date');
		$end_date = get_field('tour_end_date'); ?>
        <?php if(!empty($start_date || $end_date )): ?>
        <h5 class="heading-date"><?php echo $start_date; ?> - <?php echo $end_date; ?></h5>
        <?php endif; ?>
        <div class="productsec-description">
            <?php 
           the_content();  
            
            ?>
        </div>
        <?php 
        //check variation is selected or not 
      // do_action( 'woocommerce_single_product_summary' ); 
        
     
        ?>
        <div class="price">
            <p>Total cost</p>
            <?php 
             if ($product->is_type( 'simple' )) { ?>
            <p class="price-num"><?php echo $product->get_price_html(); ?></p>
            <?php } ?>
            <?php 
             if($product->product_type=='variable') {
                //  $available_variations = $product->get_available_variations();
                //  $count = count($available_variations)-1;
                //  $variation_id=$available_variations[$count]['variation_id']; // Getting the variable id of just the 1st product. You can loop $available_variations to get info about each variation.
                //  $variable_product1= new WC_Product_Variation( $variation_id );
                //  $regular_price = $variable_product1 ->regular_price;
                //  $sales_price = $variable_product1 ->sale_price; 
                $prices = $product->get_variation_prices('min', false );
                $maxprices = $product->get_variation_price( 'max', false ) ;
                $min_price = current( $prices['price'] );
                //$max_price = current( $maxprices['price'] );
                $minPrice = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $min_price ) );
                $maxPrice = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $maxprices ) );
                
                ?>
            <p class="price-num"><?php echo $maxPrice;?></p>
            <?php   } ?>
        </div>

        <?php do_action( 'woocommerce_single_product_summary' ); ?>

    </div>
</div>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

    <?php
	/**
	 * Hook: woocommerce_before_single_product_summary.
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20
	 */
	//do_action( 'woocommerce_before_single_product_summary' );
	?>

    <div class="summary entry-summary">
        <?php
		/**
		 * Hook: woocommerce_single_product_summary.
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_rating - 10
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked woocommerce_template_single_meta - 40
		 * @hooked woocommerce_template_single_sharing - 50
		 * @hooked WC_Structured_Data::generate_product_data() - 60
		 */
		//do_action( 'woocommerce_single_product_summary' );
		?>
    </div>

    <?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	//do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php //do_action( 'woocommerce_after_single_product' ); ?>