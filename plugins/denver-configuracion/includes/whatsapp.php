<?php
/**
 * Botón flotante de WhatsApp.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', function () {
	$texto = rawurlencode( 'Hola DENVER LIBRERIA, quisiera hacer una consulta.' );
	$url   = 'https://wa.me/' . DENVER_WHATSAPP . '?text=' . $texto;
	?>
	<a class="denver-whatsapp" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"
		aria-label="Escríbenos por WhatsApp">
		<svg viewBox="0 0 32 32" width="30" height="30" aria-hidden="true" fill="#fff">
			<path d="M16 3C9 3 3.3 8.6 3.3 15.5c0 2.4.7 4.6 1.9 6.5L3 29l7.2-2.1c1.8 1 3.8 1.5 5.8 1.5 7 0 12.7-5.6 12.7-12.5S23 3 16 3zm0 22.7c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-4.3 1.2 1.2-4.200-.3-.4a10 10 0 0 1-1.500-5.300C5.600 9.900 10.300 5.300 16 5.300s10.400 4.600 10.400 10.200S21.700 25.700 16 25.700zm5.700-7.500c-.3-.2-1.800-.9-2.100-1s-.5-.2-.7.2-.8 1-.9 1.200-.3.200-.6.100c-.3-.2-1.300-.5-2.500-1.500-.9-.8-1.500-1.800-1.700-2.100-.2-.3 0-.5.100-.6l.5-.5c.1-.2.200-.3.300-.5s0-.4 0-.5l-1-2.300c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.100-.8.400s-1.100 1.100-1.100 2.600 1.100 3 1.300 3.200 2.200 3.400 5.400 4.700c3.200 1.300 3.200.9 3.800.8s1.800-.7 2.100-1.500.3-1.300.2-1.500-.3-.3-.6-.4z"/>
		</svg>
	</a>
	<style>
		.denver-whatsapp {
			position: fixed;
			right: calc(16px + env(safe-area-inset-right));
			bottom: calc(16px + env(safe-area-inset-bottom));
			z-index: 999;
			display: flex; align-items: center; justify-content: center;
			width: 56px; height: 56px; border-radius: 50%;
			background: #25D366;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
			transition: transform 140ms cubic-bezier(0.23, 1, 0.32, 1);
			touch-action: manipulation;
		}
		.denver-whatsapp:active { transform: scale(0.94); }
		@media (prefers-reduced-motion: reduce) { .denver-whatsapp { transition: none; } }
	</style>
	<?php
} );
