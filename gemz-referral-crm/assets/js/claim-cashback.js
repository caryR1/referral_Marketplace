( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'gemz-claim-cashback-form' );
		if ( ! form ) return;

		var methodSelect = form.querySelector( '#gemz_claim_method' );
		var fieldGroups = form.querySelectorAll( '.gemz-payment-fields' );
		var msg = form.querySelector( '.gemz-form-message' );

		methodSelect.addEventListener( 'change', function () {
			fieldGroups.forEach( function ( group ) {
				group.style.display = ( group.getAttribute( 'data-method' ) === methodSelect.value ) ? '' : 'none';
			} );
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var btn = form.querySelector( '.gemz-btn' );
			btn.disabled = true;
			btn.textContent = 'Submitting...';
			msg.textContent = '';
			msg.className = 'gemz-form-message';

			var payload = {
				token: form.token.value,
				payout_method: methodSelect.value,
				paypal_email: form.paypal_email ? form.paypal_email.value : '',
				venmo_handle: form.venmo_handle ? form.venmo_handle.value : '',
				bank_account_holder: form.bank_account_holder ? form.bank_account_holder.value : '',
				bank_account_number: form.bank_account_number ? form.bank_account_number.value : '',
				bank_routing: form.bank_routing ? form.bank_routing.value : '',
				bank_name: form.bank_name ? form.bank_name.value : '',
				mailing_address: form.mailing_address ? form.mailing_address.value : ''
			};

			fetch( gemzClaimCashback.restUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload )
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					if ( data && data.success ) {
						// msg lives inside the form, so hiding the whole form would hide
						// the success text with it - hide only the fields/button instead.
						Array.prototype.forEach.call( form.children, function ( child ) {
							if ( child !== msg ) {
								child.style.display = 'none';
							}
						} );
						msg.textContent = 'Thanks! We\'ve got your payout details and we\'re processing your cash back now.';
						msg.classList.add( 'grc-success' );
					} else {
						btn.disabled = false;
						btn.textContent = 'Claim My Cash Back';
						msg.textContent = ( data && data.message ) ? data.message : 'Something went wrong - please try again.';
						msg.classList.add( 'grc-error' );
					}
				} )
				.catch( function () {
					btn.disabled = false;
					btn.textContent = 'Claim My Cash Back';
					msg.textContent = 'Something went wrong - please try again.';
					msg.classList.add( 'grc-error' );
				} );
		} );
	} );
} )();
