<?php
/**
 * Libro de Reclamaciones virtual (Ley N.° 29571, Código de Protección y Defensa del Consumidor).
 *
 * - Formulario público con el shortcode [denver_libro_reclamaciones].
 * - Hoja numerada por año (0001-2026), guardada como "Reclamación" en el panel.
 * - Copia por correo al consumidor y a la tienda.
 * - La tienda responde desde el panel; el consumidor recibe la respuesta por correo.
 * - Plazo de respuesta: 15 días hábiles (lunes a viernes; no descuenta feriados).
 */

defined( 'ABSPATH' ) || exit;

const DENVER_RECLAMO_CPT   = 'denver_reclamo';
const DENVER_RECLAMO_PLAZO = 15; // días hábiles.
const DENVER_RAZON_SOCIAL  = 'DENVER S.A.C.';
const DENVER_RUC           = '20610817433';
const DENVER_DIRECCION     = 'Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa';
const DENVER_CORREO_TIENDA = 'libreriadenver@gmail.com';

/* ------------------------------------------------------------------ Tipo de contenido */

add_action( 'init', function () {
	$cap = 'manage_woocommerce';
	register_post_type( DENVER_RECLAMO_CPT, array(
		'labels'       => array(
			'name'               => 'Reclamaciones',
			'singular_name'      => 'Reclamación',
			'menu_name'          => 'Reclamaciones',
			'all_items'          => 'Todas las reclamaciones',
			'edit_item'          => 'Reclamación',
			'search_items'       => 'Buscar reclamaciones',
			'not_found'          => 'Aún no hay reclamaciones.',
			'not_found_in_trash' => 'No hay reclamaciones en la papelera.',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_position' => 56,
		'menu_icon'    => 'dashicons-book-alt',
		'supports'     => array( 'title' ),
		'capabilities' => array(
			'edit_post' => $cap, 'read_post' => $cap, 'delete_post' => $cap,
			'edit_posts' => $cap, 'edit_others_posts' => $cap, 'delete_posts' => $cap,
			'delete_others_posts' => $cap, 'publish_posts' => $cap, 'read_private_posts' => $cap,
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap' => false,
	) );
} );

/* ------------------------------------------------------------------ Utilidades */

/** Siguiente número de hoja del año, sin repetirse aunque lleguen dos a la vez. */
function denver_reclamo_numero() {
	global $wpdb;
	$anio = wp_date( 'Y' );
	$clave = 'denver_reclamos_contador_' . $anio;
	add_option( $clave, 0, '', 'no' );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = LAST_INSERT_ID(option_value + 1) WHERE option_name = %s", $clave ) ); // phpcs:ignore
	$n = (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ); // phpcs:ignore
	return sprintf( '%04d-%s', $n, $anio );
}

/** Suma días hábiles (lunes a viernes) a partir del día siguiente. */
function denver_sumar_dias_habiles( DateTimeImmutable $desde, $dias ) {
	$d = $desde;
	while ( $dias > 0 ) {
		$d = $d->modify( '+1 day' );
		if ( (int) $d->format( 'N' ) < 6 ) {
			$dias--;
		}
	}
	return $d;
}

function denver_meta( $id, $clave ) {
	return get_post_meta( $id, '_dr_' . $clave, true );
}

function denver_fecha_larga( $ymd ) {
	return $ymd ? wp_date( 'j \d\e F \d\e Y', strtotime( $ymd . ' 12:00:00' ) ) : '';
}

/** Marco HTML sencillo para los correos. */
function denver_correo_html( $titulo, $contenido ) {
	return '<div style="background:#F4F6FC;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;color:#0A1B5C">'
		. '<div style="max-width:620px;margin:0 auto;background:#fff">'
		. '<div style="background:#0A1B5C;color:#fff;padding:18px 24px;font-size:20px;font-weight:bold">DENVER LIBRERIA</div>'
		. '<div style="padding:24px;line-height:1.5"><h2 style="margin:0 0 16px;font-size:20px">' . esc_html( $titulo ) . '</h2>' . $contenido . '</div>'
		. '<div style="padding:16px 24px;background:#F4F6FC;font-size:13px">' . esc_html( DENVER_RAZON_SOCIAL ) . ' &middot; RUC ' . esc_html( DENVER_RUC ) . ' &middot; ' . esc_html( DENVER_DIRECCION ) . '</div>'
		. '</div></div>';
}

function denver_enviar_correo( $para, $asunto, $html, $responder_a = '' ) {
	$cabeceras = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $responder_a ) {
		$cabeceras[] = 'Reply-To: ' . $responder_a;
	}
	return wp_mail( $para, $asunto, $html, $cabeceras );
}

/** Tabla con los datos de la hoja, para correos y para el panel. */
function denver_hoja_html( $id ) {
	$f = function ( $k ) use ( $id ) {
		return esc_html( denver_meta( $id, $k ) );
	};
	$fila = function ( $et, $v ) {
		return '<tr><td style="padding:6px 10px;background:#F4F6FC;width:38%;vertical-align:top"><strong>' . esc_html( $et ) . '</strong></td><td style="padding:6px 10px;vertical-align:top">' . $v . '</td></tr>';
	};
	$t  = '<table style="width:100%;border-collapse:collapse;font-size:14px">';
	$t .= $fila( 'Hoja de reclamación N.°', $f( 'numero' ) );
	$t .= $fila( 'Fecha', esc_html( denver_fecha_larga( denver_meta( $id, 'fecha' ) ) ) );
	$t .= $fila( 'Consumidor', $f( 'nombres' ) . ' ' . $f( 'apellidos' ) );
	$t .= $fila( 'Documento', $f( 'tipo_doc' ) . ' ' . $f( 'num_doc' ) );
	$t .= $fila( 'Domicilio', $f( 'domicilio' ) );
	$t .= $fila( 'Teléfono / correo', $f( 'telefono' ) . ' / ' . $f( 'email' ) );
	if ( denver_meta( $id, 'apoderado' ) ) {
		$t .= $fila( 'Padre, madre o apoderado (consumidor menor de edad)', $f( 'apoderado' ) );
	}
	$t .= $fila( 'Bien contratado', ucfirst( $f( 'bien_tipo' ) ) . ': ' . $f( 'bien_desc' ) );
	if ( '' !== denver_meta( $id, 'monto' ) ) {
		$t .= $fila( 'Monto reclamado', 'S/ ' . $f( 'monto' ) );
	}
	$t .= $fila( 'Tipo', ucfirst( $f( 'tipo' ) ) );
	$t .= $fila( 'Detalle', nl2br( $f( 'detalle' ) ) );
	$t .= $fila( 'Pedido del consumidor', nl2br( $f( 'pedido' ) ) );
	$t .= $fila( 'Proveedor', esc_html( DENVER_RAZON_SOCIAL . ' — RUC ' . DENVER_RUC . ' — ' . DENVER_DIRECCION ) );
	return $t . '</table>';
}

/* ------------------------------------------------------------------ Formulario público */

add_shortcode( 'denver_libro_reclamaciones', function () {
	$e   = $GLOBALS['denver_reclamo'] ?? array();
	$v   = $e['valores'] ?? array();
	$val = function ( $k ) use ( $v ) {
		return isset( $v[ $k ] ) ? esc_attr( $v[ $k ] ) : '';
	};
	ob_start();

	// Constancia después de enviar.
	if ( ! empty( $_GET['reclamo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$datos = get_transient( 'denver_reclamo_' . sanitize_key( wp_unslash( $_GET['reclamo'] ) ) ); // phpcs:ignore
		if ( $datos ) {
			?>
			<div class="reclamo__ok" role="status">
				<h2>Tu hoja de reclamación fue registrada</h2>
				<p>Número de hoja: <strong><?php echo esc_html( $datos['numero'] ); ?></strong></p>
				<p>Enviamos una copia a <strong><?php echo esc_html( $datos['email'] ); ?></strong>. Revisa también tu carpeta de spam.</p>
				<p>Responderemos tu reclamo a más tardar el <strong><?php echo esc_html( denver_fecha_larga( $datos['vence'] ) ); ?></strong> (15 días hábiles).</p>
			</div>
			<?php
			return ob_get_clean();
		}
	}
	?>
	<div class="reclamo">
		<p class="reclamo__aviso">Conforme a lo establecido en el Código de Protección y Defensa del Consumidor (Ley N.° 29571), este establecimiento cuenta con un Libro de Reclamaciones a tu disposición.</p>
		<div class="reclamo__proveedor">
			<strong><?php echo esc_html( DENVER_RAZON_SOCIAL ); ?></strong> · RUC <?php echo esc_html( DENVER_RUC ); ?><br>
			<?php echo esc_html( DENVER_DIRECCION ); ?>
		</div>

		<?php if ( ! empty( $e['errores'] ) ) : ?>
			<div class="reclamo__errores" role="alert">
				<p><strong>Revisa estos datos:</strong></p>
				<ul><?php foreach ( $e['errores'] as $msg ) : ?><li><?php echo esc_html( $msg ); ?></li><?php endforeach; ?></ul>
			</div>
		<?php endif; ?>

		<form class="reclamo__form" method="post" action="" novalidate>
			<?php wp_nonce_field( 'denver_reclamo', 'dr_nonce' ); ?>
			<input type="hidden" name="dr_ts" value="<?php echo esc_attr( time() ); ?>">
			<div class="reclamo__trampa" aria-hidden="true"><label>No llenar este campo <input type="text" name="dr_web" tabindex="-1" autocomplete="off"></label></div>

			<fieldset>
				<legend>Identificación del consumidor</legend>
				<div class="reclamo__fila">
					<p><label for="dr_nombres">Nombres *</label><input id="dr_nombres" name="nombres" type="text" required autocomplete="given-name" value="<?php echo $val( 'nombres' ); // phpcs:ignore ?>"></p>
					<p><label for="dr_apellidos">Apellidos *</label><input id="dr_apellidos" name="apellidos" type="text" required autocomplete="family-name" value="<?php echo $val( 'apellidos' ); // phpcs:ignore ?>"></p>
				</div>
				<div class="reclamo__fila">
					<p><label for="dr_tipo_doc">Tipo de documento *</label>
						<select id="dr_tipo_doc" name="tipo_doc">
							<?php foreach ( array( 'DNI', 'CE' => 'Carné de extranjería', 'PASAPORTE' => 'Pasaporte' ) as $k => $et ) : $k = is_int( $k ) ? $et : $k; ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $v['tipo_doc'] ?? 'DNI', $k ); ?>><?php echo esc_html( $et ); ?></option>
							<?php endforeach; ?>
						</select></p>
					<p><label for="dr_num_doc">Número de documento *</label><input id="dr_num_doc" name="num_doc" type="text" required autocomplete="off" value="<?php echo $val( 'num_doc' ); // phpcs:ignore ?>"></p>
				</div>
				<p><label for="dr_domicilio">Domicilio *</label><input id="dr_domicilio" name="domicilio" type="text" required autocomplete="street-address" value="<?php echo $val( 'domicilio' ); // phpcs:ignore ?>"></p>
				<div class="reclamo__fila">
					<p><label for="dr_telefono">Teléfono *</label><input id="dr_telefono" name="telefono" type="tel" required autocomplete="tel" value="<?php echo $val( 'telefono' ); // phpcs:ignore ?>"></p>
					<p><label for="dr_email">Correo electrónico *</label><input id="dr_email" name="email" type="email" required autocomplete="email" value="<?php echo $val( 'email' ); // phpcs:ignore ?>"></p>
				</div>
				<p class="reclamo__check"><label><input type="checkbox" name="menor" value="1" <?php checked( ! empty( $v['menor'] ) ); ?>> Soy menor de edad</label></p>
				<p><label for="dr_apoderado">Nombre del padre, madre o apoderado (solo si eres menor de edad)</label><input id="dr_apoderado" name="apoderado" type="text" value="<?php echo $val( 'apoderado' ); // phpcs:ignore ?>"></p>
			</fieldset>

			<fieldset>
				<legend>Identificación del bien contratado</legend>
				<p class="reclamo__radios"><span>Es un *</span>
					<label><input type="radio" name="bien_tipo" value="producto" <?php checked( $v['bien_tipo'] ?? 'producto', 'producto' ); ?>> Producto</label>
					<label><input type="radio" name="bien_tipo" value="servicio" <?php checked( $v['bien_tipo'] ?? '', 'servicio' ); ?>> Servicio</label></p>
				<div class="reclamo__fila">
					<p><label for="dr_bien_desc">Descripción (por ejemplo, número de pedido) *</label><input id="dr_bien_desc" name="bien_desc" type="text" required value="<?php echo $val( 'bien_desc' ); // phpcs:ignore ?>"></p>
					<p><label for="dr_monto">Monto reclamado en S/ (opcional)</label><input id="dr_monto" name="monto" type="text" inputmode="decimal" value="<?php echo $val( 'monto' ); // phpcs:ignore ?>"></p>
				</div>
			</fieldset>

			<fieldset>
				<legend>Detalle de la reclamación y pedido del consumidor</legend>
				<p class="reclamo__radios"><span>Tipo *</span>
					<label><input type="radio" name="tipo" value="reclamo" <?php checked( $v['tipo'] ?? 'reclamo', 'reclamo' ); ?>> Reclamo <small>(disconformidad con el producto o servicio)</small></label>
					<label><input type="radio" name="tipo" value="queja" <?php checked( $v['tipo'] ?? '', 'queja' ); ?>> Queja <small>(malestar por la atención, sin relación con el producto)</small></label></p>
				<p><label for="dr_detalle">Detalle *</label><textarea id="dr_detalle" name="detalle" rows="5" required maxlength="2000"><?php echo esc_textarea( $v['detalle'] ?? '' ); ?></textarea></p>
				<p><label for="dr_pedido">Pedido del consumidor *</label><textarea id="dr_pedido" name="pedido" rows="3" required maxlength="1000"><?php echo esc_textarea( $v['pedido'] ?? '' ); ?></textarea></p>
			</fieldset>

			<p class="reclamo__check"><label><input type="checkbox" name="consentimiento" value="1" <?php checked( ! empty( $v['consentimiento'] ) ); ?>> Acepto que DENVER S.A.C. trate mis datos para atender este reclamo, según la <a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>">Política de privacidad</a>. *</label></p>
			<p class="reclamo__nota">La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI. El proveedor debe dar respuesta al reclamo en un plazo no mayor a quince (15) días hábiles.</p>
			<p><button type="submit" name="dr_enviar" value="1" class="boton">Enviar hoja de reclamación</button></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
} );

/* ------------------------------------------------------------------ Procesar el envío */

add_action( 'template_redirect', function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['dr_enviar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$p = wp_unslash( $_POST ); // phpcs:ignore
	$limpio = function ( $k, $max = 200 ) use ( $p ) {
		return mb_substr( trim( sanitize_text_field( $p[ $k ] ?? '' ) ), 0, $max );
	};
	$v = array(
		'nombres' => $limpio( 'nombres', 80 ), 'apellidos' => $limpio( 'apellidos', 80 ),
		'tipo_doc' => $limpio( 'tipo_doc', 12 ), 'num_doc' => strtoupper( preg_replace( '/\s+/', '', $limpio( 'num_doc', 20 ) ) ),
		'domicilio' => $limpio( 'domicilio', 200 ), 'telefono' => $limpio( 'telefono', 20 ),
		'email' => sanitize_email( $p['email'] ?? '' ), 'menor' => ! empty( $p['menor'] ) ? '1' : '',
		'apoderado' => $limpio( 'apoderado', 120 ), 'bien_tipo' => $limpio( 'bien_tipo', 10 ),
		'bien_desc' => $limpio( 'bien_desc', 200 ), 'monto' => str_replace( ',', '.', $limpio( 'monto', 12 ) ),
		'tipo' => $limpio( 'tipo', 10 ), 'detalle' => mb_substr( trim( sanitize_textarea_field( $p['detalle'] ?? '' ) ), 0, 2000 ),
		'pedido' => mb_substr( trim( sanitize_textarea_field( $p['pedido'] ?? '' ) ), 0, 1000 ),
		'consentimiento' => ! empty( $p['consentimiento'] ) ? '1' : '',
	);
	$err = array();

	if ( ! empty( $p['dr_web'] ) ) { // Trampa para robots: se ignora en silencio.
		return;
	}
	if ( ! wp_verify_nonce( $p['dr_nonce'] ?? '', 'denver_reclamo' ) ) {
		$err[] = 'La página caducó. Recárgala e inténtalo de nuevo.';
	}
	$ip = md5( $_SERVER['REMOTE_ADDR'] ?? '' );
	$intentos = (int) get_transient( 'denver_reclamo_ip_' . $ip );
	if ( $intentos >= 5 ) {
		$err[] = 'Recibimos muchos envíos desde tu conexión. Inténtalo más tarde o escríbenos por WhatsApp al 902 022 295.';
	}
	if ( mb_strlen( $v['nombres'] ) < 2 ) { $err[] = 'Escribe tus nombres.'; }
	if ( mb_strlen( $v['apellidos'] ) < 2 ) { $err[] = 'Escribe tus apellidos.'; }
	$reglas = array( 'DNI' => '/^\d{8}$/', 'CE' => '/^[A-Z0-9]{9,12}$/', 'PASAPORTE' => '/^[A-Z0-9]{6,12}$/' );
	if ( ! isset( $reglas[ $v['tipo_doc'] ] ) || ! preg_match( $reglas[ $v['tipo_doc'] ], $v['num_doc'] ) ) {
		$err[] = 'Revisa tu documento: el DNI tiene 8 dígitos, el carné de extranjería de 9 a 12 caracteres y el pasaporte de 6 a 12.';
	}
	if ( mb_strlen( $v['domicilio'] ) < 5 ) { $err[] = 'Escribe tu domicilio.'; }
	if ( ! preg_match( '/^[0-9+() -]{6,20}$/', $v['telefono'] ) ) { $err[] = 'Escribe un teléfono válido.'; }
	if ( ! is_email( $v['email'] ) ) { $err[] = 'Escribe un correo electrónico válido para enviarte la copia.'; }
	if ( $v['menor'] && mb_strlen( $v['apoderado'] ) < 3 ) { $err[] = 'Si eres menor de edad, escribe el nombre de tu padre, madre o apoderado.'; }
	if ( ! in_array( $v['bien_tipo'], array( 'producto', 'servicio' ), true ) ) { $err[] = 'Indica si es un producto o un servicio.'; }
	if ( mb_strlen( $v['bien_desc'] ) < 3 ) { $err[] = 'Describe el producto o servicio (por ejemplo, el número de pedido).'; }
	if ( '' !== $v['monto'] && ! preg_match( '/^\d{1,8}(\.\d{1,2})?$/', $v['monto'] ) ) { $err[] = 'El monto reclamado debe ser un número, por ejemplo 25.90.'; }
	if ( ! in_array( $v['tipo'], array( 'reclamo', 'queja' ), true ) ) { $err[] = 'Elige si es un reclamo o una queja.'; }
	if ( mb_strlen( $v['detalle'] ) < 10 ) { $err[] = 'Cuéntanos con más detalle qué ocurrió.'; }
	if ( mb_strlen( $v['pedido'] ) < 5 ) { $err[] = 'Indica qué solicitas.'; }
	if ( ! $v['consentimiento'] ) { $err[] = 'Debes aceptar el tratamiento de tus datos para registrar el reclamo.'; }

	if ( $err ) {
		$GLOBALS['denver_reclamo'] = array( 'errores' => $err, 'valores' => $v );
		return;
	}

	set_transient( 'denver_reclamo_ip_' . $ip, $intentos + 1, HOUR_IN_SECONDS );

	$numero = denver_reclamo_numero();
	$hoy    = new DateTimeImmutable( 'now', wp_timezone() );
	$vence  = denver_sumar_dias_habiles( $hoy, DENVER_RECLAMO_PLAZO );
	$id     = wp_insert_post( array(
		'post_type'   => DENVER_RECLAMO_CPT,
		'post_status' => 'publish',
		'post_title'  => 'Hoja N.° ' . $numero . ' — ' . $v['apellidos'] . ', ' . $v['nombres'],
	) );
	if ( ! $id || is_wp_error( $id ) ) {
		$GLOBALS['denver_reclamo'] = array( 'errores' => array( 'No pudimos registrar tu hoja. Inténtalo otra vez o escríbenos por WhatsApp al 902 022 295.' ), 'valores' => $v );
		return;
	}
	foreach ( $v as $k => $valor ) {
		update_post_meta( $id, '_dr_' . $k, $valor );
	}
	update_post_meta( $id, '_dr_numero', $numero );
	update_post_meta( $id, '_dr_fecha', $hoy->format( 'Y-m-d' ) );
	update_post_meta( $id, '_dr_vence', $vence->format( 'Y-m-d' ) );
	update_post_meta( $id, '_dr_estado', 'pendiente' );

	$hoja = denver_hoja_html( $id );
	denver_enviar_correo(
		$v['email'],
		'Constancia de tu hoja de reclamación N.° ' . $numero . ' — DENVER LIBRERIA',
		denver_correo_html( 'Recibimos tu hoja de reclamación', '<p>Hola ' . esc_html( $v['nombres'] ) . ', registramos tu ' . esc_html( $v['tipo'] ) . '. Esta es tu copia. Te responderemos a más tardar el <strong>' . esc_html( denver_fecha_larga( $vence->format( 'Y-m-d' ) ) ) . '</strong> (15 días hábiles).</p>' . $hoja )
	);
	denver_enviar_correo(
		DENVER_CORREO_TIENDA,
		'Nuevo ' . $v['tipo'] . ' — Hoja N.° ' . $numero . ' (responder antes del ' . $vence->format( 'd/m/Y' ) . ')',
		denver_correo_html( 'Nueva hoja de reclamación', '<p>Respóndela desde el panel: <strong>Reclamaciones</strong>. Vence el <strong>' . esc_html( denver_fecha_larga( $vence->format( 'Y-m-d' ) ) ) . '</strong>.</p>' . $hoja . '<p><a href="' . esc_url( admin_url( 'post.php?post=' . $id . '&action=edit' ) ) . '">Abrir en el panel</a></p>' ),
		$v['email']
	);

	$token = wp_generate_password( 20, false );
	set_transient( 'denver_reclamo_' . $token, array( 'numero' => $numero, 'email' => $v['email'], 'vence' => $vence->format( 'Y-m-d' ) ), HOUR_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'reclamo', $token, get_permalink() ) );
	exit;
} );

/* ------------------------------------------------------------------ Panel */

add_filter( 'manage_' . DENVER_RECLAMO_CPT . '_posts_columns', function () {
	return array(
		'cb' => '<input type="checkbox">', 'title' => 'Hoja', 'dr_consumidor' => 'Consumidor', 'dr_tipo' => 'Tipo',
		'dr_estado' => 'Estado', 'dr_vence' => 'Responder antes del',
	);
} );
add_action( 'manage_' . DENVER_RECLAMO_CPT . '_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'dr_consumidor':
			echo esc_html( denver_meta( $id, 'nombres' ) . ' ' . denver_meta( $id, 'apellidos' ) );
			break;
		case 'dr_tipo':
			echo esc_html( ucfirst( denver_meta( $id, 'tipo' ) ) );
			break;
		case 'dr_estado':
			echo 'respondido' === denver_meta( $id, 'estado' ) ? '<span style="color:#0a7a2f;font-weight:600">Respondido</span>' : '<span style="color:#b3261e;font-weight:600">Pendiente</span>';
			break;
		case 'dr_vence':
			$vence = denver_meta( $id, 'vence' );
			$vencido = 'respondido' !== denver_meta( $id, 'estado' ) && $vence < wp_date( 'Y-m-d' );
			echo '<span' . ( $vencido ? ' style="color:#b3261e;font-weight:600"' : '' ) . '>' . esc_html( $vence ? wp_date( 'd/m/Y', strtotime( $vence . ' 12:00:00' ) ) : '' ) . ( $vencido ? ' (vencido)' : '' ) . '</span>';
			break;
	}
}, 10, 2 );

