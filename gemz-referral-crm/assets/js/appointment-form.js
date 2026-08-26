( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.querySelector( '.grc-appointment-form-wrap' );
		if ( ! wrap ) return;

		var form = wrap.querySelector( '#grc-appointment-form' );
		var msg  = wrap.querySelector( '.grc-form-message' );
		var partnerId = wrap.getAttribute( 'data-partner-id' );

		var params = new URLSearchParams( window.location.search );
		var ref = params.get( 'ref' ) || '';
		var campaignId = params.get( 'campaign_id' ) || '';

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var submitBtn = form.querySelector( '.grc-submit-btn' );
			submitBtn.disabled = true;
			submitBtn.textContent = 'Booking...';
			msg.textContent = '';

			var payload = {
				partner_id: partnerId,
				campaign_id: campaignId,
				ref: ref,
				customer_name: form.customer_name.value,
				customer_phone: form.customer_phone.value,
				customer_email: form.customer_email.value,
				customer_zip: form.customer_zip.value,
				preferred_contact: form.preferred_contact.value,
				appointment_primary: form.appointment_primary.value,
				appointment_backup: form.appointment_backup.value
			};

			fetch( grcFunnel.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': grcFunnel.nonce
				},
				body: JSON.stringify( payload )
			} )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					if ( data && data.success ) {
						form.style.display = 'none';
						msg.textContent = "You're all set! We'll be in touch to confirm your appointment.";
						msg.classList.add( 'grc-success' );
					} else {
						submitBtn.disabled = false;
						submitBtn.textContent = 'Book My Appointment';
						msg.textContent = ( data && data.message ) ? data.message : 'Something went wrong - please try again.';
						msg.classList.add( 'grc-error' );
					}
				} )
				.catch( function () {
					submitBtn.disabled = false;
					submitBtn.textContent = 'Book My Appointment';
					msg.textContent = 'Something went wrong - please try again.';
					msg.classList.add( 'grc-error' );
				} );
		} );
	} );
} )();
