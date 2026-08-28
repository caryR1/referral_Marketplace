( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'gemz-agent-login-form' );
		if ( ! form ) return;

		var msg = form.querySelector( '.gemz-form-message' );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var btn = form.querySelector( '.gemz-btn' );
			btn.disabled = true;
			btn.textContent = 'Signing in...';
			msg.textContent = '';
			msg.className = 'gemz-form-message';

			fetch( gemzAgentLogin.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				credentials: 'same-origin',
				body: JSON.stringify( {
					email: form.email.value,
					password: form.password.value
				} )
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					if ( data && data.success ) {
						btn.textContent = 'Signed in - redirecting...';
						window.location.href = data.redirect || gemzAgentLogin.dashboardUrl;
					} else {
						btn.disabled = false;
						btn.textContent = 'Sign In';
						msg.classList.add( 'grc-error' );
						msg.textContent = ( data && data.message ) ? data.message : 'Invalid email or password.';
					}
				} )
				.catch( function () {
					btn.disabled = false;
					btn.textContent = 'Sign In';
					msg.classList.add( 'grc-error' );
					msg.textContent = 'Something went wrong - please try again.';
				} );
		} );
	} );
} )();
