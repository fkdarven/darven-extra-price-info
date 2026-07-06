( function( $ ) {
	'use strict';

	var toggleSelector = '.darven-epi-installments-toggle';

	function setExpanded( $toggle, expanded ) {
		var $statement = $toggle.closest( '.darven-epi-installments-price-statement' );
		var $popup = $statement.find( '.darven-epi-installments-popup' ).first();

		$toggle
			.toggleClass( 'selected', expanded )
			.attr( 'aria-expanded', expanded ? 'true' : 'false' );

		$popup
			.attr( 'aria-hidden', expanded ? 'false' : 'true' )
			.stop( true, true )[ expanded ? 'slideDown' : 'slideUp' ]( 'fast' );
	}

	$( document ).on( 'click', toggleSelector, function() {
		var $toggle = $( this );
		var expanded = 'true' === $toggle.attr( 'aria-expanded' );

		setExpanded( $toggle, ! expanded );
	} );

	$( document ).on( 'blur', toggleSelector, function() {
		setExpanded( $( this ), false );
	} );

	$( document ).on( 'keydown', toggleSelector, function( event ) {
		if ( 'Escape' === event.key ) {
			setExpanded( $( this ), false );
		}
	} );
}( jQuery ) );
