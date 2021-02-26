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

	$document.on( 'click', 'a#add-reader', function() {
		$( 'tr.template' ).removeClass( 'hidden' ).find( 'input:visible:first' ).focus();
		$( this ).remove();
		return false;
	} );

	$document.on( 'click', 'a.delete-reader', function() {
		$( this ).closest('tr').html( '<td colspan=2>' + $( this ).data( 'delete-text' ) );
		return false;
	} );

} );
