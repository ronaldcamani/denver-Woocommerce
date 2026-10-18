<?php
/**
 * Dirección: en Perú casi nadie conoce su código postal, así que no se pide.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$locale['PE']['postcode']['required'] = false;
	$locale['PE']['postcode']['hidden']   = true;
	$locale['PE']['state']['label']       = 'Departamento';
	$locale['PE']['city']['label']        = 'Distrito';
	return $locale;
} );
