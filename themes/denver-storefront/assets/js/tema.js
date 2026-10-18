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
