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
						// msg lives inside the form, so hiding the whole form would
						// hide this success message with it - hide only the fields/
						// button instead.
						Array.prototype.forEach.call( form.children, function ( child ) {
							if ( child !== msg ) {
								child.style.display = 'none';
							}
						} );

						msg.textContent = '';
						msg.appendChild( document.createTextNode( 'Welcome aboard! Your referral code is ' + data.referral_code + '. Here’s your personal referral link, ready to share: ' ) );
						if ( data.referral_link ) {
							var refLink = document.createElement( 'a' );
							refLink.href = data.referral_link;
							refLink.textContent = data.referral_link;
							msg.appendChild( refLink );
						}
						msg.appendChild( document.createTextNode( ' Check your email, then log in to see your dashboard.' ) );
						msg.classList.add( 'grc-success' );
					} else {
						btn.disabled = false;
						btn.textContent = 'Become an Agent';
						msg.classList.add( 'grc-error' );

						if ( data && data.code === 'grc_email_taken' ) {
							var loginUrl = gemzAgentSignup.loginUrl || '/agent-login/';
							msg.textContent = '';
							msg.appendChild( document.createTextNode( 'An account with that email already exists. ' ) );
							var link = document.createElement( 'a' );
							link.href = loginUrl;
							link.textContent = 'Try logging in instead.';
							msg.appendChild( link );
						} else {
							msg.textContent = ( data && data.message ) ? data.message : 'Something went wrong - please try again.';
						}
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
