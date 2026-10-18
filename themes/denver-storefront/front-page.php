<?php
/**
 * Portada: banner de Navidad, categorías, destacados, servicios y confianza.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$categorias = get_terms( array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
) );
$navidad_url = get_term_link( 'navidad', 'product_cat' );
$navidad_url = is_wp_error( $navidad_url ) ? wc_get_page_permalink( 'shop' ) : $navidad_url;
?>
<div id="primary" class="content-area denver-home">
	<main id="main" class="site-main">

		<section class="denver-banner" aria-label="Campaña de Navidad">
			<div class="denver-banner__texto">
				<p class="denver-banner__etiqueta">Navidad 2026</p>
				<h1>Regalos, juguetes y adornos para esta Navidad</h1>
				<p>Compra desde tu celular y recibe en Arequipa en 12 a 24 horas, o recoge en tienda gratis.</p>
				<a class="denver-cta" href="<?php echo esc_url( $navidad_url ); ?>">Ver productos de Navidad</a>
			</div>
		</section>

		<?php if ( ! is_wp_error( $categorias ) && $categorias ) : ?>
		<section class="denver-seccion">
			<h2>Categorías de la campaña</h2>
			<ul class="denver-categorias">
				<?php foreach ( $categorias as $cat ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<span><?php echo esc_html( $cat->name ); ?></span>
						<small><?php echo esc_html( $cat->count ); ?> productos</small>
					</a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if ( wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) ) ) : ?>
		<section class="denver-seccion">
			<h2>Productos destacados</h2>
			<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]' ); ?>
		</section>
		<?php endif; ?>

		<section class="denver-seccion denver-servicios">
			<h2>También en DENVER</h2>
			<ul>
				<li><strong>Copias</strong><span>Blanco y negro y a color</span></li>
				<li><strong>Impresiones</strong><span>Documentos y trabajos escolares</span></li>
				<li><strong>Anillados</strong><span>Informes y tesis listos el mismo día</span></li>
			</ul>
		</section>

		<section class="denver-confianza" aria-label="Compra segura">
			<ul>
				<li><strong>Envíos en Arequipa</strong><span>12 a 24 horas</span></li>
				<li><strong>Paga con Yape</strong><span>o con tarjeta</span></li>
				<li><strong>Boleta o factura</strong><span>según lo que necesites</span></li>
			</ul>
		</section>

	</main>
</div>
<?php
get_footer();
