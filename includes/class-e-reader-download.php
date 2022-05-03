<?php
/**
 * Friends E-Reader Download
 *
 * This contains the class for a generic E-Reader that can receive ePub via e-mail.
 *
 * @package Friends_Send_To_E_Reader
 */

namespace Friends;

/**
 * This is the class for the sending posts to a generic E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
class E_Reader_Download extends E_Reader {
	const NAME = 'Download ePub';
	protected $id;
	protected $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function get_id() {
		if ( empty( $this->id ) ) {
			$this->id = hash( 'crc32', time(), false );
		}

		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function render_input() {
		self::render_template(
			array(
				'id' => $this->get_id(),
			)
		);
	}

	public static function get_defaults() {
		return array(
			'id' => 'new',
		);
	}

	public static function render_template( $data = array() ) {
	}

	public static function instantiate_from_field_data( $id, $data ) {
		$class = get_called_class();
		return new $class( $data['name'] );
	}

	/**
	 * Send a post to the E-Reader reachable at the particular e-mail address.
	 *
	 * @param      array $posts    The posts.
	 *
	 * @return     bool     Whether it was sent successfully.
	 */
	public function send_posts( $posts ) {
		$tmp_file = $this->generate_file( $posts );

		if ( ! file_exists( $tmp_file ) ) {
			return false;
		}

		$file = array(
			'name'     => basename( $tmp_file ),
			'type'     => 'application/epub',
			'tmp_name' => $tmp_file,
			'error'    => 0,
			'size'     => filesize( $tmp_file ),
		);

		$overrides = array(
			'test_form' => false,
			'test_type' => false,
		);

		$results = wp_handle_sideload( $file, $overrides );
		if ( ! empty( $results['error'] ) ) {
			return false;
		}

		return array( 'url' => $results['url'] );
	}

}
