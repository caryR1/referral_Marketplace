( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var select = document.getElementById( 'gemz_payment_method' );
		if ( ! select ) return;

		var groups = document.querySelectorAll( '.gemz-payment-fields' );

		function updateVisibility() {
			var method = select.value;
			groups.forEach( function ( group ) {
				group.style.display = ( group.getAttribute( 'data-method' ) === method ) ? '' : 'none';
			} );
		}

		select.addEventListener( 'change', updateVisibility );
	} );
} )();
