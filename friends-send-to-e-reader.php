<?php
/**
 * Plugin name: Friends Send to E-Reader
 * Plugin author: Alex Kirk
 * Plugin URI: https://github.com/akirk/friends-send-to-e-reader
 * Version: 0.2.4
 *
 * Description: Send friend posts to your e-reader.
 *
 * License: GPL2
 * Text Domain: friends
 *
 * @package Friends_Send_To_E_Reader
 */

/**
 * This file contains the main plugin functionality.
 */

defined( 'ABSPATH' ) || exit;
define( 'FRIENDS_SEND_TO_E_READER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require 'vendor/autoload.php';

/**
 * Display an about page for the plugin.
 *
 * @param      bool $display_about_friends  The display about friends section.
 */
function friends_send_to_e_reader_about_page( $display_about_friends = false ) {
	$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );

	$friends = Friends::get_instance();
	$nonce_value = 'friends-send-to-e-reader';
	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
		$delete_ereaders = $ereaders;
		foreach ( $_POST['ereaders'] as $id => $ereader ) {
			if ( isset( $ereaders[ $id ] ) ) {
				$ereaders[ $id ] = $ereader;
				unset( $delete_ereaders[ $id ] );
				if ( '' == trim( $ereader['email'] ) ) {
					unset( $ereaders[ $id ] );
				}
			} else {
				if ( '' != trim( $ereader['email'] ) ) {
					if ( '' == trim( $ereader['name'] ) ) {
						$ereader['name'] = $ereader['email'];
					}
					$id = hash( 'crc32', time() . $ereader['email'], false );
					$ereaders[ $id ] = $ereader;

				}
			}
		}
		foreach ( $delete_ereaders as $id => $ereader ) {
			unset( $ereaders[$id] );
		}
		usort(
			$ereaders,
			function( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		update_option( 'friends-send-to-e-reader_readers', $ereaders );
	}
	$save_changes = __( 'Save Changes', 'friends' );

	?><h1><?php _e( 'Friends Send to E-Reader', 'friends' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( $nonce_value ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Your E-Readers', 'friends' ); ?></th>
					<td>
						<table class="reader-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'friends' ); ?></th>
									<th><?php esc_html_e( 'E-Mail address', 'friends' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php
							foreach ( $ereaders as $id => $ereader ) :
								?>
								<tr>
									<td><input type="text" class="name" name="ereaders[<?php echo esc_attr( $id ); ?>][name]" value="<?php echo esc_attr( $ereader['name'] ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader Name', 'friends' ); ?>" /></td>
									<td><input type="text" class="email" name="ereaders[<?php echo esc_attr( $id ); ?>][email]" value="<?php echo esc_attr( $ereader['email'] ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader E-Mail address', 'friends' ); ?>" placeholder="<?php _e( 'E-Reader E-Mail address', 'friends' ); ?>"/></td>
									<td><a href="" class="delete-reader" data-delete-text="
									<?php
									echo wp_kses(
										sprintf(
										// translators: %1$s is the button named "delete", %2$s is the user given name of an e-reader.
											__( 'Click %1$s to really delete the reader %2$s.', 'friends' ),
											'<em>' . esc_html( $save_changes ) . '</em>',
											'<em>' . esc_html( $ereader['name'] )
										) . '</em>',
										array( 'em' => array() )
									);
									?>
										"><?php _e( 'delete' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
							<tr class="template<?php echo empty( $ereaders ) ? '' : ' hidden'; ?>">
								<td><input type="text" class="name" name="ereaders[new][name]" value="<?php echo esc_attr__( 'E-Reader', 'friends' ), empty( $ereaders ) ? '' : ( ' ' . ( count( $ereaders ) + 1 ) ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader Name', 'friends' ); ?>" /></td>
								<td><input type="text" class="email" name="ereaders[new][email]" value="" size="30" aria-label="<?php esc_attr_e( 'E-Reader E-Mail address', 'friends' ); ?>" placeholder="<?php _e( 'E-Reader E-Mail address', 'friends' ); ?>"/></td>
							</tr>
							</tbody>
						</table>
						<?php if ( ! empty( $ereaders ) ) : ?>
							<a href="" id="add-reader"><?php _e( 'Add another E-Reader', 'friends' ); ?></a>
						<?php endif; ?>
						<p class="description">
							<?php
							echo __( 'Some E-Readers offer wireless delivery via an e-mail address which you\'ll first need to create.', 'friends' );
							?>
						</p>
						<p class="description">
							<?php
							echo wp_kses(
								sprintf(
									// translators: %1$s and %2$s are URLs.
									__( 'Examples include Kindle (@free.kindle.com, <a href="%1$s">Instructions</a>) or Pocketbook (@pbsync.com, <a href="%2$s">Instructions</a>).', 'friends' ),
									'https://help.fivefilters.org/push-to-kindle/email-address.html" target="_blank" rel="noopener noreferrer',
									'https://sync.pocketbook-int.com/files/s2pb_info_en.pdf" target="_blank" rel="noopener noreferrer'
								),
								array(
									'a' => array(
										'href'   => array(),
										'rel'    => array(),
										'target' => array(),
									),
								)
							);
							echo '<br/>';

							echo esc_html(
								sprintf(
									// translators: %s is an e-mail address.
									__( 'Make sure that you whitelist the e-mail address which the friend plugin sends its e-mails from: %s', 'friends' ),
									$friends->notifications->get_friends_plugin_from_email_address()
								)
							);

							?>
						</p>
						<p class="description">
							<?php
							esc_html_e( 'Theoretically you can enter any e-mail address.', 'friends' );
							echo ' ';
							esc_html_e( 'By default the plugin will send an e-mail with an ePub file attached.', 'friends' );
							?>
							</p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" id="submit" class="button button-primary" value="<?php echo esc_html( $save_changes ); ?>">
		</p>
	</form>

	<?php if ( $display_about_friends ) : ?>
		<p>
			<?php
			echo wp_kses(
				// translators: %s: URL to the Friends Plugin page on WordPress.org.
				sprintf( __( 'The Friends plugin is all about connecting with friends and news. Learn more on its <a href=%s>plugin page on WordPress.org</a>.', 'friends' ), '"https://wordpress.org/plugins/friends" target="_blank" rel="noopener noreferrer"' ),
				array(
					'a' => array(
						'href'   => array(),
						'rel'    => array(),
						'target' => array(),
					),
				)
			);
			?>
		</p>
	<?php endif; ?>
	<p>
	<?php
	echo wp_kses(
		// translators: %s: URL to the Embed library.
		sprintf( __( 'This plugin is largely powered by the open source project <a href=%s>PHPePub</a>.', 'friends' ), '"https://github.com/Grandt/PHPePub" target="_blank" rel="noopener noreferrer"' ),
		array(
			'a' => array(
				'href'   => array(),
				'rel'    => array(),
				'target' => array(),
			),
		)
	);
	?>
	</p>
	<?php
}

/**
 * Display an about page for the plugin with the friends section.
 */
function friends_send_to_e_reader_about_page_with_friends_about() {
	return friends_send_to_e_reader_about_page( true );
}

/**
 * Display an input field to enter the e-reader e-mail address.
 *
 * @param      Friend_User $friend  The friend.
 */
function friends_send_to_e_reader_edit_friend( Friend_User $friend ) {
	$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
	$selected = get_user_option( 'friends_send_to_e_reader', $friend->ID );
	?>
<tr>
	<th scope="row"><?php esc_html_e( 'Send to new posts to E-Reader', 'friends' ); ?></th>
	<td>
		<?php if ( ! empty( $ereaders ) ) : ?>
		<select name="send-to-e-reader">
			<option value="none"><?php esc_html_e( "Don't send a notification", 'friends' ); ?></option>
			<?php foreach ( $ereaders as $id => $ereader ) : ?>
			<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $selected, $id ); ?>><?php echo esc_html( $ereader['name'] ); ?></option>
		<?php endforeach; ?>
		</select>
	<?php endif; ?>

		<p class="description">
			<?php
			if ( empty( $ereaders ) ) {
				echo wp_kses(
					sprintf(
						// translators: %s is an URL.
						__( 'You have not yet entered any e-readers. Go to the <a href=%s>Send to E-Reader settings</a> to add one.', 'friends' ),
						'"' . self_admin_url( 'admin.php?page=friends-send-to-e-reader' ) . '"'
					),
					array( 'a' => array( 'href' => array() ) )
				);
			} else {
			}
			?>
			</p>
	</td>
</tr>
	<?php
}

/**
 * Save the e-reader e-mail address to a friend.
 *
 * @param      Friend_User $friend  The friend.
 */
function friends_send_to_e_reader_edit_friend_submit( Friend_User $friend ) {
	$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
	if ( isset( $_POST['send-to-e-reader'] ) && isset( $ereaders[ $_POST['send-to-e-reader'] ] ) ) {
		update_user_option( $friend->ID, 'friends_send_to_e_reader', $_POST['send-to-e-reader'] );
	} else {
		delete_user_option( $friend->ID, 'friends_send_to_e_reader' );
	}
}

/**
 * Send a post to the E-Reader if enabled for the friend.
 *
 * @param      WP_Post $post   The post.
 */
function friends_send_to_e_reader_post_notification( WP_Post $post ) {
	if ( 'trash' === $post->post_status ) {
		return;
	}

	$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
	$id = get_user_option( 'friends_send_to_e_reader', $post->post_author );
	if ( false !== $id && isset( $ereaders[ $id ] ) ) {
		friends_send_post_to_e_reader( $post, $ereaders[ $id ]['email'] );
	}
}

/**
 * Strip Emojis from text
 *
 * @param      string $text   The text.
 *
 * @return     string  The text stripped off emojis.
 */
function friends_send_to_e_reader_strip_emojis( $text ) {
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

/**
 * Send a post to the E-Reader reachable at the particular e-mail address.
 *
 * @param      WP_Post $post    The post.
 * @param      string  $email   The email address.
 *
 * @return     bool     Whether it was sent successfully.
 */
function friends_send_post_to_e_reader( WP_Post $post, $email ) {
	require_once __DIR__ . '/class.friends-send-to-e-reader-template-loader.php';
	$template_loader = new Friends_Send_To_E_Reader_Template_Loader();

	$author = new Friend_User( $post->post_author );
	$author_name = $author->display_name;
	$override_author_name = apply_filters( 'friends_override_author_name', '', $author->display_name, $post->ID );
	if ( $override_author_name && trim( str_replace( $override_author_name, '', $author_name ) ) === $author_name ) {
		$author_name .= ' – ' . $override_author_name;
	}
	$url = get_permalink( $post );

	$format = 'epub';
	if ( preg_match( '/@(free\.)?kindle.com$/', $email ) || false !== strpos( $email, '+mobi' ) ) {
		if ( false !== strpos( $email, '+mobi' ) ) {
			$email = str_replace( '+mobi', '', $email );
		}
		$format = 'mobi';
	}

	ob_start();
	$template_loader->get_template_part(
		$format . '/header',
		null,
		array(
			'title'  => $post->post_title,
			'author' => $author_name,
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

	$dir = rtrim( sys_get_temp_dir(), '/' ) . '/friends_send_to_e_reader';
	if ( ! file_exists( $dir ) ) {
		mkdir( $dir );
	}

	$filename = sanitize_title( friends_send_to_e_reader_strip_emojis( $author_name . ' - ' . $post->post_title ) );

	if ( 'epub' === $format ) {
		$book = new PHPePub\Core\EPub();

		$book->setTitle( friends_send_to_e_reader_strip_emojis( $post->post_title ) );
		$book->setIdentifier( $url, PHPePub\Core\EPub::IDENTIFIER_URI );
		$book->setAuthor( $author_name, $author_name );

		$book->setSourceURL( $url );

		$book->addCSSFile( 'style.css', 'css', file_get_contents( $template_loader->get_template_part( 'epub/style', null, array(), false ) ) );

		$book->addChapter( $post->post_title, $filename . '.html', $content, false, PHPePub\Core\EPub::EXTERNAL_REF_ADD, $dir );
		$book->finalize();
		$book->saveBook( $filename . '.epub', $dir );
		$file = $dir . '/' . $filename . '.epub';
	} elseif ( 'mobi' === $format ) {
		require_once __DIR__ . '/MOBIClass/MOBI.php';
		$mobi = new MOBI();

		$mobi_content = new OnlineArticle( $url, $content );

		$mobi_content->setMetadata( 'title', friends_send_to_e_reader_strip_emojis( $post->post_title ) );
		$mobi_content->setMetadata( 'author', $author_name );
		$mobi_content->setMetadata( 'publishingdate', date( 'd-m-Y' ) );

		$mobi_content->setMetadata( 'source', $url );
		$mobi_content->setMetadata( 'publisher', get_bloginfo( 'name' ), get_bloginfo( 'url' ) );

		$mobi->setContentProvider( $mobi_content );

		$file = $dir . '/' . $filename . '.mobi';
		$mobi->save( $file );
	} else {
		return;
	}

	$friends = Friends::get_instance();
	$friends->notifications->send_mail( $email, $post->post_title, $post->post_title, array(), array( $file ) );

	unlink( $file );

	return true;
}

add_action(
	'friends_notification_manager_header',
	function() {
		$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
		if ( empty( $ereaders ) ) {
			return;
		}
		?>
			<th class="column-send-to-e-reader"><?php esc_html_e( 'Send to E-Reader' ); ?></th>
		<?php
	}
);

add_action(
	'friends_notification_manager_row',
	function( $friend ) {
		$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
		if ( empty( $ereaders ) ) {
			return;
		}
		$selected = get_user_option( 'friends_send_to_e_reader', $friend->ID );
		?>
		<td class="column-send-to-e-reader">
			<select name="send-to-e-reader[<?php echo esc_attr( $friend->ID ); ?>]">
				<option value="none">-</option>
				<?php foreach ( $ereaders as $id => $ereader ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $selected, $id ); ?>><?php echo esc_html( $ereader['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<?php
	}
);

add_action(
	'friends_notification_manager_after_form_submit',
	function( $friend_ids ) {
		$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
		if ( empty( $ereaders ) ) {
			return;
		}

		foreach ( $friend_ids as $friend_id ) {
			if ( ! isset( $_POST['send-to-e-reader'][ $friend_id ] ) ) {
				continue;
			}

			$ereader_notification = $_POST['send-to-e-reader'][ $friend_id ];
			if ( get_user_option( 'friends_send_to_e_reader', $friend_id ) !== $ereader_notification ) {
				update_user_option( $friend_id, 'friends_send_to_e_reader', $ereader_notification );
			}
		}
	}
);

add_action(
	'friends_entry_dropdown_menu',
	function() {
		$divider = '<li class="divider" data-content="' . esc_attr__( 'E-Reader', 'friends' ) . '"></li>';
		foreach ( get_option( 'friends-send-to-e-reader_readers', array() ) as $id => $ereader ) {
			echo $divider;
			$divider = '';
			?>
			<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" data-ereader="<?php echo esc_attr( $id ); ?>" class="friends-send-post-to-e-reader has-icon-right">
				  <?php
					echo esc_html(
						sprintf(
						// translators: %s is an E-Reader name.
							_x( 'Send to %s', 'e-reader', 'friends' ),
							$ereader['name']
						)
					);
					?>
				<i class="form-icon"></i></a></li>
			<?php
		}
	}
);

add_action(
	'admin_enqueue_scripts',
	function() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}
		wp_enqueue_script( 'friends-send-to-e-reader', plugins_url( 'friends-send-to-e-reader.js', __FILE__ ), array(), 1.0 );
	},
	40
);

add_action(
	'wp_enqueue_scripts',
	function() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}
		if ( is_user_logged_in() && ( Friends::on_frontend() ) ) {
			wp_enqueue_script( 'friends-send-to-e-reader', plugins_url( 'friends-send-to-e-reader.js', __FILE__ ), array( 'friends' ), 1.0 );
		}
	}
);

add_action(
	'admin_menu',
	function () {
		// Only show the menu if installed standalone.
		$friends_settings_exist = '' !== menu_page_url( 'friends-settings', false );
		if ( $friends_settings_exist ) {
			add_submenu_page(
				'friends-settings',
				__( 'Send to E-Reader', 'friends' ),
				__( 'Send to E-Reader', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				'friends_send_to_e_reader_about_page'
			);
		} else {
			add_menu_page( 'friends', __( 'Friends', 'friends' ), 'administrator', 'friends-send-to-e-reader', null, 'dashicons-groups', 3 );
			add_submenu_page(
				'friends-send-to-e-reader',
				__( 'About', 'friends' ),
				__( 'About', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				'friends_send_to_e_reader_about_page_with_friends_about'
			);
		}
	},
	50
);

add_action(
	'wp_ajax_send-post-to-e-reader',
	function() {
		$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
		if ( ! isset( $ereaders[ $_POST['ereader'] ] ) ) {
			wp_send_json_error( 'error' );
		}
		$result = friends_send_post_to_e_reader( get_post( $_POST['id'] ), $ereaders[ $_POST['ereader'] ]['email'] );
		if ( ! $result ) {
			wp_send_json_error( 'error' );
		}
		wp_send_json_success( 'E-Book sent' );
	}
);

add_filter( 'friends_send_to_e_reader', '__return_true' );
add_filter( 'notify_new_friend_post', 'friends_send_to_e_reader_post_notification', 10 );
add_action( 'friends_edit_friend_table_end', 'friends_send_to_e_reader_edit_friend', 10 );
add_action( 'friends_edit_friend_after_form_submit', 'friends_send_to_e_reader_edit_friend_submit', 10 );
