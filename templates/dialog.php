<dialog id="friends-ereader-multi-prompt" role="dialog" aria-labelledby="prompt-dialog-heading" style="max-width: 50%">
	<a class="btn btn-clear float-right close" aria-label="<?php esc_attr_e( 'Close', 'friends' ); ?>"></a>
	<h5></h5>
	<ul style="max-height: 5em; overflow: auto"></ul>
	<p><?php esc_html_e( 'Here you can choose a title and author for the book. You can also leave them empty, then they will be generated.', 'friends' ); ?></p>
	<form>
		<div class="form-group">
			<label class="form-label" for="ebook-title"><?php esc_html_e( 'E-Book Title', 'friends' ); ?></label>
			<input class="form-input" id="ebook-title" type="text" placeholder="<?php esc_attr_e( 'E-Book Title', 'friends' ); ?>">
		</div>
		<div class="form-group">
			<label class="form-label" for="ebook-author"><?php esc_html_e( 'E-Book Author', 'friends' ); ?></label>
			<input class="form-input" id="ebook-author" type="text" placeholder="<?php esc_attr_e( 'E-Book Author', 'friends' ); ?>">
		</div>
		<button class="btn btn-primary" name="ok"><?php esc_html_e( 'Submit', 'friends' ); ?></button>
		<button class="btn" name="close" class="close"><?php esc_html_e( 'Cancel', 'friends' ); ?></button>
	</form>
</dialog>
