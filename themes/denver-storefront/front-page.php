<?php
/**
 * Portada: banner de Navidad, productos y confianza. Simple y clara.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cats = denver_categorias();
$navidad = get_term_by( 'slug', 'navidad', 'product_cat' );
$url_navidad = $navidad ? get_term_link( $navidad ) : wc_get_page_permalink( 'shop' );

?>
<div id="primary" class="content-area denver-home">
	<main id="main" class="site-main">

		<section class="banner" aria-labelledby="t-banner">
			<div class="banner__texto">
				<h1 id="t-banner">Navidad en Denver</h1>
				<p>Regalos, juguetes y adornos. Delivery en Arequipa en 12 a 24 horas, o recojo gratis en tienda.</p>
				<a class="boton boton--amarillo" href="<?php echo esc_url( $url_navidad ); ?>">Ver productos de Navidad</a>
			</div>
			<?php if ( $cats ) : ?>
			<ul class="banner__cats">
				<?php foreach ( array_slice( $cats, 0, 3 ) as $c ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><strong><?php echo esc_html( $c->name ); ?></strong><span><?php echo esc_html( $c->count ); ?> productos</span></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><strong>Tienda</strong><span>Ver todo</span></a></li>
			</ul>
			<?php endif; ?>
		</section>

		<?php if ( wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) ) ) : ?>
		<section class="vitrina" aria-labelledby="t-vitrina">
			<h2 id="t-vitrina">Para regalar esta Navidad</h2>
			<?php echo do_shortcode( '[products category="navidad" limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]' ); ?>
			<p class="vitrina__mas"><a class="boton" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver toda la tienda</a></p>
		</section>
		<?php endif; ?>

		<section class="confianza" aria-label="Compra segura">
			<ul>
				<li><strong>Delivery en Arequipa</strong><span>Llega en 12 a 24 horas, o recoge gratis en tienda.</span></li>
				<li><strong>Paga con Yape</strong><span>Escribe tu código de aprobación al terminar el pedido.</span></li>
				<li><strong>Boleta o factura</strong><span>Eliges al comprar. Para factura, tu RUC y razón social.</span></li>
			</ul>
		</section>

	</main>
</div>
<?php
get_footer();
