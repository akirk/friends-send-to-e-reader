<?php
/**
 * This template contains the message part for the author header on /friends/.
 *
 * @package Friends_Send_To_E_Reader
 */

if ( $args['friend']->has_cap( 'post_collection' ) ) {
	if ( count( $args['unsent_posts'] ) ) {
		foreach ( $args['ereaders'] as $id => $ereader ) {
			?><a class="chip send-unread-to-ereader" data-ereader="<?php echo esc_attr( $id ); ?>" href="#">
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is an E-Reader name.
					_n( 'Send %1$d new post to %2$s', 'Send %1$d new posts to %2$s', count( $args['unsent_posts'] ), 'friends' ),
					count( $args['unsent_posts'] ),
					$ereader->get_name()
				)
			);
			?>
				</a>
				<?php
		}
	} else {
		?>
		<span class="chip"><?php esc_html_e( 'No new posts', 'friends' ); ?></span>
		<?php
	}
}

