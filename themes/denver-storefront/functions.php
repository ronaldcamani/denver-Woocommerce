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

// Base móvil: pantalla completa bajo el notch y barra del navegador del color de la marca.
add_action( 'wp_head', function () {
	echo '<meta name="theme-color" content="#0A1B5C">' . "\n";
}, 1 );
add_filter( 'wp_viewport_meta', fn() => 'width=device-width, initial-scale=1, viewport-fit=cover' );

// Sin barra lateral: en celular el contenido va a todo el ancho.
add_action( 'init', function () {
	remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
} );
add_filter( 'body_class', fn( $c ) => array_merge( $c, array( 'storefront-full-width-content' ) ) );

// Imagen genérica con el logo para productos sin foto.
add_filter( 'woocommerce_placeholder_img_src', fn() => get_stylesheet_directory_uri() . '/assets/placeholder.svg' );

// Pie de página con los datos de la empresa.
add_action( 'init', function () {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	add_action( 'storefront_footer', function () {
		echo '<div class="site-info">&copy; ' . esc_html( gmdate( 'Y' ) ) . ' DENVER S.A.C. &middot; RUC 20610817433<br>'
			. 'Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa</div>';
	}, 20 );
} );
