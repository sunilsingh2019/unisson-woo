<?php
/**
 * Block Name: accordion-module
 *
 * This is the template that displays the feature module.
 */

// create id attribute for specific styling
$id = 'accordion-module-' . $block['id'];

$enable__disable = get_field('enable__disable');

if($enable__disable): 
?>
<section id="<?php print $id; ?>" class="section homebanner--section">
    <div class="container">
        <div class="homebanner">
            <div class="homebanner-slider-wrap">
                <div class="homebanner-slider">
                    <?php while(have_rows('slides')): the_row(); 
                        $image = get_sub_field('image');  
                        $heading = get_sub_field('heading');  
                        $blurb = get_sub_field('blurb');  
                        ?>
                    <div class="item">
                        <div class="homebanner-slider-item">
                            <div class="row homebanner-slider--row">
                                <div class="col-lg-6 homebanner-slider--col-media">
                                    <div class="media">
                                        <img src="<?php echo $image['url'] ?>" alt="">
                                    </div>
                                </div>
                                <div class="col-lg-6 homebanner-slider--col-text">
                                    <div class="text">
                                        <h2 class="heading color-yellow"><?php echo $heading; ?></h2>
                                        <?php echo $blurb; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <div class="homebanner-slider-navwrap">
                    <span class="slidenav-prev"></span>
                    <span class="slidenav-next"></span>
                    <div class="num">1/4</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
endif;