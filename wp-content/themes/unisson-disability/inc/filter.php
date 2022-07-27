<?php


add_action( 'wp_ajax_nopriv_filter', 'filter_ajax' );
add_action( 'wp_ajax_filter', 'filter_ajax' );

function filter_ajax() {
  
    $program_category_list = [];
    $program_location_list = [];
    $program_suburb_list = [];
    
    $program_category_list = get_terms(
        array( 'product_cat' ),
        array( 'fields' => 'ids' )
    );
    
    $terms = get_terms(array('taxonomy'=> 'location','hide_empty' => false, ));  
    foreach ( $terms as $term ) {
        if ($term->parent == 0 ) {
            array_push($program_location_list, $term->term_id);
            $subterms = get_terms(array('taxonomy'=> 'location','hide_empty' => false,'parent'=> $term->term_id));
            foreach ($subterms as $key => $value) { 
                array_push($program_suburb_list, $value->term_id);
            }
        }
    }

    $program_category = isset($_POST['program-category'])?$_POST['program-category']:$program_category_list;
    $program_location = isset($_POST['program-location'])?$_POST['program-location']:$program_location_list;
    $program_suburb = isset($_POST['program-suburb'])?$_POST['program-suburb']:$program_suburb_list;
    $sort_by = $_POST['sort_by'];
   

    $args = array(
    'post_type'        	=> 'product',
    'post_per_page'    => -1,
    'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $program_category,
                'operator' => 'IN',
            ),
            array(
                'taxonomy' => 'location',
                'field'    => 'term_id',
                'terms'    => $program_location,
                'operator' => 'IN',
            ),
            array(
                'taxonomy' => 'location',
                'field'    => 'term_id',
                'terms'    => $program_suburb,
                'operator' => 'IN',
            ),
        ),
    
    );

   

  if(!empty($sort_by)){

    $order_param = '';

    if($sort_by == 'dateposted-desc'){
        $order_param = 'date';
    }
    elseif($sort_by == 'price-desc'){
        $order_param = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'DESC';
    }
    elseif($sort_by == 'price-asc'){
        $order_param = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'ASC';
    }elseif($sort_by == 'program-asc'){
       // $order_param = 'meta_value_num';
        //$args['meta_key'] = '_price';
        $args['order'] = 'ASC';
    }elseif($sort_by == 'program-asc'){
       // $order_param = 'meta_value_num';
       // $args['meta_key'] = '_price';
        $args['order'] = 'ASC';
    } else {
        $order_param = 'date';
    }
    $args['orderby'] = $order_param;
  }


global $product;
$loop = new WP_Query($args );
if ( $loop->have_posts() ) :
while ( $loop->have_posts() ) : $loop->the_post(); 
$thumbnail_url = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), '', true); 
// $terms = get_the_terms( $post->ID, 'list_resource_category' );
$post_id = get_the_ID();

//get content taxonomies
$taxonomies = array();
$taxonomies_name = array();
$term_list = wp_get_post_terms($post_id,'product_cat', array("fields" => "all"));
foreach($term_list as $term_single) {
array_push($taxonomies, $term_single->slug);
array_push($taxonomies_name, $term_single->name);
}
//taxonomies tags merge
$taxonomies_data = array_merge($taxonomies);
$taxonomies_name = array_merge($taxonomies_name);


//location

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

$start_date = get_field('tour_start_date', $post_id);
$end_date = get_field('tour_end_date', $post_id);

?>
<div class="col-lg-4 col-md-6 homefilter-item">
    <div class="programfilter-card">
        <div class="media">
            <img src="<?php echo esc_url( $thumbnail_url[0] ); ?>" alt="">
        </div>
        <div class="text">
            <h6 class="heading-location">
                <?php print implode(' - ',$locations_name) ;?>
            </h6>
            <h3 class="heading-title"><?php the_title(); ?></h3>
            <span class="heading-date"><?php echo $start_date; ?> -
                <?php echo $end_date; ?></span>
            <p><?php the_excerpt();  ?>
            </p>
            <div class="card-footer">
                <div class="price">
                    <p>Total Cost</p>
                    <?php 
                         global $product;

                        if ($product->is_type( 'simple' )) { ?>
                    <p class="price-num"><?php echo $product->get_price_html(); ?></p>
                    <?php } ?>
                    <?php 
                        if($product->product_type=='variable') {
                           
                            $prices = $product->get_variation_prices('min', true );
                            $maxprices = $product->get_variation_price( 'max', true ) ;
                            $min_price = current( $prices['price'] );
                            $minPrice = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $min_price ) );
                            $maxPrice = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $maxprices ) );
                                                    
                                                    ?>
                    <p class="price-num"><?php echo $maxPrice;?></p>
                    <?php   } ?>

                </div>
                <a href="<?php the_permalink(); ?>" class="btn btn-yellow">View more</a>
            </div>
        </div>
    </div>
</div>

<?php endwhile; ?>
<?php endif; ?>

<?php
  
}