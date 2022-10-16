<tr>
	<th scope="row"><?php esc_html_e( 'Send to new posts to E-Reader', 'friends' ); ?></th>
	<td>
		<?php if ( ! empty( $args['ereaders'] ) ) : ?>
		<select name="send-to-e-reader">
			<option value="none"><?php esc_html_e( "Don't send a notification", 'friends' ); ?></option>
			<?php foreach ( $args['ereaders'] as $id => $ereader ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $args['selected'], $id ); ?>><?php echo esc_html( $ereader->get_name() ); ?></option>
			<?php endforeach; ?>
			</select>
		<?php endif; ?>

		<p class="description">
			<?php
			if ( empty( $args['ereaders'] ) ) {
				echo wp_kses(
					sprintf(
						// translators: %s is an URL.
						__( 'No E-Reader available that supports sending. Go to the <a href=%s>Send to E-Reader settings</a> to add one.', 'friends' ),
						'"' . self_admin_url( 'admin.php?page=friends-send-to-e-reader' ) . '"'
					),
					array( 'a' => array( 'href' => array() ) )
				);
			} else {
			}
			?>
			</p>
	</td>
</tr>
