var tribe_tickets_rsvp = {
	num_attendees: 0,
	event: {}
};

(function( $, my ) {
	'use strict';

	my.init = function() {
		this.$rsvp = $( '.tribe-events-tickets-rsvp' );
		this.attendee_template = $( document.getElementById( 'tribe-tickets-rsvp-tmpl' ) ).html();

		this.$rsvp.on( 'change input keyup', '.tribe-ticket-quantity', this.event.quantity_changed );

		this.$rsvp.closest( '.cart' )
			.on( 'submit', this.event.handle_submission );

		$( '.tribe-rsvp-list' ).on( 'click', '.attendee-meta-row .toggle', function() {
			$( this )
				.toggleClass( 'on' )
				.siblings( '.attendee-meta-details' )
				.slideToggle();
		});
	};

	my.quantity_changed = function( $quantity ) {

		var $rsvp = $quantity.closest( '.tribe-events-tickets-rsvp' );
		var $rsvp_qtys = $rsvp.find( '.tribe-ticket-quantity' );
		var rsvp_qty = 0;
		$rsvp_qtys.each( function () {
			rsvp_qty = rsvp_qty + parseInt( $( this ).val() );
		} );

		if ( 0 === rsvp_qty ) {
			$rsvp.removeClass( 'tribe-tickets-has-rsvp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-rsvp' );
		}
	};

	my.validate_submission = function( $form ) {
		var $rsvp = $form.find( '.tribe-ticket-quantity' );
		var rsvp_qty = 0;
		var $name = $( document.getElementById( 'tribe-tickets-full-name' ) );
		var $email = $( document.getElementById( 'tribe-tickets-email' ) );

		$rsvp.each( function () {
			rsvp_qty = rsvp_qty + parseInt( $( this ).val() );
		} );

		if (
			0 === rsvp_qty ||
			! $.trim( $rsvp.val() ).length ||
			! $.trim( $name.val() ).length ||
			! $.trim( $email.val() ).length
		) {
			return false;
		}

		return true;
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	my.event.handle_submission = function( e ) {

		if ( ! my.validate_submission(  $( this ).closest( 'form' ) ) ) {
			e.preventDefault();
			var $form = $( this ).closest( 'form' );

			$form.addClass( 'tribe-rsvp-message-display' );
			$form.find( '.tribe-rsvp-message-confirmation-error' ).show();

			$( 'html, body').animate({
				scrollTop: $form.offset().top
			}, 300 );
			return false;
		}
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_rsvp );