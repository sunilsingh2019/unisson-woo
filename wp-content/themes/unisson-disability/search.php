<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Unisson_Disability
 */

get_header();
global $product;

?>

<section class="section searchresult--section pageheader--pullup">
    <div class="container">
        <div class="searchresult">
            <div class="searchresult-listing">

                <?php 
				
				
                $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
                if ( have_posts() ) :
                    while (have_posts() ) : the_post();
				 if(isset($_GET['post_type'])) {
						$type = $_GET['post_type'];
						if($type == 'product') {
							
							$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), '' );
                            $post_id = get_the_ID();
							$start_date = get_field('tour_start_date');
							$end_date = get_field('tour_end_date');

                            //product location
                            $locations = array();
                            $locations_name = array();
                            $location_term_list = wp_get_post_terms($post_id, 'location', array("fields" => "all"));
                            foreach($location_term_list as $location_term_single) {
                            array_push($locations, $location_term_single->slug);
                            array_push($locations_name, $location_term_single->name);
                            }
                            //location tags merge
                            $locations_data = array_merge($locations);
                            $locations_name = array_merge($locations_name);

							
    

							?>
                <div class="searchresult-item">
                    <div class="searchresult-card">
                        <div class="media">
                            <img src="<?php  echo $image[0]; ?>" alt="">
                        </div>
                        <div class="text">
                            <h6 class="heading-location">
                                <?php print implode(' - ',$locations_name) ;?>
                            </h6>
                            <h3 class="heading-title"><?php the_title(); ?></h3>
                            <?php if(!empty($start_date || $end_date )): ?>
                            <h5 class="heading-date"><?php echo $start_date; ?> - <?php echo $end_date; ?></h5>
                            <?php endif; ?>
                            <p><?php the_excerpt();  ?></p>
                            <a href="<?php the_permalink(); ?>" class="btn btn-orange">View More</a>
                        </div>
                    </div>
                </div>
                <?php } else { ?>

                <section class="section searchresult--section searchresult-empty--section pageheader--pullup">
                    <div class="container">
                        <div class="searchresult-empty-notice">
                            <div class="icon"><img
                                    src="<?php echo get_template_directory_uri();?>/./images/icons/icon-info.svg"
                                    alt=""></div>
                            <p>No products were found matching your selection.</p>
                        </div>
                    </div>
                </section>

                <?php } ?>
                <?php } else { ?>

                <section class="section searchresult--section searchresult-empty--section pageheader--pullup">
                    <div class="container">
                        <div class="searchresult-empty-notice">
                            <div class="icon"><img
                                    src="<?php echo get_template_directory_uri();?>/./images/icons/icon-info.svg"
                                    alt=""></div>
                            <p>No products were found matching your selection.</p>
                        </div>
                    </div>
                </section>
                <?php } ?>
                <?php endwhile; 
            //the_posts_navigation();
            else: ?>

                <section class="section searchresult--section searchresult-empty--section pageheader--pullup">
                    <div class="container">
                        <div class="searchresult-empty-notice">
                            <div class="icon"><img
                                    src="<?php echo get_template_directory_uri();?>/./images/icons/icon-info.svg"
                                    alt=""></div>
                            <p>No products were found matching your selection.</p>
                        </div>
                    </div>
                </section>

                <?php endif; ?>
            </div>
            <!-- <ul class="pagination text-center">
                <li class="prev"><a href="#"></a></li>
                <li class="active"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li><a href="#">4</a></li>
                <li><a href="#">...</a></li>
                <li class="next"><a href="#"></a></li>
            </ul> -->
            <?php
            if ( $GLOBALS['wp_query']->max_num_pages <= 1 ) {
            return;
            }
            $args = wp_parse_args(
            $args,
            array(
                'mid_size' => 2,
                'prev_next' => true,
                'prev_text' => __( '&lang;' ),
                'next_text' => __( '&rang;' ),
                'screen_reader_text' => __( 'Posts navigation' ),
                'type' => 'array',
                'current' => max( 1, get_query_var( 'paged' ) ),
            )
            );
            $links = paginate_links( $args ); ?>
            <ul class="pagination text-center">
                <?php
                        foreach ( $links as $key => $link ) {
                        ?>
                <li class="page-item <?php echo strpos( $link, 'current' ) ? 'active' : ''; ?>">
                    <?php echo str_replace( 'page-numbers', 'page-link', $link ); ?>
                </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</section>
<section class="section ctafooter--section bgcolor-gray">
    <div class="container">
        <div class="ctafooter">
            <h2 class="heading">SUBSCRIBE TO OUR WEEKLY NEWSLETTER</h2>
            <a href="#" class="btn btn-purple">Subscribe</a>
        </div>
    </div>
</section>
<?php
get_footer();