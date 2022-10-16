<?php
/**
 * Friends Send To E-Reader
 *
 * This contains the Send to E-Reader functions.
 *
 * @package Friends_Send_To_E_Reader
 */

namespace Friends;

/**
 * This is the class for the sending posts to an E-Reader for the Friends Plugin.
 *
 * @since 0.3
 *
 * @package Friends_Send_To_E_Reader
 * @author Alex Kirk
 */
class Send_To_E_Reader {
	/**
	 * Contains a reference to the Friends class.
	 *
	 * @var Friends
	 */
	private $friends;

	const POST_META = 'friends-sent-to-ereader';
	const EREADERS_OPTION = 'friends-send-to-e-reader_readers';
	const READING_SUMMARY_OPTION = 'friends-send-to-e-reader_reading-summary';
	const CRON_OPTION = 'friends-send-to-e-reader_cron';

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
		add_filter( 'notify_new_friend_post', array( $this, 'post_notification' ), 10 );
		add_action( 'friends_edit_friend_notifications_table_end', array( $this, 'edit_friend_notifications' ), 10 );
		add_action( 'users_edit_post_collection_table_end', array( $this, 'users_edit_post_collection' ), 10 );
		add_action( 'friends_edit_friend_notifications_after_form_submit', array( $this, 'edit_friend_notifications_submit' ), 10 );
		add_action( 'friends_notification_manager_header', array( $this, 'notification_manager_header' ) );
		add_action( 'friends_notification_manager_row', array( $this, 'notification_manager_row' ) );
		add_action( 'friends_notification_manager_after_form_submit', array( $this, 'notification_manager_after_form_submit' ) );
		add_action( 'friends_entry_dropdown_menu', array( $this, 'entry_dropdown_menu' ) );
		add_action( 'friends_template_paths', array( $this, 'friends_template_paths' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 40 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'print_dialog' ) );
		add_action( 'wp_ajax_send-post-to-e-reader', array( $this, 'ajax_send' ) );
		add_action( 'friends_author_header', array( $this, 'friends_author_header' ), 10, 2 );
	}

	public function register_ereader( $ereader_class ) {
		$this->ereader_classes[ $ereader_class ] = $ereader_class;
	}

	protected function get_active_ereaders() {
		return array_filter(
			$this->get_ereaders(),
			function ( $ereader ) {
				return $ereader->active;
			}
		);
	}

	protected function get_active_email_ereaders() {
		return array_filter(
			$this->get_active_ereaders(),
			function ( $ereader ) {
				return $ereader instanceof E_Reader_Generic_Email;
			}
		);
	}

	protected function get_ereaders() {
		if ( is_null( $this->ereaders ) ) {
			$this->ereaders = array();
			foreach ( get_option( self::EREADERS_OPTION, array() ) as $id => $ereader ) {
				if ( get_class( $ereader ) === '__PHP_Incomplete_Class' ) {
					// We need to update these to new class names.
					$this->ereaders = null;
					$alloptions = wp_load_alloptions();
					if ( isset( $alloptions[ self::EREADERS_OPTION ] ) ) {
						$alloptions[ self::EREADERS_OPTION ] = str_replace( 'Friends_', 'Friends\\', $alloptions[ self::EREADERS_OPTION ] );
						$this->update_ereaders( unserialize( $alloptions[ self::EREADERS_OPTION ] ) );
						return $this->get_ereaders();
					}
				}
				if ( is_array( $ereader ) ) {
					if ( false !== strpos( $ereader['email'], '+mobi' ) ) {
						$ereader = new E_Reader_Kindle( $ereader['name'], $ereader['email'] );
					} elseif ( '@pbsync.com' === substr( $ereader['email'], -11 ) ) {
						$ereader = new E_Reader_Pocketbook( $ereader['name'], $ereader['email'] );
					} else { // '@kindle.com' === substr( $ereader['email'], -11 ) || '@free.kindle.com' === substr( $ereader['email'], -16 )
						$ereader = new E_Reader_Generic_Email( $ereader['name'], $ereader['email'] );
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
		return update_option( self::EREADERS_OPTION, $ereaders );
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
		if ( ! class_exists( 'Friends\Friends' ) ) {
			return;
		}
		if ( is_user_logged_in() && Friends::on_frontend() ) {
			$handle = 'friends-send-to-e-reader';
			$file = 'friends-send-to-e-reader.js';
			$version = FRIENDS_SEND_TO_E_READER_VERSION;
			wp_enqueue_script( $handle, plugins_url( $file, __DIR__ ), array( 'friends' ), apply_filters( 'friends_debug_enqueue', $version, $handle, dirname( __DIR__ ) . '/' . $file ) );
		}
	}

	public function print_dialog() {
		if ( ! class_exists( 'Friends\Friends' ) ) {
			return;
		}
		if ( is_user_logged_in() && Friends::on_frontend() ) {
			global $wp_query;
			$friend_name = __( 'Friend Post', 'friends' );
			if ( $this->friends->frontend->author ) {
				$friend_name = $this->friends->frontend->author->display_name;
			}
			Friends::template_loader()->get_template_part( 'frontend/ereader/dialog', null, array(
				'friend_name' => $friend_name,
			) );
		}
	}

	public function admin_enqueue_scripts() {
		if ( ! class_exists( 'Friends\Friends' ) ) {
			return;
		}
		$handle = 'friends-send-to-e-reader';
		$file = 'friends-send-to-e-reader.js';
		$version = FRIENDS_SEND_TO_E_READER_VERSION;
			wp_enqueue_script( $handle, plugins_url( $file, __DIR__ ), array( 'friends-admin' ), apply_filters( 'friends_debug_enqueue', $version, $handle, dirname( __DIR__ ) . '/' . $file ) );
	}

	public function admin_menu() {
		// Only show the menu if installed standalone.
		$friends_settings_exist = '' !== menu_page_url( 'friends', false );
		if ( $friends_settings_exist ) {
			add_submenu_page(
				'friends',
				__( 'E-Readers', 'friends' ),
				__( 'E-Readers', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				array( $this, 'configure_ereaders' )
			);
			add_submenu_page(
				'friends',
				__( 'E-Reader Settings', 'friends' ),
				__( 'E-Reader Settings', 'friends' ),
				'administrator',
				'friends-send-to-e-reader-settings',
				array( $this, 'settings' )
			);
		} else {
			add_menu_page( 'friends', __( 'Friends', 'friends' ), 'administrator', 'friends-send-to-e-reader', null, 'dashicons-groups', 3 );
			add_submenu_page(
				'friends-send-to-e-reader',
				__( 'About', 'friends' ),
				__( 'About', 'friends' ),
				'administrator',
				'friends-send-to-e-reader',
				array( $this, 'configure_ereaders_with_friends_about' )
			);
		}
	}

	public function notification_manager_header() {
		$ereaders = $this->get_ereaders();
		if ( empty( $ereaders ) ) {
			return;
		}
		?>
			<th class="column-send-to-e-reader"><?php esc_html_e( 'Send to E-Reader', 'friends' ); ?></th>
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

	public function friends_template_paths( $paths ) {
		$c = 50;
		$my_path = FRIENDS_SEND_TO_E_READER_PLUGIN_DIR . 'templates/';
		while ( isset( $paths[ $c ] ) && $my_path !== $paths[ $c ] ) {
			$c += 1;
		}
		$paths[ $c ] = $my_path;
		return $paths;
	}

	public function get_unsent_posts() {
		global $wp_query;
		$query = new \WP_Query(
			array_merge(
				$wp_query->query_vars,
				array(
					'nopaging'     => true,
					'meta_key'     => self::POST_META,
					'meta_compare' => 'NOT EXISTS',
				)
			)
		);
		return $query->get_posts();
	}

	public function entry_dropdown_menu() {
		$divider = '<li class="divider" data-content="' . esc_attr__( 'E-Reader', 'friends' ) . '"></li>';
		$already_sent = get_post_meta( get_the_ID(), self::POST_META );
		if ( $already_sent ) {
			$divider = '<li class="divider" data-content="' . esc_attr(
				sprintf(
					// translators: %s is a date.
					__( 'E-Reader: Sent on %s', 'friends' ),
					date_i18n( __( 'M j' ) ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				)
			) . '"></li>';
		}
		$ereaders = $this->get_active_ereaders();
		foreach ( $ereaders as $id => $ereader ) {
			echo wp_kses(
				$divider,
				array(
					'li' => array(
						'class'        => array(),
						'data-content' => array(),
					),
				)
			);
			$divider = '';
			?>
			<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" data-ereader="<?php echo esc_attr( $id ); ?>" class="friends-send-post-to-e-reader has-icon-right">
				<?php
				if ( $ereader instanceof E_Reader_Download ) {
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
		?>
		<li class="menu-item">
			<label class="form-switch">
				<input type="checkbox" name="multi-entry"><i class="form-icon off"></i> <?php esc_html_e( 'Include all posts above', 'friends' ); ?>
			</label>
		</li>
		<li class="menu-item">
			<label class="form-switch">
				<input type="checkbox" name="reading-summary" <?php checked( $this->reading_summary_enabled() ); ?>><i class="form-icon on"></i> <?php esc_html_e( 'Create a reading summary draft', 'friends' ); ?>
			</label>
		</li>
		<?php
	}

	function ajax_send() {
		$ereaders = $this->get_ereaders();
		if ( ! isset( $ereaders[ $_POST['ereader'] ] ) ) {
			wp_send_json_error( __( 'E-Reader not configured', 'friends' ) );
			exit;
		}
		if ( empty( $_POST['ids'] ) ) {
			wp_send_json_error( __( 'No post ids specified', 'friends' ) );
			exit;
		}
		$posts = array_map( 'get_post', (array) $_POST['ids'] );
		$ereader = $ereaders[ $_POST['ereader'] ];
		$result = $ereader->send_posts(
			$posts,
			empty( $_POST['title'] ) ? false : $_POST['title'],
			empty( $_POST['author'] ) ? false : $_POST['author']
		);
		if ( ! $result || is_wp_error( $result ) ) {
			wp_send_json_error( $result );
			exit;
		}
		if ( isset( $_POST['reading_summary'] ) && $_POST['reading_summary'] && is_array( $result ) ) {
			$this->create_reading_summary( $posts, $result['title'], $result['author'] );
		}

		if ( $result instanceof E_Reader ) {
			$this->update_ereader( $_POST['ereader'], $result );
		}
		wp_send_json_success( $result );
	}

	/**
	 * Display the E-Reader Settings header
	 *
	 * @param      string $active  The active page.
	 */
	private function settings_header( $active ) {
		Friends::template_loader()->get_template_part(
			'admin/settings-header',
			null,
			array(
				'active' => $active,
				'title'  => __( 'Send to E-Reader', 'friends' ),
				'menu'   => array(
					'E-Readers' => 'friends-send-to-e-reader',
					'Settings'  => 'friends-send-to-e-reader-settings',
				),
			)
		);
	}

	protected function create_reading_summary( $posts, $title, $author ) {
		$post_content = array();
		foreach ( $posts as $post ) {
			update_post_meta( $post->ID, self::POST_META, time() );

			$content = '<!-- wp:heading {"level":4} -->' . PHP_EOL . '<h4><a href="' . esc_url( get_the_permalink( $post ) ) . '">';

			$content .= wp_kses_post( get_the_title( $post ) );
			$content .= '</a></h4>' . PHP_EOL;
			$content .= '<!-- /wp:heading -->';
			$content .= '<!-- wp:quote -->' . PHP_EOL . '<blockquote class="wp-block-quote"><p>' . wp_kses_post( get_the_excerpt( $post ) );
			$content .= '</p></blockquote>' . PHP_EOL;
			$content .= '<!-- /wp:quote -->';
			$content .= '<!-- wp:paragraph -->' . PHP_EOL . '<p>';
			$content .= apply_filters( 'friends_send_to_ereader_reading_summary_paragraph_content', '', $post );
			$content .= '</p>' . PHP_EOL;
			$content .= '<!-- /wp:paragraph -->';
			$post_content[] = apply_filters( 'friends_send_to_e_reader_summary_entry', $content, $post );
		}

		$replace = array(
			'$date' => date_i18n( __( 'F j, Y' ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'$author' => $author,
			'$title' => $title,
		);
		wp_insert_post(
			array(
				'post_title'   => str_replace(
					array_keys( $replace ),
					array_values( $replace ),
					$this->reading_summary_title()

				),
				'post_status'  => 'draft',
				'post_content' => implode( PHP_EOL, $post_content ),
			)
		);
	}

	/**
	 * Whether the Reading Summary is enabled.
	 */
	protected function reading_summary_enabled() {
		$summary = get_option( self::READING_SUMMARY_OPTION, array() );
		return isset( $summary['enabled'] ) && $summary['enabled'];
	}

	/**
	 * Whether the Reading Summary is enabled.
	 */
	protected function reading_summary_title() {
		$summary = get_option( self::READING_SUMMARY_OPTION, array() );
		return ! empty( $summary['title'] ) ? $summary['title'] : sprintf(
			// translators: %s is a date.
			__( 'Reading List of %s', 'friends' ),
			'$date'
		);
	}

	/**
	 * Display the configure e-readers page for the plugin.
	 */
	public function settings() {
		$nonce_value = 'friends-send-to-e-reader';

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
			$summary = array();

			$summary['enabled'] = isset( $_POST['reading_summary'] ) && $_POST['reading_summary'];
			$summary['title'] = sanitize_text_field( wp_unslash( $_POST['reading_summary_title'] ) );

			update_option( self::READING_SUMMARY_OPTION, $summary );
		}
		$summary = get_option( self::READING_SUMMARY_OPTION, array() );

		$this->settings_header( 'friends-send-to-e-reader-settings' );

		Friends::template_loader()->get_template_part(
			'admin/ereader-settings',
			null,
			array(
				'nonce_value'           => $nonce_value,
				'reading_summary'       => $this->reading_summary_enabled(),
				'reading_summary_title' => $this->reading_summary_title(),
				// 'cron_day' => $this->cron_day(),
				// 'cron_ereader' => $this->cron_ereader(),
			)
		);

		Friends::template_loader()->get_template_part( 'admin/settings-footer' );
	}

	/**
	 * Display the configure e-readers page for the plugin.
	 *
	 * @param      bool $display_about_friends  The display about friends section.
	 */
	public function configure_ereaders( $display_about_friends = false ) {
		$ereaders = $this->get_ereaders();

		$friends = Friends::get_instance();
		$nonce_value = 'friends-send-to-e-reader';
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
			$delete_ereaders = $ereaders;
			foreach ( $_POST['ereaders'] as $id => $ereader_data ) {
				if ( ! isset( $ereader_data['class'] ) ) {
					continue;
				}

				$class = wp_unslash( $ereader_data['class'] );
				if ( ! $class || ! class_exists( $class ) || ! is_subclass_of( $class, 'Friends\E_Reader' ) ) {
					continue;
				}

				if ( 'new' === $id && isset( $_POST['ereaders'][ 'new' . $class ] ) ) {
					$ereader_data = array_merge( $ereader_data, $_POST['ereaders'][ 'new' . $class ] );
				}

				$ereader = $class::instantiate_from_field_data( $id, $ereader_data );
				$id = $ereader->get_id();
				if ( ! $id ) {
					continue;
				}

				if ( isset( $ereaders[ $id ] ) ) {
					unset( $delete_ereaders[ $id ] );
				}
				$ereader->active = isset( $ereader_data['active'] ) && $ereader_data['active'];
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

		$this->settings_header( 'friends-send-to-e-reader' );

		Friends::template_loader()->get_template_part(
			'admin/configure-ereaders',
			null,
			array(
				'ereaders'              => $ereaders,
				'nonce_value'           => $nonce_value,
				'friends'               => $friends,
				'display_about_friends' => $display_about_friends,
				'ereader_classes'       => $this->ereader_classes,
			)
		);

		Friends::template_loader()->get_template_part( 'admin/settings-footer' );
	}

	/**
	 * Display an about page for the plugin with the friends section.
	 */
	public function configure_ereaders_with_friends_about() {
		return $this->configure_ereaders( true );
	}

	/**
	 * Display an input field to enter the e-reader e-mail address.
	 *
	 * @param      User $friend  The friend.
	 */
	function users_edit_post_collection( User $friend ) {
		Friends::template_loader()->get_template_part(
			'admin/automatic-sending',
			null,
			array(
				'ereaders' => $this->get_active_email_ereaders(),
			)
		);
	}

	/**
	 * Display an input field to enter the e-reader e-mail address.
	 *
	 * @param      User $friend  The friend.
	 */
	function edit_friend_notifications( User $friend ) {
		Friends::template_loader()->get_template_part(
			'admin/edit-notifications-ereader',
			null,
			array(
				'ereaders' => $this->get_active_email_ereaders(),
				'selected' => get_user_option( 'friends_send_to_e_reader', $friend->ID ),
			)
		);
		Friends::template_loader()->get_template_part(
			'admin/automatic-sending',
			null,
			array(
				'ereaders' => $this->get_active_email_ereaders(),
			)
		);
	}

	/**
	 * Save the e-reader e-mail address to a friend.
	 *
	 * @param      User $friend  The friend.
	 */
	function edit_friend_notifications_submit( User $friend ) {
		$ereaders = get_option( self::EREADERS_OPTION, array() );
		if ( isset( $_POST['send-to-e-reader'] ) && isset( $ereaders[ $_POST['send-to-e-reader'] ] ) ) {
			update_user_option( $friend->ID, 'friends_send_to_e_reader', $_POST['send-to-e-reader'] );
		} else {
			delete_user_option( $friend->ID, 'friends_send_to_e_reader' );
		}
	}

	/**
	 * Send a post to the E-Reader if enabled for the friend.
	 *
	 * @param      \WP_Post $post   The post.
	 */
	function post_notification( \WP_Post $post ) {
		if ( 'trash' === $post->post_status ) {
			return;
		}

		$ereaders = get_option( self::EREADERS_OPTION, array() );
		$id = get_user_option( 'friends_send_to_e_reader', $post->post_author );
		if ( false !== $id && isset( $ereaders[ $id ] ) ) {
			$ereaders[ $id ]->send_posts( array( $post ), $ereaders[ $id ]['email'] );
		}
	}

	public function friends_author_header( User $friend_user, $args ) {
		Friends::template_loader()->get_template_part(
			'frontend/ereader/author-header',
			null,
			array_merge(
				array(
					'ereaders'     => $this->get_active_ereaders(),
					'unsent_posts' => $this->get_unsent_posts(),
					'friend'       => $friend_user,
				),
				$args
			)
		);
	}

}
