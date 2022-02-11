<?php
/**
 * Friends E-Reader
 *
 * This contains an abstract class of an E-Reader
 *
 * @package Friends_Send_To_E_Reader
 */

namespace Friends;

/**
 * This is the abstract class for the sending posts to an E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
abstract class E_Reader {
	abstract public function get_id();
	abstract public function render_input();
	abstract public static function render_template( $data = array() );
	abstract public static function instantiate_from_field_data( $id, $data );
	abstract public function send_post( \WP_Post $post );

	/**
	 * Strip Emojis from text
	 *
	 * @param      string $text   The text.
	 *
	 * @return     string  The text stripped off emojis.
	 */
	protected function strip_emojis( $text ) {
		// Match Emoticons.
		$regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
		$text = preg_replace( $regex_emoticons, '', $text );

		// Match Miscellaneous Symbols and Pictographs.
		$regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
		$text = preg_replace( $regex_symbols, '', $text );

		// Match Transport And Map Symbols.
		$regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
		$text = preg_replace( $regex_transport, '', $text );

		// Match Miscellaneous Symbols.
		$regex_misc = '/[\x{2600}-\x{26FF}]/u';
		$text = preg_replace( $regex_misc, '', $text );

		// Match Dingbats.
		$regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
		$text = preg_replace( $regex_dingbats, '', $text );

		return $text;
	}

	protected function get_content( $format, \WP_Post $post ) {
		require_once __DIR__ . '/class-friends-send-to-e-reader-template-loader.php';
		$template_loader = new Send_To_E_Reader_Template_Loader();

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

		echo wp_kses_post( $post->post_content );

		$template_loader->get_template_part(
			$format . '/footer',
			null,
			array(
				'url' => get_permalink( $post ),
			)
		);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	protected function update_author_name( \WP_Post $post ) {
		if ( ! isset( $post->author_name ) ) {
			$author = new User( $post->post_author );
			$author_name = $author->display_name;
			$override_author_name = apply_filters( 'friends_override_author_name', '', $author->display_name, $post->ID );
			if ( $override_author_name && trim( str_replace( $override_author_name, '', $author_name ) ) === $author_name ) {
				$author_name .= ' – ' . $override_author_name;
			}
			$post->author_name = $author_name;
		}
		return $post->author_name;
	}

	protected function generate_file( \WP_Post $post ) {
		$this->update_author_name( $post );

		$content = $this->get_content( 'epub', $post );

		$dir = rtrim( sys_get_temp_dir(), '/' ) . '/friends_send_to_e_reader';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		$filename = sanitize_title( $this->strip_emojis( $post->author_name . ' - ' . $post->post_title ) );
		$url = get_permalink( $post );

		$book = new \PHPePub\Core\EPub();

		$book->setTitle( $this->strip_emojis( $post->post_title ) );
		$book->setIdentifier( $url, \PHPePub\Core\EPub::IDENTIFIER_URI );
		$book->setAuthor( $post->author_name, $post->author_name );

		$book->setSourceURL( $url );

		require_once __DIR__ . '/class-friends-send-to-e-reader-template-loader.php';
		$template_loader = new Send_To_E_Reader_Template_Loader();

		$book->addCSSFile( 'style.css', 'css', file_get_contents( $template_loader->get_template_part( 'epub/style', null, array(), false ) ) );

		$book->addChapter( $post->post_title, $filename . '.html', $content, false, \PHPePub\Core\EPub::EXTERNAL_REF_ADD, $dir );
		$book->finalize();
		$book->saveBook( $filename . '.epub', $dir );

		return $dir . '/' . $filename . '.epub';
	}

}