add_action( 'add_meta_boxes_' . DENVER_RECLAMO_CPT, function () {
	remove_meta_box( 'submitdiv', DENVER_RECLAMO_CPT, 'side' );
	add_meta_box( 'dr_hoja', 'Hoja de reclamación', function ( $post ) {
		echo denver_hoja_html( $post->ID ); // phpcs:ignore
	}, DENVER_RECLAMO_CPT, 'normal', 'high' );
	add_meta_box( 'dr_respuesta', 'Respuesta de la tienda', function ( $post ) {
		wp_nonce_field( 'dr_respuesta', 'dr_resp_nonce' );
		$estado = denver_meta( $post->ID, 'estado' );
		$vence  = denver_meta( $post->ID, 'vence' );
		echo '<p>Plazo máximo: <strong>' . esc_html( denver_fecha_larga( $vence ) ) . '</strong> (15 días hábiles). Estado: <strong>' . ( 'respondido' === $estado ? 'Respondido el ' . esc_html( denver_fecha_larga( denver_meta( $post->ID, 'respondido' ) ) ) : 'Pendiente' ) . '</strong></p>';
		echo '<textarea name="dr_respuesta" rows="8" style="width:100%">' . esc_textarea( denver_meta( $post->ID, 'respuesta' ) ) . '</textarea>';
		if ( 'respondido' !== $estado ) {
			echo '<p><label><input type="checkbox" name="dr_enviar_respuesta" value="1"> Marcar como respondido y enviar esta respuesta al correo del consumidor</label></p>';
		}
		submit_button( 'Guardar', 'primary', 'save', false );
	}, DENVER_RECLAMO_CPT, 'normal', 'default' );
} );

