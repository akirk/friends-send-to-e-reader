<?php
/**
 * This template contains the message part for the author header on /friends/.
 *
 * @package Friends
 */

foreach ( $args['ereaders'] as $id => $ereader ) {
	?><a class="chip send-unread-to-ereader" data-ereader="<?php echo esc_attr( $id ); ?>" href="#"><?php echo esc_html( sprintf(
		// translators: %s is an E-Reader name.
		__( 'Send new posts to %s', 'friends' ), $ereader->get_name() ) ); ?></a><?php
}

