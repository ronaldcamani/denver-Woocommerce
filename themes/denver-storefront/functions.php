<?php
/**
 * Tema hijo DENVER Storefront.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style(
		'denver-storefront',
		get_stylesheet_uri(),
		array( 'storefront-style' ),
		wp_get_theme()->get( 'Version' )
	);
} );
