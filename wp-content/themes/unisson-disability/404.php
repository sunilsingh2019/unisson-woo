<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Unisson_Disability
 */

get_header();
?>
<main class="sitecontent">

   

    <section class="section accountlogin--section pageheader--pullup text-center">
        <div class="container">
            <div class="accountlogin">
                <h2 class="heading-title error-heading">Oops looks like the page you 
					are looking for doesn’t exist</h2>
				<a href="<?php echo esc_url( home_url( '/' ) ) ?>" class="btn btn-purple">Go to homepage</a>
            </div>
        </div>
    </section>



</main>
<?php
get_footer();