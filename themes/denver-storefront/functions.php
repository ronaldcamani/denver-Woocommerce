<?php
/**
 * Tema hijo DENVER Storefront.
 */

defined( 'ABSPATH' ) || exit;

const DENVER_WA = '51902022295'; // WhatsApp de la tienda (902 022 295).

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_stylesheet_directory();
	wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'denver-storefront', get_stylesheet_uri(), array( 'storefront-style' ), filemtime( $dir . '/style.css' ) );
	wp_enqueue_script( 'denver-tema', get_stylesheet_directory_uri() . '/assets/js/tema.js', array(), filemtime( $dir . '/assets/js/tema.js' ), true );
	if ( is_front_page() ) {
		wp_enqueue_script( 'denver-home', get_stylesheet_directory_uri() . '/assets/js/home.js', array(), filemtime( $dir . '/assets/js/home.js' ), true );
	}
} );

// Fuentes propias (Barlow) y marca "js" para animar solo si hay JavaScript.
add_action( 'wp_head', function () {
	$f = get_stylesheet_directory_uri() . '/assets/fonts/';
	echo '<meta name="theme-color" content="#0A1B5C">' . "\n";
	foreach ( array( 'barlow-condensed-latin-800-normal', 'barlow-latin-400-normal' ) as $n ) {
		echo '<link rel="preload" href="' . esc_url( $f . $n . '.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
	echo '<script>document.documentElement.classList.add("js")</script>' . "\n";
}, 1 );
add_filter( 'wp_viewport_meta', fn() => 'width=device-width, initial-scale=1, viewport-fit=cover' );

// Sin barra lateral: el contenido va a todo el ancho.
add_action( 'init', function () {
	remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
} );
add_filter( 'body_class', fn( $c ) => array_merge( $c, array( 'storefront-full-width-content' ) ) );

// Imagen genérica con el logo para productos sin foto.
add_filter( 'woocommerce_placeholder_img_src', fn() => get_stylesheet_directory_uri() . '/assets/placeholder.svg?v=' . filemtime( get_stylesheet_directory() . '/assets/placeholder.svg' ) );

// Pie de página compacto con los datos de la empresa.
add_action( 'init', function () {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	add_action( 'storefront_footer', function () {
		echo '<div class="site-info">DENVER S.A.C. &middot; RUC 20610817433<br>Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa</div>';
	}, 20 );
} );

/** Enlace de WhatsApp con un mensaje ya escrito. */
function denver_wa( $mensaje ) {
	return 'https://wa.me/' . DENVER_WA . '?text=' . rawurlencode( $mensaje );
}


/* ---------- Cabecera y pie propios (limpios, como una tienda clásica) ---------- */

add_action( 'init', function () {
	foreach ( array(
		array( 'storefront_header_container', 0 ), array( 'storefront_skip_links', 5 ),
		array( 'storefront_site_branding', 20 ), array( 'storefront_secondary_navigation', 30 ),
		array( 'storefront_product_search', 40 ), array( 'storefront_header_container_close', 41 ),
		array( 'storefront_primary_navigation_wrapper', 42 ), array( 'storefront_primary_navigation', 50 ),
		array( 'storefront_header_cart', 60 ), array( 'storefront_primary_navigation_wrapper_close', 68 ),
	) as $q ) {
		remove_action( 'storefront_header', $q[0], $q[1] );
	}
	remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
	add_action( 'storefront_header', 'denver_cabecera', 10 );
	add_action( 'storefront_footer', 'denver_pie', 10 );
}, 20 );

function denver_icono( $nombre ) {
	$i = array(
		'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'buscar'  => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
		'usuario' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20c.6-3.6 3.6-5.5 7.5-5.5s6.900 1.900 7.500 5.500"/>',
		'carrito' => '<path d="M3 4h2.500l2.200 11h10.500l2-8H6.500"/><circle cx="9.500" cy="19" r="1.300"/><circle cx="17" cy="19" r="1.300"/>',
	);
	return '<svg class="icono" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $i[ $nombre ] . '</svg>';
}

function denver_enlace_carrito() {
	$n = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	return '<a class="denver-carrito" href="' . esc_url( wc_get_cart_url() ) . '">' . denver_icono( 'carrito' )
		. '<span class="cuenta">' . esc_html( $n ) . '</span><span class="etiqueta">Mi carrito</span></a>';
}
add_filter( 'woocommerce_add_to_cart_fragments', function ( $f ) {
	$f['a.denver-carrito'] = denver_enlace_carrito();
	return $f;
} );

/** Categorías con productos publicados (sin "Uncategorized"). */
function denver_categorias() {
	$t = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
	return is_wp_error( $t ) ? array() : $t;
}

function denver_cabecera() {
	$cats = denver_categorias();
	?>
	<div class="cabecera">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="DENVER LIBRERIA, inicio">
			<span class="logo__nombre">Denver</span><span class="logo__sub">Librería</span>
		</a>

		<div class="categorias">
			<button class="categorias__boton" type="button" aria-expanded="false" aria-controls="menu-categorias">
				<?php echo denver_icono( 'menu' ); // phpcs:ignore ?><span>Categorías</span>
			</button>
			<ul class="categorias__lista" id="menu-categorias" hidden>
				<?php foreach ( $cats as $c ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?> <small><?php echo esc_html( $c->count ); ?></small></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Toda la tienda</a></li>
			</ul>
		</div>

		<form class="buscador" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="buscar">Buscar productos</label>
			<input id="buscar" type="search" name="s" placeholder="Busca en nuestro catálogo" value="<?php echo esc_attr( get_search_query() ); ?>">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="Buscar"><?php echo denver_icono( 'buscar' ); // phpcs:ignore ?></button>
		</form>

		<a class="denver-cuenta" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo denver_icono( 'usuario' ); // phpcs:ignore ?><span class="etiqueta">Mi cuenta</span></a>
		<?php echo denver_enlace_carrito(); // phpcs:ignore ?>
	</div>

	<nav class="barra" aria-label="Principal">
		<ul>
			<?php foreach ( $cats as $c ) : ?>
				<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a></li>
			<?php endforeach; ?>
			<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Tienda</a></li>
		</ul>
	</nav>
	<?php
}

function denver_pie() {
	?>
	<div class="pie">
		<div class="pie__col">
			<p class="pie__marca">Denver Librería</p>
			<p>Útiles escolares y de oficina, juguetes y regalos.</p>
			<p>Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa.</p>
		</div>
		<div class="pie__col">
			<p class="pie__titulo">Tienda</p>
			<ul>
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Todos los productos</a></li>
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Mi cuenta</a></li>
				<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Mi carrito</a></li>
			</ul>
		</div>
		<div class="pie__col">
			<p class="pie__titulo">Ayuda</p>
			<ul>
				<?php
				foreach ( array(
					'terminos-y-condiciones' => 'Términos y condiciones',
					'politica-de-privacidad' => 'Política de privacidad',
					'envios-cambios-y-devoluciones' => 'Envíos, cambios y devoluciones',
					'libro-de-reclamaciones' => 'Libro de Reclamaciones',
				) as $slug => $titulo ) {
					$pg = get_page_by_path( $slug );
					if ( $pg ) {
						echo '<li><a href="' . esc_url( get_permalink( $pg ) ) . '">' . esc_html( $titulo ) . '</a></li>';
					}
				}
				?>
			</ul>
		</div>
		<div class="pie__col">
			<p class="pie__titulo">Escríbenos</p>
			<p><a class="pie__wa" href="<?php echo esc_url( denver_wa( 'Hola DENVER LIBRERIA, quisiera hacer una consulta.' ) ); ?>" target="_blank" rel="noopener">WhatsApp 902 022 295</a></p>
			<p>libreriadenver@gmail.com</p>
		</div>
		<p class="pie__legal">DENVER S.A.C. &middot; RUC 20610817433</p>
	</div>
	<?php
}
