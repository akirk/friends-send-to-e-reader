<?php
/**
 * Friends E-Reader Pocketbook
 *
 * This contains the class for a Pocketbook E-Reader
 *
 * @package Friends_Send_To_E_Reader
 */

namespace Friends;

/**
 * This is the class for the sending posts to a Pocketbook E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
class E_Reader_Pocketbook extends E_Reader_Generic_Email {
	const NAME = 'Pocketbook';

	public static function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'email_placeholder' => '@pbsync.com',
			)
		);
	}

}
