( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.querySelector( '.gemz-browser' );
		if ( ! root ) return;

		var steps = {
			industry: root.querySelector( '[data-step="industry"]' ),
			location: root.querySelector( '[data-step="location"]' ),
			offers:   root.querySelector( '[data-step="offers"]' )
		};
		var selectedIndustry = null;
		var selectedIndustryLabel = '';

		function showStep( key ) {
			Object.keys( steps ).forEach( function ( k ) {
				steps[ k ].classList.toggle( 'is-active', k === key );
			} );
		}

		root.querySelectorAll( '.gemz-industry-card' ).forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				selectedIndustry = card.getAttribute( 'data-industry' );
				selectedIndustryLabel = card.getAttribute( 'data-label' );
				root.querySelector( '.gemz-selected-industry-label' ).textContent = selectedIndustryLabel;
				showStep( 'location' );
			} );
			card.setAttribute( 'tabindex', '0' );
			card.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					card.click();
				}
			} );
		} );

		root.querySelectorAll( '[data-back]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				showStep( btn.getAttribute( 'data-back' ) );
			} );
		} );

		var locationForm = root.querySelector( '.gemz-location-form' );
		locationForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var zip = locationForm.querySelector( '[name="zip"]' ).value.trim();
			var city = locationForm.querySelector( '[name="city"]' ).value.trim();
			var state = locationForm.querySelector( '[name="state"]' ).value.trim();

			var url = new URL( gemzBrowser.restUrl );
			url.searchParams.set( 'industry', selectedIndustry );
			if ( zip ) url.searchParams.set( 'zip', zip );
			if ( city ) url.searchParams.set( 'city', city );
			if ( state ) url.searchParams.set( 'state', state );

			var resultsWrap = root.querySelector( '.gemz-offers-list' );
			var emptyState = root.querySelector( '.gemz-empty-state' );
			resultsWrap.innerHTML = '<p>Searching...</p>';
			emptyState.style.display = 'none';
			showStep( 'offers' );

			fetch( url.toString() )
				.then( function ( res ) { return res.json(); } )
				.then( function ( data ) {
					resultsWrap.innerHTML = '';
					var offers = ( data && data.offers ) ? data.offers : [];
					if ( offers.length === 0 ) {
						emptyState.style.display = 'block';
						return;
					}
					// offer.link is a bare /go/{slug} URL with no ?ref= - without this,
					// an agent's generic homepage link (?ref=CODE) loses attribution
					// the moment a visitor uses this widget instead of a campaign-
					// specific /go/ link. Carry the current page's ref (and
					// campaign_id, if present) forward onto every offer link.
					var pageParams = new URLSearchParams( window.location.search );
					var ref = pageParams.get( 'ref' );
					var campaignId = pageParams.get( 'campaign_id' );

					offers.forEach( function ( offer ) {
						var offerUrl = new URL( offer.link, window.location.origin );
						if ( ref ) offerUrl.searchParams.set( 'ref', ref );
						if ( campaignId ) offerUrl.searchParams.set( 'campaign_id', campaignId );

						var card = document.createElement( 'div' );
						card.className = 'gemz-offer-card';
						card.innerHTML =
							'<div><h4>' + selectedIndustryLabel + ' Cashback Available</h4>' +
							'<p>Serving your area &middot; get cash back when you book through us</p></div>' +
							'<a class="gemz-btn" href="' + offerUrl.toString() + '">Get My Cashback</a>';
						resultsWrap.appendChild( card );
					} );
				} )
				.catch( function () {
					resultsWrap.innerHTML = '';
					emptyState.style.display = 'block';
				} );
		} );
	} );
} )();
