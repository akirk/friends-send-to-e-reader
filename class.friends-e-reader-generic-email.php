<?php
/**
 * Friends E-Reader Generic E-Mail
 *
 * This contains the class for a generic E-Reader that can receive ePub via e-mail.
 *
 * @package Friends_Send_To_E_Reader
 */

/**
 * This is the class for the sending posts to a generic E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
class Friends_E_Reader_Generic_Email extends Friends_E_Reader {
	const NAME = 'ePub via E-Mail';
	protected $id;
	protected $name;
	protected $email;

	public function __construct( $name, $email ) {
		$this->name = $name;
		$this->email = $email;
	}

	public function get_id() {
		if ( ! $this->email ) {
			return null;
		}

		if ( empty( $this->id ) ) {
			$this->id = hash( 'crc32', time() . $this->email, false );
		}

		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function render_input() {
		self::render_template(
			array(
				'id'    => $this->get_id(),
				'email' => $this->email,
			)
		);
	}

	public static function get_defaults() {
		return array(
			'id'                => 'new',
			'email'             => '',
			'email_placeholder' => __( 'E-Reader E-Mail address', 'friends' ),
		);
	}

	public static function render_template( $data = array() ) {
		$data = array_merge( static::get_defaults(), $data );
		?><input type="text" class="email" name="ereaders[<?php echo esc_attr( $data['id'] ); ?>][email]" value="<?php echo esc_attr( $data['email'] ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader E-Mail address', 'friends' ); ?>" placeholder="<?php echo esc_attr( $data['email_placeholder'] ); ?>"/>
		<?php
	}

	public static function instantiate_from_field_data( $data ) {
		$class = get_called_class();
		return new $class( $data['name'], $data['email'] );
	}

	/**
	 * Strip Emojis from text
	 *
	 * @param      string $text   The text.
	 *
	 * @return     string  The text stripped off emojis.
	 */
	protected function strip_emojis( $text ) {
		// Match Emoticons
		$regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$text = preg_replace( $regex_emoticons, '', $text );

		// Match Miscellaneous Symbols and Pictographs
		$regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$text = preg_replace( $regex_symbols, '', $text );

		// Match Transport And Map Symbols
		$regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
		$text = preg_replace( $regex_transport, '', $text );

		// Match Miscellaneous Symbols
		$regex_misc = '/[\x{2600}-\x{26FF}]/u';
		$text = preg_replace( $regex_misc, '', $text );

		// Match Dingbats
		$regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
		$text = preg_replace( $regex_dingbats, '', $text );

		return $text;
	}

	protected function get_author_name( WP_Post $post ) {
		$author = new Friend_User( $post->post_author );
		$author_name = $author->display_name;
		$override_author_name = apply_filters( 'friends_override_author_name', '', $author->display_name, $post->ID );
		if ( $override_author_name && trim( str_replace( $override_author_name, '', $author_name ) ) === $author_name ) {
			$author_name .= ' – ' . $override_author_name;
		}
		return $author_name;
	}

	protected function generate_file( WP_Post $post ) {
		$content = $this->get_content( 'epub', $post );

		$dir = rtrim( sys_get_temp_dir(), '/' ) . '/friends_send_to_e_reader';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$filename = sanitize_title( $this->strip_emojis( $this->get_author_name( $post ) . ' - ' . $post->post_title ) );

		$book = new PHPePub\Core\EPub();

		$book->setTitle( $this->strip_emojis( $post->post_title ) );
		$book->setIdentifier( $url, PHPePub\Core\EPub::IDENTIFIER_URI );
		$book->setAuthor( $post->author_name, $post->author_name );

		$book->setSourceURL( $url );

		require_once __DIR__ . '/class.friends-send-to-e-reader-template-loader.php';
		$template_loader = new Friends_Send_To_E_Reader_Template_Loader();

		$book->addCSSFile( 'style.css', 'css', file_get_contents( $template_loader->get_template_part( 'epub/style', null, array(), false ) ) );

		$book->addChapter( $post->post_title, $filename . '.html', $content, false, PHPePub\Core\EPub::EXTERNAL_REF_ADD, $dir );
		$book->finalize();
		$book->saveBook( $filename . '.epub', $dir );

		return $dir . '/' . $filename . '.epub';
	}

	protected function get_content( $format, WP_Post $post ) {
		require_once __DIR__ . '/class.friends-send-to-e-reader-template-loader.php';
		$template_loader = new Friends_Send_To_E_Reader_Template_Loader();

		ob_start();
		$template_loader->get_template_part(
			$format . '/header',
			null,
			array(
				'title'  => $post->post_title,
				'author' => $post->author_name,
				'date'   => get_the_time( 'l, F j, Y', $post ),
			)
		);

		echo $post->post_content;

		$template_loader->get_template_part(
			$format . '/footer',
			null,
			array(
				'url' => $url,
			)
		);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Send a post to the E-Reader reachable at the particular e-mail address.
	 *
	 * @param      WP_Post $post    The post.
	 *
	 * @return     bool     Whether it was sent successfully.
	 */
	public function send_post( WP_Post $post ) {
		$post->author_name = $this->get_author_name( $post );

		$file = $this->generate_file( $post );

		if ( ! file_exists( $file ) ) {
			return false;
		}

		$friends = Friends::get_instance();
		$friends->notifications->send_mail( $this->email, $post->post_title, $post->post_title, array(), array( $file ) );
		unlink( $file );
		return true;
	}

}
