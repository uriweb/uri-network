<?php
/**
 * @package URI_Network
 * @version 1.0
 */
/*
Plugin Name: URI Network
Description: Provides tools to glean data about the network
Author: John Pennypacker
Author URI: https://www.uri.edu/
*/

// Block direct requests
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once ( plugin_dir_path( __FILE__ ) . '/inc/general.php' );
require_once ( plugin_dir_path( __FILE__ ) . '/inc/options.php' );
require_once ( plugin_dir_path( __FILE__ ) . '/inc/users.php' );
require_once ( plugin_dir_path( __FILE__ ) . '/inc/admin.php' );

$URI_Network = NULL; // initialize the table object variable





/**
 * Enqueues javascript for front end.
 */
function _uri_network_js() {
	$file = plugin_dir_path( __FILE__ ) . 'js/sort-table.js';
	$cache_buster = filemtime( $file );
	wp_register_script( 'uri-network-stats-table-sort', plugins_url( '/js/sort-table.js', __FILE__ ), array(), $cache_buster, TRUE );
	wp_enqueue_script( 'uri-network-stats-table-sort' );
}

