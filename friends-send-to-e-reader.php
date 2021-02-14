<?php
/**
 * Plugin name: Friends Send to E-Reader
 * Plugin author: Alex Kirk
 * Plugin URI: https://github.com/akirk/friends-send-to-e-reader
 * Version: 0.1
 *
 * Description: Send friend posts to your e-reader.
 *
 * License: GPL2
 * Text Domain: friends-send-to-e-reader
 * Domain Path: /languages/
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
	$nonce_value = 'send-to-e-reader';
	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
		update_option( 'friends-send-to-e-reader_default_email', $_POST['email'] );
	}

	?><h1><?php _e( 'Friends Send to E-Reader', 'send-to-e-reader' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( $nonce_value ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default E-Mail Address', 'send-to-e-reader' ); ?></th>
					<td>
						<fieldset>
							<label for="email">
								<input name="email" type="email" id="email" value="<?php echo esc_html( get_option( 'friends-send-to-e-reader_default_email' ) ); ?>"  required size="60" placeholder="<?php esc_html_e( 'Enter an e-mail address that will reach your e-reader', 'friends-send-to-e-reader' ); ?>" />
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'send-to-e-reader' ); ?>">
		</p>
	</form>

	<?php if ( $display_about_friends ) : ?>
		<p>
			<?php
			echo wp_kses(
				// translators: %s: URL to the Friends Plugin page on WordPress.org.
				sprintf( __( 'The Friends plugin is all about connecting with friends and news. Learn more on its <a href=%s>plugin page on WordPress.org</a>.', 'send-to-e-reader' ), '"https://wordpress.org/plugins/friends" target="_blank" rel="noopener noreferrer"' ),
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
		sprintf( __( 'This plugin is largely powered by the open source project <a href=%s>PHPePub</a>.', 'send-to-e-reader' ), '"https://github.com/Grandt/PHPePub" target="_blank" rel="noopener noreferrer"' ),
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
	?>
<tr>
	<th scope="row"><?php esc_html_e( 'Send to E-Reader', 'friends' ); ?></th>
	<td><input type="email" name="friends_send_to_e_reader_email" value="<?php echo esc_html( get_user_option( 'friends_send_to_e_reader_email', $friend->ID ) ); ?>" size=60 placeholder="<?php esc_html_e( 'Leave empty for no notification', 'friends-send-to-e-reader' ); ?>" />
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is an e-mail address.
					__( 'Enter the e-mail address that will reach your e-reader (e.g. %s).', 'friends-send-to-e-reader' ),
					get_option( 'friends-send-to-e-reader_default_email', '@pbsync.com' )
				)
			);
			?>
			</p>
	</td>
</tr>
	<?php
}

/**
 * Save the e-reader e-mail address to a friend.
 *
 * @param      Friend_User $friend  The friend/
 */
function friends_send_to_e_reader_edit_friend_submit( Friend_User $friend ) {
	if ( filter_input( INPUT_POST, 'friends_send_to_e_reader_email', FILTER_VALIDATE_EMAIL ) ) {
		update_user_option( $friend->ID, 'friends_send_to_e_reader_email', filter_input( INPUT_POST, 'friends_send_to_e_reader_email', FILTER_SANITIZE_EMAIL ) );
	} else {
		delete_user_option( $friend->ID, 'friends_send_to_e_reader_email' );
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

	$email = get_user_option( 'friends_send_to_e_reader_email', $post->post_author );
	if ( $email ) {
		friends_send_post_to_e_reader( $post, $email );
	}
}

/**
 * Send a post to the E-Reader reachable at the particular e-mail address.
 *
 * @param      WP_Post $post    The post.
 * @param      string  $email   The email address.
 * @param      string  $format  The format (currently only epub supported).
 *
 * @return     bool     Whether it was sent successfully.
 */
function friends_send_post_to_e_reader( WP_Post $post, $email, $format = 'epub' ) {
	require_once __DIR__ . '/class.friends-send-to-e-reader-template-loader.php';
	$template_loader = new Friends_Send_To_E_Reader_Template_Loader();

	if ( 'epub' !== $format ) {
		return;
	}

	$dir = rtrim( sys_get_temp_dir(), '/' ) . '/friends_send_to_e_reader';
	if ( ! file_exists( $dir ) ) {
		mkdir( $dir );
	}

	$filename = sanitize_title_with_dashes( $post->post_title );
	$author = new WP_User( $post->post_author );

	$book = new PHPePub\Core\EPub();

	$book->setTitle( $post->post_title );
	$book->setIdentifier( $post->permalink, PHPePub\Core\EPub::IDENTIFIER_URI );
	$book->setAuthor( $author->display_name, $author->display_name );

	$book->setSourceURL( $post->permalink );

	$book->addCSSFile( 'style.css', 'css', file_get_contents( $template_loader->get_template_part( 'epub/style', null, array(), false ) ) );

	ob_start();

	$template_loader->get_template_part(
		'epub/header',
		null,
		array(
			'title' => $post->post_title,
		)
	);

	echo $post->post_content;

	$template_loader->get_template_part(
		'epub/footer',
		null,
		array(
			'url' => $post->permalink,
		)
	);
	$content = ob_get_contents();
	ob_end_clean();

	$book->addChapter( $post->post_title, $filename . '.html', $content, false, PHPePub\Core\EPub::EXTERNAL_REF_ADD, $dir );
	$book->finalize();
	$book->saveBook( $filename . '.epub', $dir );
	$file = $dir . '/' . $filename . '.epub';

	$friends = Friends::get_instance();
	$friends->notifications->send_mail( $email, $post->post_title, $post->post_title, array(), array( $file ) );

	unlink( $file );

	return true;
}

add_action(
	'friends_entry_dropdown_menu',
	function() {
		if ( get_option( 'friends-send-to-e-reader_default_email' ) ) {
			?>
			<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" class="friends-send-post-to-e-reader"><?php _e( 'Send to E-Reader', 'friends-send-to-e-reader' ); ?></a></li>
			<?php
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function() {
		if ( is_user_logged_in() ) {
			wp_enqueue_script( 'send-to-e-reader', plugins_url( 'friends-send-to-e-reader.js', __FILE__ ), array( 'friends' ), 1.0 );
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
				__( 'Plugin: Send to E-Reader', 'send-to-e-reader' ),
				__( 'Plugin: Send to E-Reader', 'send-to-e-reader' ),
				'administrator',
				'friends-send-to-e-reader',
				'friends_send_to_e_reader_about_page'
			);
		} else {
			add_menu_page( 'friends', __( 'Friends', 'send-to-e-reader' ), 'administrator', 'friends-settings', null, 'dashicons-groups', 3.73 );
			add_submenu_page(
				'friends-settings',
				__( 'About', 'send-to-e-reader' ),
				__( 'About', 'send-to-e-reader' ),
				'administrator',
				'friends-settings',
				'friends_send_to_e_reader_about_page_with_friends_about'
			);
		}
	},
	50
);

add_action(
	'wp_ajax_send-post-to-e-reader',
	function() {
		$result = friends_send_post_to_e_reader( get_post( $_POST['id'] ), get_option( 'friends-send-to-e-reader_default_email' ) );
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
