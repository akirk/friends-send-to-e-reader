jQuery( function( $ ) {
	var $document = $( document );

	wp = wp || {};
	$document.on( 'click', 'a.friends-send-post-to-e-reader', function() {
		var $this = $(this);
		var search_indicator = $this.find( 'i' );
		if ( search_indicator.hasClass( 'loading' ) ) {
			return;
		}

		var data = {
			ids: [ $this.data( 'id' ) ],
			ereader: $this.data( 'ereader' )
		}

		var dialog = document.getElementById( 'friends-ereader-multi-prompt' );
		var post_list = $( dialog ).find( 'ul' );
		post_list.append( '<li>' + $this.closest( 'article' ).find( 'h4.card-title' ).text() + ' by ' + $this.closest( 'article' ).find( 'div.author' ).text() );

		if ( $this.closest( 'ul' ).find( 'li.menu-item input[name=multi-entry]' ).is( ':checked' ) ) {
			$this.closest( 'article' ).prevAll().slice( -30 ).each( function( i, article ) {
				if ( 'post-' === article.id.substr( 0, 5 ) ) {
					data.ids.push( Number( article.id.substr( 5 ) ) );
					post_list.append( '<li>' + $( article ).find( 'h4.card-title' ).text() + ' by ' + $( article ).find( 'div.author' ).text() );
				}
			} );
		}

		if ( $this.closest( 'ul' ).find( 'li.menu-item input[name=reading-summary]' ).is( ':checked' ) ) {
			data.reading_summary = 1;
		}

		var send = function( data ) {
			wp.ajax.send( 'send-post-to-e-reader', {
				data: data,
				beforeSend: function() {
					search_indicator.addClass( 'form-icon loading' );
					setTimeout( function() { $this.closest( 'div' ).find( 'a.friends-dropdown-toggle' ).focus(); }, 100 );
				},
				success: function( e ) {
					search_indicator.removeClass( 'form-icon loading' ).addClass( 'dashicons dashicons-saved' );
					if ( e.url ) {
						location.href = e.url;
					}
				},
				error: function( e ) {
					search_indicator.removeClass( 'form-icon loading' ).addClass( 'dashicons dashicons-warning' ).prop( 'title', e );
				}
			} );
		}

		if ( data.ids.length > 1 && dialog ) {
			$( dialog ).find( 'h5' ).text( data.ids.length + ' posts selected' );
			$( '#ebook-title' ).prop( 'placeholder', $.trim( $( '#post-' + data.ids[0] + ' h4.card-title' ).text().replace( /\s+/, ' ' ) ) + ' & more' );
			$( '#ebook-author' ).prop( 'placeholder', $.trim( $.trim( $( '#post-' + data.ids[0] + ' div.author' ).text().replace( /\s+/, ' ' ) ) + ' et al' ) );
			dialog.showModal();
			$( document ).on( 'click', 'dialog .close', function() {
				dialog.close();
				return false;
			} );
			$( document ).on( 'click', 'dialog button[name=ok]', function() {
				if ( $( '#ebook-title' ).val() ) {
					data.title = $( '#ebook-title' ).val();
				}
				if ( $( '#ebook-author' ).val() ) {
					data.author = $( '#ebook-author' ).val();
				}
				send( data );
				dialog.close();
				return false;
			} );
		} else {
			send( data );
		}


		return false;
	} );

	$document.on( 'click', 'a#add-reader', function() {
		$( 'tr.template' ).removeClass( 'hidden' ).find( 'input:visible:first' ).focus();
		$( this ).remove();
		return false;
	} );

	$document.on( 'click', '#friends-ereader-multi-prompt a.title', function() {
		$( '#ebook-title' ).val( $( this ).data( 'content' ) );
		return false;
	} );

	$document.on( 'click', '#friends-ereader-multi-prompt a.author', function() {
		$( '#ebook-author' ).val( $( this ).data( 'content' ) );
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
