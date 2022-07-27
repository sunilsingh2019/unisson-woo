<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header( 'shop' ); ?>
<section class="section pageheader--section">
    <div class="container">
        <div class="pageheader">
            <?php
			/**
			 * woocommerce_before_main_content hook.
			 *
			 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
			 * @hooked woocommerce_breadcrumb - 20
			 */
			do_action( 'woocommerce_before_main_content' );
			?>

        </div>
    </div>
</section>
<section class="section productsec--section background-white pageheader--pullup bgcolor-white">
    <div class="container">
        <div class="productsec">
            <div class="row">
                <?php while ( have_posts() ) : ?>
                <?php the_post(); ?>
                <?php wc_get_template_part( 'content', 'single-product' ); ?>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<section class="section productfaq--section bgcolor-gray">
    <div class="container">
        <div class="productfaq">
            <div class="customtab">
                <div class="customtab--nav">
                    <div class="customtab--nav-preview">More information - <span
                            class="customtab--nav-active">Itenerary</span></div>
                    <ul class="customtab--nav-list">
                        <?php
                        $i = 1; // Set the increment variable
                        // loop through the rows of data for the tab header
                        while ( have_rows('tab') ) : the_row();
                        $tab_title = get_sub_field('tab_title');?>
                        <li><a class="<?php if($i == 1) echo 'active';?>" href="tab<?php echo $i ?>">
                                <?php echo $tab_title; ?></a></li>
                        <?php   
                        $i++; // Increment the increment variable
                        endwhile; //End the loop 
                        ?>
                    </ul>
                </div>
                <div class="customtab--content">
                    <?php
                    $x = 1; // Set the increment variable
                    // loop through the rows of data for the tab header
                    while ( have_rows('tab') ) : the_row();
                    $tab_title = get_sub_field('tab_title');
                    $heading = get_sub_field('heading');
                    $blurb = get_sub_field('blurb');
                    
                    ?>
                    <div class="customtab--content-item <?php if($x == 1) echo 'active';?>"
                        data-id="tab<?php echo $x ?>">
                        <?php if($heading): ?>
                        <h2 class="heading-text color-purple"><?php echo $heading; ?></h2>
                        <?php endif; ?>
                        <?php if($blurb): ?>
                        <p><?php echo $blurb; ?></p>
                        <?php endif; ?>
                        <div class="customaccord">
                            <?php
                        $z = 1; // Set the increment variable
                            // loop through the rows of data for the tab header
                            while ( have_rows('accordion') ) : the_row();
                            $heading = get_sub_field('heading');
                            $blurb = get_sub_field('blurb');
                            $image = get_sub_field('image');
                            
                            ?>
                            <div class="customaccord--item <?php if($z == 1) echo 'active';?>">
                                <?php if($heading): ?>
                                <div class="customaccord--item-header">
                                    <h2><?php echo $heading; ?></h2>
                                </div>
                                <?php endif; ?>
                                <div class="customaccord--item-body">
                                    <div class="row">
                                        <?php if($image): ?>
                                        <div class="col-lg-5">
                                            <div class="media">
                                                <img src="<?php echo $image['url']; ?>" alt="">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if($blurb): ?>
                                        <div class="col-lg-7">
                                            <?php echo $blurb; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php   
                            $z++; // Increment the increment variable
                            endwhile; //End the loop 
                            ?>
                        </div>
                    </div>
                    <?php   
                    $x++; // Increment the increment variable
                    endwhile; //End the loop 
                    ?>

                </div>
            </div>
        </div>
    </div>
</section>

<?php
$enable__disable = get_field('enable__disable');
$blurb = get_field('blurb');
$button = get_field('button');

if($enable__disable): 
  
?>
<section class="section ctafooter--section bgcolor-gray">
    <div class="container">
        <div class="ctafooter">
            <h2 class="heading"><?php echo $blurb; ?></h2>
            <?php if($button): ?>
            <a href="<?php echo $button['url']?>" class="btn btn-purple"><?php echo $button['title']?></a>
            <?php endif; ?>
        </div>
    </div>
</section>


<?php
endif;
		/**
		 * woocommerce_before_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 */
	//	do_action( 'woocommerce_before_main_content' );
	?>

<?php //while ( have_posts() ) : ?>
<?php //the_post(); ?>

<?php //wc_get_template_part( 'content', 'single-product' ); ?>

<?php// endwhile; // end of the loop. ?>

<?php
		/**
		 * woocommerce_after_main_content hook.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
	//	do_action( 'woocommerce_after_main_content' );
	?>

<?php
		/**
		 * woocommerce_sidebar hook.
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
	//	do_action( 'woocommerce_sidebar' );
	?>

<?php
get_footer( 'shop' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */