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
	protected $ebook_title;
	protected $ebook_author;
	public $active;
	abstract public function get_id();
	abstract public function render_input();
	abstract public static function render_template( $data = array() );
	abstract public static function instantiate_from_field_data( $id, $data );
	abstract public function send_posts( array $posts, $title = null, $author = null );

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
		ob_start();
		$post_title = $post->post_title;
		if ( empty( $post_title ) ) {
			$post_title = get_the_time( 'F j, Y H:i:s', $post );
		}

		Friends::template_loader()->get_template_part(
			$format . '/header',
			null,
			array(
				'title'  => $post_title,
				'author' => $post->author_name,
				'date'   => get_the_time( 'l, F j, Y', $post ),
			)
		);

		echo wp_kses_post( $post->post_content );

		Friends::template_loader()->get_template_part(
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


	protected function generate_file( array $posts, $title = null, $author = null ) {
		$authors = array();
		$this->ebook_title = $title;
		$this->ebook_author = $author;

		$dir = rtrim( sys_get_temp_dir(), '/' ) . '/friends_send_to_e_reader';
		if ( ! file_exists( $dir ) ) {
			mkdir( $dir );
		}

		foreach ( $posts as $post ) {
			if ( ! $this->ebook_title ) {
				$post_title = get_the_time( 'F j, Y H:i:s', $post );
				if ( empty( $post_title ) ) {
					$post_title = get_the_excerpt( $post );
				}
				$this->ebook_title = $this->strip_emojis( $post_title );
			}

			$author_name = $this->update_author_name( $post );
			if ( ! in_array( $author_name, $authors ) ) {
				$authors[] = $author_name;
			}
		}

		if ( count( $posts ) > 1 && ! $title ) {
			// translators: %s is a post title. This is a title to be used when multiple posts are compiled to an ePub.
			$this->ebook_title = sprintf( __( '%s & more', 'friends' ), $this->ebook_title );
		}

		if ( ! $this->ebook_author ) {
			$this->ebook_author = implode( ', ', $authors );
		}
		$this->ebook_title = $this->strip_emojis( $this->ebook_title );
		$this->ebook_author = $this->strip_emojis( $this->ebook_author );

		$filename = sanitize_title( substr( $this->ebook_author, 0, 40 ) . ' - ' . substr( $this->ebook_title, 0, 100 ) );
		$url = home_url( '?' . implode( '-', array_map( 'intval', array_column( $posts, 'ID' ) ) ) );
		$book = new \PHPePub\Core\EPub();

		$book->setTitle( htmlspecialchars( $this->ebook_title ) );
		$book->setIdentifier( $url, \PHPePub\Core\EPub::IDENTIFIER_URI );
		$book->setAuthor( htmlspecialchars( $this->ebook_author ), htmlspecialchars( $this->ebook_author ) );

		$book->setSourceURL( $url );

		$book->addCSSFile( 'style.css', 'css', file_get_contents( Friends::template_loader()->get_template_part( 'epub/style', null, array(), false ) ) );

		foreach ( $posts as $count => $post ) {
			$post_title = $post->post_title;
			if ( empty( $post_title ) ) {
				$post_title = get_the_excerpt( $post );
			}

			$content = $this->get_content( 'epub', $post );

			$book->addChapter( $post_title, sanitize_title( substr( $this->strip_emojis( $post->post_author ), 0, 40 ) . ' - ' . substr( $post_title, 0, 100 ) ) . '.html', $content, false, \PHPePub\Core\EPub::EXTERNAL_REF_ADD, $dir );
		}

		if ( count( $posts ) > 1 ) {
			$book->buildTOC( null, 'toc', __( 'Table of Contents', 'friends' ), true, true );
		}

		$book->finalize();
		$book->saveBook( $filename . '.epub', $dir );

		return $dir . '/' . $filename . '.epub';
	}

}
