( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'gemz-agent-signup-form' );
		if ( ! form ) return;

		var msg = form.querySelector( '.gemz-form-message' );
		var params = new URLSearchParams( window.location.search );
		var ref = params.get( 'ref' ) || '';

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var btn = form.querySelector( '.gemz-btn' );
			btn.disabled = true;
			btn.textContent = 'Creating your account...';
			msg.textContent = '';
			msg.className = 'gemz-form-message';

			fetch( gemzAgentSignup.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( {
					full_name: form.full_name.value,
					email: form.email.value,
					password: form.password.value,
					ref: ref
				} )
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					if ( data && data.success ) {
						form.style.display = 'none';
						msg.textContent = 'Welcome aboard! Your referral code is ' + data.referral_code + '. Check your email, then log in to see your dashboard.';
						msg.classList.add( 'grc-success' );
					} else {
						btn.disabled = false;
						btn.textContent = 'Become an Agent';
						msg.textContent = ( data && data.message ) ? data.message : 'Something went wrong - please try again.';
						msg.classList.add( 'grc-error' );
					}
				} )
				.catch( function () {
					btn.disabled = false;
					btn.textContent = 'Become an Agent';
					msg.textContent = 'Something went wrong - please try again.';
					msg.classList.add( 'grc-error' );
				} );
		} );
	} );
} )();
