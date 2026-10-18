<?php
/**
 * Campos del checkout de bloques: tipo de comprobante, documento y razón social.
 */

defined( 'ABSPATH' ) || exit;

const DENVER_CAMPO_TIPO   = 'denver/tipo_comprobante';
const DENVER_CAMPO_DOC    = 'denver/documento';
const DENVER_CAMPO_RAZON  = 'denver/razon_social';

/** Regla JSON Schema: "el tipo de comprobante elegido es $valor". */
function denver_regla_tipo( $valor, $negar = false ) {
	$condicion = array( 'const' => $valor );
	return array(
		'type'       => 'object',
		'properties' => array(
			'customer' => array(
				'properties' => array(
					'additional_fields' => array(
						'required'   => $negar ? array() : array( DENVER_CAMPO_TIPO ),
						'properties' => array(
							DENVER_CAMPO_TIPO => $negar ? array( 'not' => $condicion ) : $condicion,
						),
					),
				),
			),
		),
	);
}

add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}

	woocommerce_register_additional_checkout_field( array(
		'id'          => DENVER_CAMPO_TIPO,
		'label'       => 'Comprobante de pago',
		'location'    => 'contact',
		'type'        => 'select',
		'required'    => true,
		'placeholder' => 'Elige boleta o factura',
		'options'     => array(
			array( 'value' => 'boleta', 'label' => 'Boleta' ),
			array( 'value' => 'factura', 'label' => 'Factura' ),
		),
	) );

	woocommerce_register_additional_checkout_field( array(
		'id'                => DENVER_CAMPO_DOC,
		'label'             => 'DNI, carné de extranjería o RUC',
		'location'          => 'contact',
		'type'              => 'text',
		'required'          => true,
		'attributes'        => array( 'maxLength' => 12, 'autocomplete' => 'off' ),
		'sanitize_callback' => fn( $v ) => strtoupper( preg_replace( '/\s+/', '', (string) $v ) ),
	) );

	woocommerce_register_additional_checkout_field( array(
		'id'                => DENVER_CAMPO_RAZON,
		'label'             => 'Razón social (solo para factura)',
		'location'          => 'contact',
		'type'              => 'text',
		'required'          => denver_regla_tipo( 'factura' ),
		'hidden'            => denver_regla_tipo( 'factura', true ),
		'attributes'        => array( 'maxLength' => 120, 'autocomplete' => 'organization' ),
		'sanitize_callback' => fn( $v ) => trim( preg_replace( '/\s+/', ' ', (string) $v ) ),
	) );
} );

/** Validación cruzada con mensajes claros en español. */
add_action( 'woocommerce_blocks_validate_location_contact_fields', function ( $errors, $campos ) {
	$tipo  = $campos[ DENVER_CAMPO_TIPO ] ?? '';
	$doc   = strtoupper( preg_replace( '/\s+/', '', (string) ( $campos[ DENVER_CAMPO_DOC ] ?? '' ) ) );
	$razon = trim( (string) ( $campos[ DENVER_CAMPO_RAZON ] ?? '' ) );

	if ( 'factura' === $tipo ) {
		if ( ! preg_match( '/^(10|15|17|20)\d{9}$/', $doc ) ) {
			$errors->add( 'denver_documento', 'Para factura escribe un RUC de 11 dígitos que empiece con 10, 15, 17 o 20.' );
		}
		if ( '' === $razon ) {
			$errors->add( 'denver_razon_social', 'Para factura escribe la razón social.' );
		}
	} elseif ( 'boleta' === $tipo ) {
		$es_dni = (bool) preg_match( '/^\d{8}$/', $doc );
		$es_ce  = (bool) preg_match( '/^[A-Z0-9]{9,12}$/', $doc );
		if ( ! $es_dni && ! $es_ce ) {
			$errors->add( 'denver_documento', 'Para boleta escribe un DNI de 8 dígitos o un carné de extranjería de 9 a 12 caracteres.' );
		}
	}
}, 10, 2 );
