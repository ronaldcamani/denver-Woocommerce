<?php
/**
 * Portada: banner de Navidad, productos, servicios y confianza. Simple y clara.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cats = denver_categorias();
$img  = get_stylesheet_directory_uri() . '/assets/img/';
$navidad = get_term_by( 'slug', 'navidad', 'product_cat' );
$url_navidad = $navidad ? get_term_link( $navidad ) : wc_get_page_permalink( 'shop' );

$servicios = array(
	array( 'Copias', 'Libros y expedientes', '6 por S/ 0.30' ),
	array( 'Impresiones y plotter', 'Tamaños A5, A4, A3, A2, A1 y A0', 'Cotizar' ),
	array( 'Escaneos', 'Tus documentos en digital', 'Cotizar' ),
	array( 'Anillados y enmicados', 'Informes, tesis y carpetas', 'Cotizar' ),
);
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
				<li><a href="#servicios"><strong>Copias</strong><span>6 por S/ 0.30</span></a></li>
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

		<section class="servicios" id="servicios" aria-labelledby="t-servicios">
			<div class="servicios__caja">
				<div class="servicios__lista">
					<h2 id="t-servicios">Copias e impresiones</h2>
					<ul>
						<?php foreach ( $servicios as $s ) : ?>
							<li>
								<a href="<?php echo esc_url( denver_wa( 'Hola DENVER, quisiera consultar por: ' . $s[0] ) ); ?>" target="_blank" rel="noopener">
									<span><strong><?php echo esc_html( $s[0] ); ?></strong><small><?php echo esc_html( $s[1] ); ?></small></span>
									<span class="servicios__precio"><?php echo esc_html( $s[2] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="servicios__nota">Toca un servicio y te cotizamos por WhatsApp.</p>
				</div>
				<div class="servicios__lado">
					<img src="<?php echo esc_url( $img . 'flyer-plotter.jpg' ); ?>" alt="Plotter imprimiendo un plano" width="740" height="452" loading="lazy">
					<form class="calculadora" data-calculadora aria-labelledby="t-calc">
						<h3 id="t-calc">¿Cuánto cuestan tus copias?</h3>
						<label for="copias">Número de copias</label>
						<input id="copias" name="copias" type="number" inputmode="numeric" min="1" max="10000" step="1" value="60">
						<p class="calculadora__total" aria-live="polite">Total <strong data-total>S/ 3.00</strong></p>
						<p class="calculadora__nota">Precio referencial con la promoción de 6 copias por S/ 0.30. Confirma en tienda.</p>
					</form>
				</div>
			</div>
		</section>

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
