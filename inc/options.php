<?php
/**
 * @package URI_Network
 */

 // Block direct requests
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


/**
 * Create a shortcode to query site options across the network
 * [uri_network_option option="uridepartment" key="urid_jsinline"] option is the name of the option, key is used if the option is an array
 * @param arr shortcode attriibutes ('option' is the only currently valid attribute)
 */
function uri_network_option_shortcode( $atts ) {
	_uri_network_js();
	$attributes = shortcode_atts(
		array(
			'option' => 'uridepartment', // shortcode attributes to seek and their defaults
			'key' => '', // if the option we query is an array, this key will show only the value for one element
		), $atts
	);

	$result = uri_network_option_query( $attributes['option'] );
	return uri_network_option_output( $result, $attributes['key']);
}
add_shortcode( 'uri_network_option', 'uri_network_option_shortcode' );



/**
 * Query the db for options of a specified name
 * @param str the name of the option to look up
 * @return arr
 */
function uri_network_option_query( $option_name ) {
	global $wpdb;
	// get all the blog ids from the blogs table
	$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);

	// build a sql statement for each blog options table, adding in the blog id for each row
	$sqls = array();
	foreach ($blogs as $blog_row) {
		$bid = $wpdb->get_blog_prefix($blog_row['blog_id']);
		$sqls[] = 'SELECT option_value,
						(SELECT option_value FROM ' . $bid . 'options WHERE option_name="siteurl") AS url,
						(SELECT option_value FROM ' . $bid . 'options WHERE option_name="blogname") AS name,
						CAST( ' . $blog_row['blog_id'] . ' AS UNSIGNED INTEGER ) AS blog_id 
			FROM ' . $bid . 'options
			WHERE option_name="' . $option_name . '"';
	}
	
	// cache the results of the union of all these select statements
	$option_results = $wpdb->get_results(implode(' UNION ALL ', $sqls), ARRAY_A);

	return $option_results;

}

/**
 * Output the data in a pretty way
 * @param arr $rows to iterate over
 * @param $array_key is the key to use in the event that $rows contains serialized arrays
 * @return str HTML string
 */
function uri_network_option_output( $rows, $key ) {
	$output = '<table>';
	$output .= '<thead><tr>';
	$output .= '<th>Blog ID</th>';
	$output .= '<th>Blog Name</th>';
	$output .= '<th>Value</th>';
	$output .= '</tr></thead>';
	$output .= '<tbody>';
	
	foreach($rows as $k => $value) {
		if ( is_serialized($value['option_value']) ) {
			$v = unserialize( $value['option_value'] );
			if( $key ) {
				$v = $v[$key];
			} else {
				$v = print_r( $v, TRUE );
			}
		} else {
			$v = $value['option_value'];
		}
		
		$output .= '<tr>';
		$output .= '<th>' . $value['blog_id'] . '</th>';
		$output .= '<td><a href="' . $value['url'] . '">' . $value['name'] . '</a></td>';
		$output .= '<td>' . $v . '</td>';
		$output .= '</tr>';
	}
	$output .= '</tbody>';
	$output .= '</table>';
	return $output;
}
