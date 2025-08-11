jQuery( document ).ready( function( $ ) {
	$( '.wewp.notice.is-dismissible' ).on( 'click', '.notice-dismiss', function ( e ) {
		e.preventDefault();

		$.post( window.ajaxurl, {
			action: 'wewp_dismiss_notice',
			nonce: $( this ).parent().data( 'nonce' ),
			notice: $( this ).parent().data( 'notice' ),
		} );
	} );
} );
