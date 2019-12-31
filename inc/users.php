<?php
/**
 * @package URI_Network
 */

// Block direct requests
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Create a shortcode to query metadata of sites across the network
 * [uri_network_users]
 * @param arr shortcode attriibutes ('option' is the only currently valid attribute)
 */
function uri_network_users_shortcode( $atts ) {
	_uri_network_js();
	$result = uri_network_users_query();
	return uri_network_users_output( $result );
}
add_shortcode( 'uri_network_users', 'uri_network_users_shortcode' );


/**
 * Query the db for user information
 * @return arr
 */
function uri_network_users_query() {
	global $wpdb;

	$site_id = '%';
	if( isset( $_GET['site_id'] )&& is_numeric( $_GET['site_id'] ) ) {
		$site_id = $_GET['site_id'];
	}
	$prefix = $wpdb->get_blog_prefix();
	$bid = $prefix . $site_id . '_';

	$uid = '';
	if( isset( $_GET['user_id'] )&& is_numeric( $_GET['user_id'] ) ) {
		$uid = ' AND user_id = ' . $_GET['user_id'];
	}

	$sql = 'SELECT u.ID, u.user_email, u.user_nicename, u.user_login, u.display_name, m.meta_key, m.meta_value, b.path, SUBSTRING_INDEX(SUBSTRING_INDEX(m.meta_key,"_", 2), "_",-1) as site_id 
			FROM ' . $prefix . 'usermeta m 
			LEFT JOIN ' . $prefix . 'users u on m.user_id = u.ID
			LEFT JOIN ' . $prefix . 'blogs b ON b.blog_id = SUBSTRING_INDEX(SUBSTRING_INDEX(m.meta_key,"_", 2), "_",-1)
			WHERE meta_key LIKE "' . $bid . 'capabilities"' . $uid;

	// cache the results of the union of all these select statements
	$results = $wpdb->get_results( $sql, ARRAY_A );

	return $results;

}

/**
 * Output the data in a pretty way
 * @param arr $result to iterate over
 * @return str HTML string
 */
function uri_network_users_output( $result ) {

	if( 0 === count($result) ) {
		return '<p>No results found.</p>';
	}

	$output = '<table>';
	$output .= '<thead><tr>';
	$output .= '<th>User ID</th>';
	$output .= '<th>Username</th>';
	$output .= '<th>Name</th>';
	$output .= '<th>Role</th>';
	$output .= '<th>Site ID</th>';
	$output .= '<th>Site</th>';
	$output .= '</tr></thead>';
	$output .= '<tbody>';
	
	foreach($result as $row) {

		$roles = unserialize($row['meta_value']);

		$output .= '<tr>';
		$output .= '<th>' . $row['ID'] . '</th>';
		if ( ! empty( $row['user_email'] ) ) {
			$output .= '<td><a href="mailto:' . $row['user_email'] . '">' . $row['user_login'] . '</a></td>';
		} else {
			$output .= '<td>' . $row['user_login'] . '</td>';
		}
		$output .= '<td>' . $row['display_name'] . '</td>';
		$output .= '<td>' . implode( ' ', array_keys( $roles ) ) . '</td>';
		$output .= '<td>' . $row['site_id'] . '</td>';
		$output .= '<td><a href="' . $row['path'] . '">' . $row['path'] . '</a></td>';
		$output .= '</tr>';
	}
	$output .= '</tbody>';
	$output .= '</table>';

	return $output;
}
