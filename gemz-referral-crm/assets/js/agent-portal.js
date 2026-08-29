( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initPaymentMethodToggle();
		initCopyReferralLink();
		initReferralQrCode();
		initPasswordChangeForm();
	} );

	function initPaymentMethodToggle() {
		var select = document.getElementById( 'gemz_payment_method' );
		if ( ! select ) return;
		var groups = document.querySelectorAll( '.gemz-payment-fields' );
		select.addEventListener( 'change', function () {
			groups.forEach( function ( group ) {
				group.style.display = ( group.getAttribute( 'data-method' ) === select.value ) ? '' : 'none';
			} );
		} );
	}

	function initCopyReferralLink() {
		var btn = document.getElementById( 'gemz-copy-referral-link' );
		var input = document.getElementById( 'gemz-referral-link-input' );
		if ( ! btn || ! input ) return;

		btn.addEventListener( 'click', function () {
			input.select();
			input.setSelectionRange( 0, 99999 );
			navigator.clipboard.writeText( input.value ).then( function () {
				var original = btn.textContent;
				btn.textContent = 'Copied!';
				setTimeout( function () { btn.textContent = original; }, 1500 );
			} );
		} );
	}

	function initReferralQrCode() {
		var container = document.getElementById( 'gemz-referral-qr' );
		if ( ! container || typeof qrcode === 'undefined' ) return;

		var link = container.getAttribute( 'data-link' );
		var qr = qrcode( 0, 'M' ); // type 0 = auto-detect smallest size, ECC level M
		qr.addData( link );
		qr.make();

		container.innerHTML = qr.createSvgTag( { cellSize: 4, margin: 4 } );

		var downloadBtn = document.getElementById( 'gemz-download-qr' );
		if ( downloadBtn ) {
			downloadBtn.addEventListener( 'click', function () {
				var svg = container.querySelector( 'svg' );
				if ( ! svg ) return;

				var svgData = new XMLSerializer().serializeToString( svg );
				var img = new Image();
				var canvas = document.createElement( 'canvas' );
				var size = 512;
				canvas.width = size;
				canvas.height = size;

				img.onload = function () {
					var ctx = canvas.getContext( '2d' );
					ctx.fillStyle = '#ffffff';
					ctx.fillRect( 0, 0, size, size );
					ctx.drawImage( img, 0, 0, size, size );

					var link = document.createElement( 'a' );
					link.download = 'gemz-referral-qr.png';
					link.href = canvas.toDataURL( 'image/png' );
					link.click();
				};
				img.src = 'data:image/svg+xml;base64,' + btoa( svgData );
			} );
		}
	}

	function initPasswordChangeForm() {
		var form = document.getElementById( 'gemz-change-password-form' );
		if ( ! form ) return;

		var msg = form.querySelector( '.gemz-form-message' );
		form.addEventListener( 'submit', function ( e ) {
			var newPass = form.new_password.value;
			var confirmPass = form.confirm_password.value;
			if ( newPass !== confirmPass ) {
				e.preventDefault();
				msg.textContent = 'New password and confirmation don\'t match.';
				msg.classList.add( 'grc-error' );
			}
		} );
	}
} )();
