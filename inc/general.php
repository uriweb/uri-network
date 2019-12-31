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
 * [uri_network_stats]
 * @param arr shortcode attriibutes ('option' is the only currently valid attribute)
 */
function uri_network_stats_shortcode( $atts ) {
	_uri_network_js();
	$result = uri_network_general_query();
	return uri_network_general_output( $result );
}
add_shortcode( 'uri_network_stats', 'uri_network_stats_shortcode' );


/**
 * Query the db for blogs count
 * @return arr
 */
function uri_network_blogs_count() {
	$count = get_sites( array( 'count'=>TRUE, 'archived'=>0 ) );
	return $count;
}

/**
 * Query the db for general blogs information
 * @return arr
 */
function uri_network_blogs_query() {
	return get_sites( array( 'archived'=>0 ) );
}

/**
 * Validate / sanitize page options
 * @param arr
 * @return arr
 */
function uri_network_scrub_general_options( $options ) {
	$options['per_page'] = ( isset( $options['per_page'] ) && (int)$options['per_page'] > 0 ) ? $options['per_page'] : 10;
	$options['current_page'] = ( isset( $options['current_page'] ) && (int)$options['current_page'] > 1 ) ? $options['current_page'] : 1;
	return $options;
}

/**
 * Query the db for general information
 * @param arr
 * @return arr
 */
function uri_network_general_query( $options ) {
	global $wpdb;

	$options = uri_network_scrub_general_options( $options );

	// get all the blog ids from the blogs table
	$blogs = uri_network_blogs_query();

	// build a sql statement for each blog options table, adding in the blog id for each row
	$sqls = array();
	foreach ( $blogs as $blog_row ) {
		$prefix = $wpdb->get_blog_prefix(0);
		$bid = $wpdb->get_blog_prefix($blog_row->blog_id);
		$sqls[] = 'SELECT option_value AS home,
					(SELECT option_value FROM ' . $bid . 'options WHERE option_name="siteurl") AS url,
					(SELECT option_value FROM ' . $bid . 'options WHERE option_name="blogname") AS name,
					(SELECT option_value FROM ' . $bid . 'options WHERE option_name="template") AS theme,
					(SELECT post_modified FROM ' . $bid . 'posts ORDER BY post_modified DESC LIMIT 1) AS modified,
					(SELECT COUNT(ID) FROM ' . $bid . 'posts WHERE post_status = "publish" AND post_type="page") AS pages,
					(SELECT COUNT(ID) FROM ' . $bid . 'posts WHERE post_status = "publish" AND post_type="post") AS posts,
					(SELECT COUNT(term_id) FROM ' . $bid . 'terms) AS cats,
					(SELECT COUNT(user_id) FROM ' . $prefix . 'usermeta WHERE meta_key = "' . $bid . 'capabilities" AND meta_value NOT LIKE "%subscriber%") AS users,
					CAST( ' . $blog_row->blog_id . ' AS UNSIGNED INTEGER ) AS blog_id 
			FROM ' . $bid . 'options
			WHERE option_name = "home"
			';
	}

	$query = implode(' UNION ALL ', $sqls );

	$order = ' ORDER BY ' . $options['orderby'] . ' ' . $options['order'];

	$limit = ' LIMIT ' . $options['per_page'];
	if( $options['current_page'] > 1 ) {
		$offset = $options['per_page'] * ( $options['current_page']-1 );
		$limit = ' LIMIT ' . $offset . ', ' . $options['per_page'];
	}
	$query .=  $order . $limit;

	//echo $query;

	// cache the results of the union of all these select statements
	$results = $wpdb->get_results( $query, ARRAY_A );

	if ( $wpdb->last_error ) {
		echo 'DB Error: ' . $wpdb->last_error;
	}
	
	return $results;

}

/**
 * Output the data in a pretty way
 * @param arr $result to iterate over
 * @return str HTML string
 */
function uri_network_general_output( $result ) {
	if( 0 === count($result) ) {
		return '<p>No results found.</p>';
	}
	
	$total = 0;
	$themes = array();

	$output = '<table>';
	$output .= '<thead><tr>';
	$output .= '<th>Blog ID</th>';
	$output .= '<th>Blog Name</th>';
	$output .= '<th>Theme</th>';
	$output .= '<th>Last Updated</th>';
	$output .= '<th>Pages</th>';
	$output .= '<th>Posts</th>';
	$output .= '<th>Users</th>';
//	$output .= '<th>Categories</th>';
	$output .= '</tr></thead>';
	$output .= '<tbody>';
	
	foreach($result as $row) {
		$total++;
		if ( array_key_exists($row['theme'], $themes) ) {
			$themes[$row['theme']]++;
		} else {
			$themes[$row['theme']] = 1;
		}
		$output .= '<tr>';
		$output .= '<th>' . $row['blog_id'] . '</th>';
		$output .= '<td><a href="' . $row['url'] . '">' . $row['name'] . '</a></td>';
		$output .= '<td>' . $row['theme'] . '</td>';
		$output .= '<td>' . date('Y-m-d', strtotime($row['modified'])) . '</td>';
		$output .= '<td>' . $row['pages'] . '</td>';
		$output .= '<td>' . $row['posts'] . '</td>';
		$output .= '<td>' . $row['users'] . '</td>';
//		$output .= '<td>' . $row['cats'] . '</td>';
		$output .= '</tr>';
	}
	$output .= '</tbody>';
	$output .= '</table>';
	
	$output .= '<dl><dt>Total Sites:</dt><dd>' . $total . '</dd>';
	foreach($themes as $theme => $count) {
		$output .= '<dt>' . $theme . '</dt><dd>' . $count . '</dd>';
	}

	$output .= '</dl>';


	return $output;
}
