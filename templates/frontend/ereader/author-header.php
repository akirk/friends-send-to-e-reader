<?php
/**
 * This template contains the message part for the author header on /friends/.
 *
 * @package Friends
 */

foreach ( $ereaders as $id => $ereader ) {
	?><a class="chip send-new-message" href="#"><?php esc_html( sprintf(
		// translators: %s is an E-Reader name.
		__( 'Send new posts to %s', 'friends' ), $ereader->name ) ); ?></a><?php
}

