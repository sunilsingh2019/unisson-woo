<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
    

?>
<section class="section homefilter--section">
    <div class="container">
	<?php
			/**
			 * Hook: woocommerce_before_main_content.
			 *
			 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
			 * @hooked woocommerce_breadcrumb - 20
			 * @hooked WC_Structured_Data::generate_website_data() - 30
			 */
			//do_action( 'woocommerce_before_main_content' );
			?>
        <div class="homefilter">
            
            <div class="homefilter-selects">
                <div class="custom-dropdown">
                    <div class="custom-dropdown-btn">Program</div>
                    <div class="custom-dropdown-list">
                        <ul>
                            <li>
                                <div class="checkbox">
                                    <input id="lga1" type="checkbox" name="lga1">
                                    <label for="lga1">LGA 1</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga2" type="checkbox" name="lga2">
                                    <label for="lga2">LGA 2</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga3" type="checkbox" name="lga3">
                                    <label for="lga3">LGA 3</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga4" type="checkbox" name="lga4">
                                    <label for="lga4">LGA 4</label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="custom-dropdown">
                    <div class="custom-dropdown-btn">Location</div>
                    <div class="custom-dropdown-list">
                        <ul>
                            <li>
                                <div class="checkbox">
                                    <input id="lga5" type="checkbox" name="lga5">
                                    <label for="lga5">LGA 1</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga6" type="checkbox" name="lga6">
                                    <label for="lga6">LGA 2</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga7" type="checkbox" name="lga7">
                                    <label for="lga7">LGA 3</label>
                                </div>
                            </li>
                            <li>
                                <div class="checkbox">
                                    <input id="lga8" type="checkbox" name="lga8">
                                    <label for="lga8">LGA 4</label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="programfilter">
                <div class="programfilter-header">
                    <button class="reset-btn color-orange dis-sm">Reset search</button>
                    <div class="sortby">
                        <span>Sort by - </span>
						<?php 	do_action( 'woocommerce_before_shop_loop' ); ?>
						
                    </div>
                </div>
                <div class="programfilter-body">
                    
					<?php
						if ( woocommerce_product_loop() ) {

							/**
							 * Hook: woocommerce_before_shop_loop.
							 *
							 * @hooked woocommerce_output_all_notices - 10
							 * @hooked woocommerce_result_count - 20
							 * @hooked woocommerce_catalog_ordering - 30
							 */
							//do_action( 'woocommerce_before_shop_loop' );

							woocommerce_product_loop_start();

							if ( wc_get_loop_prop( 'total' ) ) {
								while ( have_posts() ) {
									the_post();

									/**
									 * Hook: woocommerce_shop_loop.
									 */
									do_action( 'woocommerce_shop_loop' );

									wc_get_template_part( 'content', 'product' );
								}
							}

							woocommerce_product_loop_end();

							/**
							 * Hook: woocommerce_after_shop_loop.
							 *
							 * @hooked woocommerce_pagination - 10
							 */
							do_action( 'woocommerce_after_shop_loop' );
						} else {
							/**
							 * Hook: woocommerce_no_products_found.
							 *
							 * @hooked wc_no_products_found - 10
							 */
							do_action( 'woocommerce_no_products_found' );
						}

						/**
						 * Hook: woocommerce_after_main_content.
						 *
						 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
						 */
						do_action( 'woocommerce_after_main_content' ); ?>
                    
                </div>
            </div>
        </div>
    </div>
</section>

<!-- <header class="woocommerce-products-header">
	<?php //if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
		<h1 class="woocommerce-products-header__title page-title"><?php// woocommerce_page_title(); ?></h1>
	<?php //endif; ?>

	<?php
	/**
	 * Hook: woocommerce_archive_description.
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	//do_action( 'woocommerce_archive_description' );
	?>
</header> -->


<?php 


/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
//do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );