<?php
/**
 * Friends Send To E-Reader
 *
 * This contains the Send to E-Reader functions.
 *
 * @package Friends_Send_To_E_Reader
 */

/**
 * This is the class for the sending posts to an E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
class Friends_Send_To_E_Reader {
	/**
	 * Contains a reference to the Friends class.
	 *
	 * @var Friends
	 */
	private $friends;

	private $ereaders = null;
	private $ereader_classes = array();

	/**
	 * Constructor
	 *
	 * @param Friends $friends A reference to the Friends object.
	 */
	public function __construct( Friends $friends ) {
		$this->friends = $friends;
		$this->register_hooks();
	}

	/**
	 * Register the WordPress hooks
	 */
	private function register_hooks() {
		add_filter( 'notify_new_friend_post', 'post_notification', 10 );
		add_action( 'friends_edit_friend_table_end', 'edit_friend', 10 );
		add_action( 'friends_edit_friend_after_form_submit', 'edit_friend_submit', 10 );
		add_action( 'friends_notification_manager_header', array( $this, 'notification_manager_header' ) );
		add_action( 'friends_notification_manager_row', array( $this, 'notification_manager_row' ) );
		add_action( 'friends_notification_manager_after_form_submit', array( $this, 'notification_manager_after_form_submit' ) );
		add_action( 'friends_entry_dropdown_menu', array( $this, 'entry_dropdown_menu' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 40 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_ajax_send-post-to-e-reader', array( $this, 'ajax_send' ) );
	}

	public function register_ereader( $ereader_class ) {
		$this->ereader_classes[ $ereader_class ] = $ereader_class;
	}

	protected function get_ereaders() {
		if ( is_null( $this->ereaders ) ) {
			$this->ereaders = array();
			foreach ( get_option( 'friends-send-to-e-reader_readers', array() ) as $id => $ereader ) {
				if ( is_array( $ereader ) ) {
					if ( '@kindle.com' === substr( $ereader['email'], -11 ) || '@free.kindle.com' === substr( $ereader['email'], -16 ) || false !== strpos( $ereader['email'], '+mobi' ) ) {
						$ereader = new Friends_E_Reader_Kindle( $ereader['name'], $ereader['email'] );
					} elseif ( '@pbsync.com' === substr( $ereader['email'], -11 ) ) {
						$ereader = new Friends_E_Reader_Pocketbook( $ereader['name'], $ereader['email'] );
					} else {
						$ereader = new Friends_E_Reader_Generic_Email( $ereader['name'], $ereader['email'] );
					}
					$id = $ereader->get_id();
				}

				if ( $id ) {
					$this->ereaders[ $id ] = $ereader;
				}
			}
		}
		return $this->ereaders;
	}

	protected function update_ereaders( $ereaders ) {
		$this->ereaders = $ereaders;
		return update_option( 'friends-send-to-e-reader_readers', $ereaders );
	}

	protected function update_ereader( $id, $ereader ) {
		if ( ! isset( $this->ereaders[ $id ] ) ) {
			return false;
		}
		$this->ereaders[ $id ] = $ereader;
		return $this->update_ereaders( $this->ereaders );
	}

	protected function get_ereader( $id ) {
		$ereaders = $this->get_ereaders();
		return $ereaders[ $id ];
	}

	public function wp_enqueue_scripts() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}
		if ( is_user_logged_in() && ( Friends::on_frontend() ) ) {
			wp_enqueue_script( 'friends-send-to-e-reader', plugins_url( 'friends-send-to-e-reader.js', __FILE__ ), array( 'friends' ), 1.0 );
		}
	}

	public function admin_enqueue_scripts() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}
		wp_enqueue_script( 'friends-send-to-e-reader', plugins_url( 'friends-send-to-e-reader.js', __FILE__ ), array(), 1.0 );
	}

	public function admin_menu() {
		// Only show the menu if installed standalone.
		$friends_settings_exist = '' !== menu_page_url( 'friends-settings', false );
		if ( $friends_settings_exist ) {
			add_submenu_page(
				'friends-settings',
				__( 'Send to E-Reader', 'friends' ),
				__( 'Send to E-Reader', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				array( $this, 'about_page' )
			);
		} else {
			add_menu_page( 'friends', __( 'Friends', 'friends' ), 'administrator', 'friends-send-to-e-reader', null, 'dashicons-groups', 3 );
			add_submenu_page(
				'friends-send-to-e-reader',
				__( 'About', 'friends' ),
				__( 'About', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				array( $this, 'about_page_with_friends_about' )
			);
		}
	}

	public function notification_manager_header() {
		$ereaders = $this->get_ereaders();
		if ( empty( $ereaders ) ) {
			return;
		}
		?>
			<th class="column-send-to-e-reader"><?php esc_html_e( 'Send to E-Reader' ); ?></th>
		<?php
	}

	public function notification_manager_row( $friend ) {
		$ereaders = $this->get_ereaders();
		if ( empty( $ereaders ) ) {
			return;
		}
		$selected = get_user_option( 'friends_send_to_e_reader', $friend->ID );
		?>
		<td class="column-send-to-e-reader">
			<select name="send-to-e-reader[<?php echo esc_attr( $friend->ID ); ?>]">
				<option value="none">-</option>
				<?php foreach ( $ereaders as $id => $ereader ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $selected, $id ); ?>><?php echo esc_html( $ereader->get_name() ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<?php
	}

	public function notification_manager_after_form_submit( $friend_ids ) {
		$ereaders = $this->get_ereaders();
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

	public function entry_dropdown_menu() {
		$divider = '<li class="divider" data-content="' . esc_attr__( 'E-Reader', 'friends' ) . '"></li>';
		$ereaders = $this->get_ereaders();
		foreach ( $ereaders as $id => $ereader ) {
			echo $divider;
			$divider = '';
			?>
			<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" data-ereader="<?php echo esc_attr( $id ); ?>" class="friends-send-post-to-e-reader has-icon-right">
				<?php
					if ( $ereader instanceof Friends_E_Reader_Download ) {
						echo esc_html( $ereader->get_name() );
					} else {
						echo esc_html(
							sprintf(
								// translators: %s is an E-Reader name.
								_x( 'Send to %s', 'e-reader', 'friends' ),
								$ereader->get_name()
							)
						);
					}
				?>
				<i class="form-icon"></i></a></li>
			<?php
		}
	}

	function ajax_send() {
		$ereaders = $this->get_ereaders();
		if ( ! isset( $ereaders[ $_POST['ereader'] ] ) ) {
			wp_send_json_error( 'error' );
		}
		$ereader = $ereaders[ $_POST['ereader'] ];
		$result = $ereader->send_post( get_post( $_POST['id'] ) );
		if ( ! $result || is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}
		if ( $result instanceof Friends_E_Reader ) {
			$this->update_ereader( $_POST['ereader'], $result );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Display an about page for the plugin.
	 *
	 * @param      bool $display_about_friends  The display about friends section.
	 */
	public function about_page( $display_about_friends = false ) {
		$ereaders = $this->get_ereaders();

		$friends = Friends::get_instance();
		$nonce_value = 'friends-send-to-e-reader';
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
			$delete_ereaders = $ereaders;
			foreach ( $_POST['ereaders'] as $id => $ereader_data ) {
				$class = $ereader_data['class'];
				if ( ! $class || ! class_exists( $class ) || ! is_subclass_of( $class, 'Friends_E_Reader' ) ) {
					continue;
				}

				$ereader = $class::instantiate_from_field_data( $id, $ereader_data );
				$id = $ereader->get_id();
				if ( ! $id ) {
					continue;
				}

				if ( isset( $ereaders[ $id ] ) ) {
					unset( $delete_ereaders[ $id ] );
				}
				$ereaders[ $id ] = $ereader;
			}
			foreach ( $delete_ereaders as $id => $ereader ) {
				unset( $ereaders[ $id ] );
			}
			uasort(
				$ereaders,
				function( $a, $b ) {
					return strcmp( $a->get_name(), $b->get_name() );
				}
			);

			$this->update_ereaders( $ereaders );
		}
		$save_changes = __( 'Save Changes', 'friends' );

		?>
		<h1><?php _e( 'Friends Send to E-Reader', 'friends' ); ?></h1>

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
										<th><?php esc_html_e( 'E-Reader Type', 'friends' ); ?></th>
										<th><?php esc_html_e( 'Name', 'friends' ); ?></th>
										<th><?php esc_html_e( 'E-Mail address', 'friends' ); ?></th>
									</tr>
								</thead>
								<tbody>
								<?php
								foreach ( $ereaders as $id => $ereader ) :
									?>
									<tr>
										<td><input type="hidden" name="ereaders[<?php echo esc_attr( $id ); ?>][class]" value="<?php echo esc_attr( get_class( $ereader ) ); ?>" /><?php echo esc_html( $ereader::NAME ); ?> </td>
										<td><input type="text" class="name" name="ereaders[<?php echo esc_attr( $id ); ?>][name]" value="<?php echo esc_attr( $ereader->get_name() ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader Name', 'friends' ); ?>" /></td>
										<td><?php $ereader->render_input(); ?></td>
										<td><a href="" class="delete-reader" data-delete-text="
										<?php
										echo wp_kses(
											sprintf(
											// translators: %1$s is the button named "delete", %2$s is the user given name of an e-reader.
												__( 'Click %1$s to really delete the reader %2$s.', 'friends' ),
												'<em>' . esc_html( $save_changes ) . '</em>',
												'<em>' . esc_html( $ereader->get_name() )
											) . '</em>',
											array( 'em' => array() )
										);
										?>
											"><?php _e( 'delete' ); ?></a></td>
									</tr>
								<?php endforeach; ?>
								<tr class="template<?php echo empty( $ereaders ) ? '' : ' hidden'; ?>">
									<td>
										<select name="ereaders[new][class]" id="ereader-class">
											<option  disabled selected hidden><?php _e( 'Select your E-Reader', 'friends' ); ?></option>
											<?php foreach ( $this->ereader_classes as $ereader_class ) : ?>
												<option value="<?php echo esc_attr( $ereader_class ); ?>"><?php echo esc_html( $ereader_class::NAME ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td><input type="text" class="name" name="ereaders[new][name]" placeholder="<?php echo esc_attr__( 'Name', 'friends' ); ?>" size="30" aria-label="<?php esc_attr_e( 'E-Reader Name', 'friends' ); ?>" /></td>
									<td>
										<?php foreach ( $this->ereader_classes as $ereader_class ) : ?>
											<div id="<?php echo esc_html( $ereader_class ); ?>" class="hidden">
												<?php $ereader_class::render_template(); ?>
											</div>
										<?php endforeach; ?>
									</td>
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
	public function about_page_with_friends_about() {
		return $this->about_page( true );
	}

	/**
	 * Display an input field to enter the e-reader e-mail address.
	 *
	 * @param      Friend_User $friend  The friend.
	 */
	function edit_friend( Friend_User $friend ) {
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
					<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $selected, $id ); ?>><?php echo esc_html( $ereader->get_name() ); ?></option>
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
	function edit_friend_submit( Friend_User $friend ) {
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
	function post_notification( WP_Post $post ) {
		if ( 'trash' === $post->post_status ) {
			return;
		}

		$ereaders = get_option( 'friends-send-to-e-reader_readers', array() );
		$id = get_user_option( 'friends_send_to_e_reader', $post->post_author );
		if ( false !== $id && isset( $ereaders[ $id ] ) ) {
			$this->send_post( $post, $ereaders[ $id ]['email'] );
		}
	}

	private function upload_tolino( $access_token, $hardware_id, $file ) {
		// curl -vL -H "t_auth_token: $ACCESS_TOKEN" -H "hardware_id: $HARDWARE_ID" -H "reseller_id: $RESELLER_ID" -F "file=@$ZEIT_EPUB" https://bosh.pageplace.de/bosh/rest/upload

	}

}
