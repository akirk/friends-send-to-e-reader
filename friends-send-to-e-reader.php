<?php
/**
 * Plugin name: Friends Send to E-Reader
 * Plugin author: Alex Kirk
 * Plugin URI: https://github.com/akirk/friends-send-to-e-reader
 * Version: 0.5
 * Requires Plugins: friends
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
define( 'FRIENDS_SEND_TO_E_READER_VERSION', 0.5 );

require 'libs/autoload.php';
require_once __DIR__ . '/includes/class-send-to-e-reader.php';
require_once __DIR__ . '/includes/class-e-reader.php';

add_filter( 'friends_send_to_e_reader', '__return_true' );
add_action(
	'friends_loaded',
	function( $friends ) {
		$send_to_e_reader = new Friends\Send_To_E_Reader( $friends );

		require_once __DIR__ . '/includes/class-e-reader-generic-email.php';
		$send_to_e_reader->register_ereader( 'Friends\E_Reader_Generic_Email' );

		require_once __DIR__ . '/includes/class-e-reader-kindle.php';
		$send_to_e_reader->register_ereader( 'Friends\E_Reader_Kindle' );

		require_once __DIR__ . '/includes/class-e-reader-pocketbook.php';
		$send_to_e_reader->register_ereader( 'Friends\E_Reader_Pocketbook' );

		/*
		Not ready.
		require_once __DIR__ . '/includes/class-e-reader-tolino.php';
		$send_to_e_reader->register_ereader( 'Friends\E_Reader_Tolino' );
		*/

		require_once __DIR__ . '/includes/class-e-reader-download.php';
		$send_to_e_reader->register_ereader( 'Friends\E_Reader_Download' );
	}
);
