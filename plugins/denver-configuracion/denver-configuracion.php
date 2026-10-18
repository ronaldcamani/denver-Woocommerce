<?php
/**
 * Plugin Name: DENVER Configuración
 * Description: Ajustes de la tienda DENVER LIBRERIA: comprobante (boleta/factura), pago con Yape y botón de WhatsApp.
 * Version: 0.2.0
 * Text Domain: denver-configuracion
 */

defined( 'ABSPATH' ) || exit;

define( 'DENVER_VERSION', '0.2.0' );
define( 'DENVER_DIR', plugin_dir_path( __FILE__ ) );
define( 'DENVER_URL', plugin_dir_url( __FILE__ ) );
define( 'DENVER_WHATSAPP', '51902022295' ); // 902 022 295 con código de país.

// Precios con el formato "S/ 12.90".
add_filter( 'woocommerce_currency_symbol', function ( $symbol, $currency ) {
	return 'PEN' === $currency ? 'S/' : $symbol;
}, 10, 2 );

require_once DENVER_DIR . 'includes/comprobante.php';
require_once DENVER_DIR . 'includes/direccion.php';
require_once DENVER_DIR . 'includes/whatsapp.php';
require_once DENVER_DIR . 'includes/gateway-yape.php';