add_action( 'save_post_' . DENVER_RECLAMO_CPT, function ( $id ) {
	if ( empty( $_POST['dr_resp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dr_resp_nonce'] ) ), 'dr_respuesta' ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$respuesta = sanitize_textarea_field( wp_unslash( $_POST['dr_respuesta'] ?? '' ) );
	update_post_meta( $id, '_dr_respuesta', $respuesta );
	if ( ! empty( $_POST['dr_enviar_respuesta'] ) && '' !== trim( $respuesta ) && 'respondido' !== denver_meta( $id, 'estado' ) ) {
		update_post_meta( $id, '_dr_estado', 'respondido' );
		update_post_meta( $id, '_dr_respondido', wp_date( 'Y-m-d' ) );
		denver_enviar_correo(
			denver_meta( $id, 'email' ),
			'Respuesta a tu hoja de reclamación N.° ' . denver_meta( $id, 'numero' ) . ' — DENVER LIBRERIA',
			denver_correo_html( 'Respuesta a tu hoja de reclamación', '<p>Hola ' . esc_html( denver_meta( $id, 'nombres' ) ) . ', esta es la respuesta de DENVER S.A.C. a tu ' . esc_html( denver_meta( $id, 'tipo' ) ) . ' N.° ' . esc_html( denver_meta( $id, 'numero' ) ) . ':</p><blockquote style="margin:16px 0;padding:12px 16px;background:#F4F6FC">' . nl2br( esc_html( $respuesta ) ) . '</blockquote><p>Si no estás conforme, puedes acudir al INDECOPI.</p>' ),
			DENVER_CORREO_TIENDA
		);
	}
} );
