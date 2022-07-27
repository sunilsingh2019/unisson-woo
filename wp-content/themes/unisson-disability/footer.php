<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Unisson_Disability
 */

?>
</main>

<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-unisson app-md">
                <img src="<?php echo get_template_directory_uri();?>/./images/logo.svg" alt="">
            </div>
            <div class="row">
                <?php if(have_rows('menu','options')): ?>
                <div class="col-lg-3">

                    <ul class="footer-top-list">
                        <?php 
                        while(have_rows('menu','options')): the_row(); 
                        $menu = get_sub_field('link_item','options'); ?>
                        <li><a href="<?php echo $menu['url'] ?>"><?php echo $menu['title'] ?></a></li>
                        <?php endwhile; ?>
                    </ul>

                </div>
                <?php endif; ?>
                <?php $contact_info = get_field('contact_info','options'); ?>
                <?php if(!empty($contact_info)): ?>
                <div class="col-lg-3">
                    <?php echo $contact_info; ?>
                </div>
                <?php endif; ?>
                <?php if(have_rows('social_links','options')): ?>
                <div class="col-lg-5 ml-lg-auto">
                    <div class="lets-connect">
                        <h4>Let's connect</h4>
                        <div class="social-icons">
                            <?php 
                            while(have_rows('social_links','options')): the_row(); 
                            $svg_icon = get_sub_field('svg_icon','options'); 
                            $link = get_sub_field('link','options'); ?>
                            <a href="<?php echo $link; ?>">
                                <img src="<?php echo $svg_icon['url'] ?>" alt="">
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php 
    $ndis_logo = get_field('ndis_logo','options');
    $ndis_text = get_field('ndis_text','options');
    $copyright_text = get_field('copyright_text','options');
    $unisson_logo = get_field('unisson_logo','options');
     ?>
    <div class="footer-bot">
        <div class="container">
            <div class="footer-logos dis-md">
                <div class="footer-ndis">
                    <?php if(!empty($ndis_logo)): ?>
                    <div class="logo">
                        <img src="<?php echo $ndis_logo['url'] ?>" alt="">
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($ndis_text)): ?>
                    <p class="text"><?php echo $ndis_text; ?></p>
                    <?php endif; ?>
                </div>
                <?php if(!empty($unisson_logo)): ?>
                <div class="footer-unisson">
                    <img src="<?php echo $unisson_logo['url'] ?>" alt="">
                </div>
                <?php endif; ?>
            </div>
            <div class="footer-bot-text">
                <div class="footer-ndis app-md">
                    <?php if(!empty($ndis_logo)): ?>
                    <div class="logo">
                        <img src="<?php echo $ndis_logo['url'] ?>" alt="">
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($ndis_text)): ?>
                    <p class="text"><?php echo $ndis_text; ?></p>
                    <?php endif; ?>
                </div>
                <?php if(!empty($copyright_text)): ?>
                <h2 class="copyright">&copy; <?php echo $copyright_text; ?></h2>
                <?php endif; ?>
                <ul class="footer-bot-list">
                    <?php 
                    while(have_rows('footer_bottom_menu','options')): the_row(); 
                    $item = get_sub_field('item','options');  ?>
                    <li>
                        <a href="<?php echo $item['url'] ?>"><?php echo $item['title'] ?></a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"
    integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"
    integrity="sha512-3j3VU6WC5rPQB4Ld1jnLV7Kd5xr+cq9avvhwqzbH/taCRNURoeEpoPBK9pDyeukwSxwRPJ8fDgvYXd6SkaZ2TA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"
    integrity="sha512-XtmMtDEcNz2j7ekrtHvOVR4iwwaD6o/FUJe6+Zq+HgcCsk3kj4uSQQR8weQ2QVj1o0Pk6PwYLohm206ZzNfubg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/jquery.simplePagination.js"></script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js" crossorigin="anonymous"></script>
<script src="<?php echo get_template_directory_uri();?>/js/app.js"></script>

<?php wp_footer(); ?>
</body>

</html>