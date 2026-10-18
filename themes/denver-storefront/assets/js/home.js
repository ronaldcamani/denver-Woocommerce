( function () {
	'use strict';
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Los productos entran suavemente al aparecer (una sola vez).
	var partes = document.querySelectorAll( '.vitrina ul.products li.product, .banner__cats li, .confianza li' );
	if ( 'IntersectionObserver' in window && ! reduce ) {
		var io = new IntersectionObserver( function ( entradas ) {
			entradas.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					e.target.classList.add( 'visto' );
					io.unobserve( e.target );
				}
			} );
		}, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 } );
		partes.forEach( function ( el, i ) {
			el.style.setProperty( '--retraso', ( ( i % 4 ) * 70 ) + 'ms' );
			io.observe( el );
		} );
	} else {
		partes.forEach( function ( el ) { el.classList.add( 'visto' ); } );
	}
} )();
