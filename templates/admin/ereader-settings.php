<?php
/**
 * E-Reader Settings
 *
 * @package Friends_Send_To_E_Reader
 */

?>
<form method="post">
	<?php wp_nonce_field( $args['nonce_value'] ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Reading Summary', 'friends' ); ?></th>
			<td>
				<fieldset>
					<label for="reading_summary">
						<input name="reading_summary" type="checkbox" id="reading_summary" value="1" <?php checked( '1', $args['reading_summary'] ); ?> />
						<?php esc_html_e( 'Automatically create a draft post when sending to E-Reader.', 'friends' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Summary Draft Title', 'friends' ); ?></th>
			<td>
				<fieldset>
					<p>
					<label for="reading_summary_title">
						<input type="text" name="reading_summary_title" id="reading_summary_title" value="<?php echo esc_attr( $args['reading_summary_title'] ); ?>" />
					</label>
					</p>
					<p class="description">
						<?php echo wp_kses( __( 'This title for the draft post will be used. You can use the following variables: <tt>$date</tt> <tt>$author</tt> <tt>$title</tt>', 'friends' ), array( 'tt' => array() ) ); ?>
					</p>
				</fieldset>
			</td>
		</tr>
	</table>
	<input type="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'friends' ); ?>" />
</form>
