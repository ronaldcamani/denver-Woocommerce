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

	// Calculadora de copias: 6 copias por S/ 0.30 (S/ 0.05 cada una).
	var form = document.querySelector( '[data-calculadora]' );
	if ( form ) {
		var campo = form.querySelector( 'input' );
		var total = form.querySelector( '[data-total]' );
		var calcular = function () {
			var n = Math.max( 0, Math.min( 10000, parseInt( campo.value, 10 ) || 0 ) );
			total.textContent = 'S/ ' + ( n * 0.05 ).toFixed( 2 );
		};
		campo.addEventListener( 'input', calcular );
		form.addEventListener( 'submit', function ( e ) { e.preventDefault(); } );
		calcular();
	}
} )();
