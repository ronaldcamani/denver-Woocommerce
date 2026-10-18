<?php
/**
 * Plugin Name: DENVER Configuración
 * Description: Ajustes de la tienda DENVER LIBRERIA (formato de moneda, comprobante, pagos).
 * Version: 0.1.0
 * Text Domain: denver-configuracion
 */

defined( 'ABSPATH' ) || exit;

define( 'DENVER_VERSION', '0.1.0' );
define( 'DENVER_DIR', plugin_dir_path( __FILE__ ) );

// Precios con el formato "S/ 12.90".
add_filter( 'woocommerce_currency_symbol', function ( $symbol, $currency ) {
	return 'PEN' === $currency ? 'S/' : $symbol;
}, 10, 2 );
