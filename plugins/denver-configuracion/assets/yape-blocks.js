( function () {
	var el = window.wp.element.createElement;
	var useState = window.wp.element.useState;
	var useEffect = window.wp.element.useEffect;
	var decode = window.wp.htmlEntities.decodeEntities;
	var datos = window.wc.wcSettings.getSetting( 'denver_yape_data', {} );
	var titulo = decode( datos.title || 'Yape' );

	function Contenido( props ) {
		var estado = useState( '' );
		var codigo = estado[ 0 ];
		var setCodigo = estado[ 1 ];
		var onPaymentSetup = props.eventRegistration.onPaymentSetup;
		var total = props.billing.cartTotal.value / Math.pow( 10, props.billing.currency.minorUnit );

		useEffect( function () {
			return onPaymentSetup( function () {
				var limpio = codigo.replace( /[^A-Za-z0-9]/g, '' ).toUpperCase();
				if ( ! /^[A-Z0-9]{3,10}$/.test( limpio ) ) {
					return { type: 'error', message: 'Escribe el código de aprobación que aparece en tu constancia de Yape.' };
				}
				return { type: 'success', meta: { paymentMethodData: { denver_yape_codigo: limpio } } };
			} );
		}, [ codigo, onPaymentSetup ] );

		return el( 'div', { className: 'denver-yape' },
			el( 'p', null, decode( datos.description || '' ) ),
			el( 'ol', null,
				el( 'li', null, 'Abre Yape y yapea S/ ' + total.toFixed( 2 ) + ' al ', el( 'strong', null, datos.numero ), ' (' + datos.titular + ').' ),
				el( 'li', null, 'Copia el código de aprobación de tu constancia.' ),
				el( 'li', null, 'Escríbelo aquí y presiona "Realizar el pedido".' )
			),
			el( 'label', { className: 'denver-yape__campo' },
				'Código de aprobación ',
				el( 'input', {
					type: 'text', value: codigo, maxLength: 10, autoComplete: 'off',
					onChange: function ( e ) { setCodigo( e.target.value ); },
					style: { display: 'block', width: '100%', fontSize: '16px', padding: '0.6rem', marginTop: '0.25rem' }
				} )
			)
		);
	}

	window.wc.wcBlocksRegistry.registerPaymentMethod( {
		name: 'denver_yape',
		label: el( 'span', null, titulo ),
		ariaLabel: titulo,
		content: el( Contenido ),
		edit: el( Contenido ),
		canMakePayment: function () { return true; },
		supports: { features: datos.supports || [ 'products' ] },
	} );
} )();
