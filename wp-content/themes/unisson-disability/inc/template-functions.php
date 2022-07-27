<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Unisson_Disability
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function unisson_disability_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}
	
	if (  is_user_logged_in() ) {
		$classes[] = 'order-pages logged-in';
	}else {
		$classes[] = 'logged-out';
	}
	// if (  is_user_logged_in('my-account') ) {
	// 	$classes[] = 'login-login';
	// } else{
	// 	$classes[] = 'not-login';
	// }
	if (  is_page('login') ) {
		$classes[] = 'page-login';
	}
	
	if (  is_page('register') ) {
		$classes[] = 'page-register';
	}

	// if (is_user_logged_in()) {
    // 	$classes[] = 'logged-in';
	// } else {
	// 	$classes[] = 'logged-out';
	// }

	return $classes;
}
add_filter( 'body_class', 'unisson_disability_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function unisson_disability_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'unisson_disability_pingback_header' );