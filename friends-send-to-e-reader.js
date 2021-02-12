jQuery( function( $ ) {
	var $document = $( document );

	wp = wp || {};
	$document.on( 'click', 'a.friends-send-post-to-e-reader', function() {
		var $this = $(this);
		wp.ajax.post( 'send-post-to-e-reader', {
			id: $this.data( 'id' )
		}).done( function( response ) {
			alert( response );
		} );
		return false;
	} );
} );
