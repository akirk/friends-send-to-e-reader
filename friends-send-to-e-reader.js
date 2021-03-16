jQuery( function( $ ) {
	var $document = $( document );

	wp = wp || {};
	$document.on( 'click', 'a.friends-send-post-to-e-reader', function() {
		var $this = $(this);
		var search_indicator = $this.find( 'i' );
		if ( search_indicator.hasClass( 'loading' ) ) {
			return;
		}

		wp.ajax.send( 'send-post-to-e-reader', {
			data: {
				id: $this.data( 'id' ),
				ereader: $this.data( 'ereader' )
			},
			beforeSend: function() {
				search_indicator.addClass( 'form-icon loading' );
			},
			success: function( response ) {
				search_indicator.removeClass( 'form-icon loading' ).addClass( 'dashicons dashicons-saved' );
			}
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
