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
			success: function( e ) {
				search_indicator.removeClass( 'form-icon loading' ).addClass( 'dashicons dashicons-saved' );
				if ( e.url ) {
					location.href = e.url;
				}
			}
		} );
		return false;
	} );

	$document.on( 'click', 'a#add-reader', function() {
		$( 'tr.template' ).removeClass( 'hidden' ).find( 'input:visible:first' ).focus();
		$( this ).remove();
		return false;
	} );

	$document.on( 'change', 'select#ereader-class', function() {
		var td = $( '#' + $( this ).val().replace( '\\', '\\\\' ) );
		td.show().siblings().hide();
		var n = $( 'tr.template input.name' );
		if ( '' === n.val() ) {
			n.val( $( this ).find( 'option:selected' ).text() );
		}
		td.find( 'input:visible:first' ).focus();
	} );

	$document.on( 'click', 'a.delete-reader', function() {
		$( this ).closest('tr').html( '<td colspan=3>' + $( this ).data( 'delete-text' ) );
		return false;
	} );

} );
