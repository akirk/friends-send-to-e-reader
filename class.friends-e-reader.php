<?php
/**
 * Friends E-Reader
 *
 * This contains an abstract class of an E-Reader
 *
 * @package Friends_Send_To_E_Reader
 */

/**
 * This is the abstract class for the sending posts to an E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
abstract class Friends_E_Reader {
	abstract public function get_id();
	abstract public function render_input();
	abstract public static function render_template( $data = array() );
	abstract public static function instantiate_from_field_data( $data );
	abstract public function send_post( WP_Post $post );
}
