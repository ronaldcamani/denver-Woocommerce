( function () {
	'use strict';
	// El delivery va por inDrive y su costo varía: en vez de "Gratis" se muestra "Por coordinar".
	var observador;
	function corregir() {
		if ( observador ) { observador.disconnect(); }
		document.querySelectorAll( '.wc-block-components-radio-control__option, .wc-block-components-totals-shipping' ).forEach( function ( caja ) {
			if ( caja.textContent.indexOf( 'inDrive' ) === -1 ) { return; }
			caja.querySelectorAll( '.wc-block-components-radio-control__secondary-label, .wc-block-components-totals-item__value' ).forEach( function ( v ) {
				if ( /gratis/i.test( v.textContent ) ) { v.textContent = 'Por coordinar'; }
			} );
		} );
		if ( observador ) { observador.observe( document.body, { childList: true, subtree: true, characterData: true } ); }
	}
	if ( ! document.body.classList.contains( 'woocommerce-checkout' ) && ! document.body.classList.contains( 'woocommerce-cart' ) ) { return; }
	observador = new MutationObserver( corregir );
	corregir();
}() );

( function () {
	'use strict';
	// Menú de categorías de la cabecera.
	var boton = document.querySelector( '.categorias__boton' );
	var lista = document.getElementById( 'menu-categorias' );
	if ( ! boton || ! lista ) { return; }
	function cerrar() { lista.hidden = true; boton.setAttribute( 'aria-expanded', 'false' ); }
	boton.addEventListener( 'click', function ( e ) {
		e.stopPropagation();
		var abrir = lista.hidden;
		lista.hidden = ! abrir;
		boton.setAttribute( 'aria-expanded', abrir ? 'true' : 'false' );
	} );
	document.addEventListener( 'click', function ( e ) { if ( ! lista.contains( e.target ) ) { cerrar(); } } );
	document.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { cerrar(); boton.focus(); } } );
} )();
