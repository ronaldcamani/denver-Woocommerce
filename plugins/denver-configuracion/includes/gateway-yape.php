<?php
/**
 * Pago con Yape: el cliente paga desde su app y escribe el código de aprobación.
 * El pedido queda "en espera" hasta que la tienda verifica el pago.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	class Denver_Gateway_Yape extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'denver_yape';
			$this->method_title       = 'Yape (código de aprobación)';
			$this->method_description = 'El cliente paga con Yape y escribe el código de aprobación. Tú verificas el pago y completas el pedido.';
			$this->has_fields         = true;
			$this->supports           = array( 'products' );
			$this->init_form_fields();
			$this->init_settings();
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'mostrar_instrucciones' ) );
			add_action( 'woocommerce_email_before_order_table', array( $this, 'instrucciones_en_correo' ), 10, 3 );
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'     => array( 'title' => 'Activar', 'type' => 'checkbox', 'label' => 'Aceptar pagos con Yape', 'default' => 'yes' ),
				'title'       => array( 'title' => 'Título', 'type' => 'text', 'default' => 'Yape' ),
				'description' => array( 'title' => 'Descripción', 'type' => 'textarea', 'default' => 'Paga con Yape y escribe el código de aprobación.' ),
				'numero'      => array( 'title' => 'Número de Yape', 'type' => 'text', 'default' => '902022295', 'description' => 'Confirma que este es el número donde recibes Yape.' ),
				'titular'     => array( 'title' => 'Nombre que ve el cliente en Yape', 'type' => 'text', 'default' => 'DENVER S.A.C.' ),
			);
		}

		public function datos() {
			return array(
				'numero'  => $this->get_option( 'numero' ),
				'titular' => $this->get_option( 'titular' ),
			);
		}

		public function payment_fields() {
			echo wp_kses_post( wpautop( $this->description ) );
			printf(
				'<p>Yape al <strong>%s</strong> a nombre de <strong>%s</strong>.</p><p><label>Código de aprobación <input type="text" name="denver_yape_codigo" maxlength="10" autocomplete="off"></label></p>',
				esc_html( $this->get_option( 'numero' ) ),
				esc_html( $this->get_option( 'titular' ) )
			);
		}

		public function process_payment( $order_id ) {
			$order  = wc_get_order( $order_id );
			$codigo = $order->get_meta( '_denver_yape_codigo' );
			if ( ! $codigo && isset( $_POST['denver_yape_codigo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$codigo = denver_limpiar_codigo( wp_unslash( $_POST['denver_yape_codigo'] ) ); // phpcs:ignore
			}
			$order->update_meta_data( '_denver_yape_codigo', $codigo );
			$order->update_status( 'on-hold', 'Pago con Yape pendiente de verificación. Código de aprobación: ' . $codigo . '.' );
			wc_reduce_stock_levels( $order_id );
			WC()->cart->empty_cart();
			return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
		}

		public function mostrar_instrucciones( $order_id ) {
			$order = wc_get_order( $order_id );
			echo wp_kses_post( $this->texto_instrucciones( $order ) );
		}

		public function instrucciones_en_correo( $order, $sent_to_admin, $plain_text ) {
			if ( $sent_to_admin || $order->get_payment_method() !== $this->id || ! $order->has_status( 'on-hold' ) ) {
				return;
			}
			echo $plain_text ? wp_strip_all_tags( $this->texto_instrucciones( $order ) ) . "\n\n" : wp_kses_post( $this->texto_instrucciones( $order ) ); // phpcs:ignore
		}

		private function texto_instrucciones( $order ) {
			return sprintf(
				'<h2>Cómo completar tu pago con Yape</h2><p>Yapea <strong>%s</strong> al <strong>%s</strong> (%s). Recibimos tu código de aprobación <strong>%s</strong> y verificaremos el pago. Cuando lo confirmemos, prepararemos tu pedido. Si tienes dudas, escríbenos por WhatsApp al 902 022 295.</p>',
				wp_kses_post( wc_price( $order->get_total() ) ),
				esc_html( $this->get_option( 'numero' ) ),
				esc_html( $this->get_option( 'titular' ) ),
				esc_html( $order->get_meta( '_denver_yape_codigo' ) )
			);
		}
	}

	add_filter( 'woocommerce_payment_gateways', function ( $g ) {
		$g[] = 'Denver_Gateway_Yape';
		return $g;
	} );
} );

function denver_limpiar_codigo( $valor ) {
	return strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $valor ) );
}

/** Valida el código y lo guarda en el pedido (checkout de bloques). */
add_action( 'woocommerce_rest_checkout_process_payment_with_context', function ( $context ) {
	if ( 'denver_yape' !== $context->payment_method ) {
		return;
	}
	$codigo = denver_limpiar_codigo( $context->payment_data['denver_yape_codigo'] ?? '' );
	if ( ! preg_match( '/^[A-Z0-9]{3,10}$/', $codigo ) ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
			'denver_yape_codigo',
			'Escribe el código de aprobación que aparece en tu constancia de Yape.',
			400
		);
	}
	$context->order->update_meta_data( '_denver_yape_codigo', $codigo );
	$context->order->save();
}, 5 );

/** Muestra el código en el panel del pedido. */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$codigo = $order->get_meta( '_denver_yape_codigo' );
	if ( $codigo ) {
		echo '<p><strong>Código de aprobación Yape:</strong> ' . esc_html( $codigo ) . '</p>';
	}
} );

/** Integración con el checkout de bloques. */
add_action( 'woocommerce_blocks_loaded', function () {
	if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	class Denver_Yape_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
		protected $name = 'denver_yape';

		public function initialize() {
			$this->settings = get_option( 'woocommerce_denver_yape_settings', array() );
		}

		public function is_active() {
			return 'yes' === ( $this->settings['enabled'] ?? 'yes' );
		}

		public function get_payment_method_script_handles() {
			wp_register_script( 'denver-yape-blocks', DENVER_URL . 'assets/yape-blocks.js',
				array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities' ), DENVER_VERSION, true );
			return array( 'denver-yape-blocks' );
		}

		public function get_payment_method_data() {
			return array(
				'title'       => $this->settings['title'] ?? 'Yape',
				'description' => $this->settings['description'] ?? '',
				'numero'      => $this->settings['numero'] ?? '902022295',
				'titular'     => $this->settings['titular'] ?? 'DENVER S.A.C.',
				'supports'    => array( 'products' ),
			);
		}
	}

	add_action( 'woocommerce_blocks_payment_method_type_registration', function ( $registry ) {
		$registry->register( new Denver_Yape_Blocks() );
	} );
} );

/** El aviso de nuevo pedido a la tienda incluye el código para verificar el pago. */
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin, $plain_text ) {
	$codigo = $order->get_meta( '_denver_yape_codigo' );
	if ( ! $sent_to_admin || ! $codigo ) {
		return;
	}
	$linea = 'Código de aprobación Yape: ' . $codigo;
	echo $plain_text ? "\n" . esc_html( $linea ) . "\n" : '<p><strong>' . esc_html( $linea ) . '</strong></p>'; // phpcs:ignore
}, 10, 3 );
