<?php

/**
 * Plugin Name: Admin Last Pages
 * Plugin URI: https://github.com/jonjennings/admin-last-pages
 * Description: Add the last five pages visited in the admin to a drop downmenu in th admin bar.
 * Version: 0.1
 * Author: Jon Jennings, Flynn O'Connor
 */

add_action( 'admin_bar_menu', 'add_nodes_and_groups_to_toolbar', 999 );

function add_nodes_and_groups_to_toolbar( $wp_admin_bar ) {

	$user_new = get_user_meta( get_current_user_id( ), '_previous_pages', true );

    // add a parent item
    $args = array(
	    'id'        => 'previous_pages',
	    'title'     => 'Previous Pages',
	    'parent'    => 'top-secondary',
	);

    $wp_admin_bar->add_node( $args );

	foreach( $user_new as $test_page ) {

	    // add a child item to our parent item
	    $args = array(
		    'id'      => 'prev_page_' . $test_page[0], 
		    'title'   => $test_page[0],
		    'href'    => $test_page[1], 
		    'parent'  => 'previous_pages'
	    ); 
	    $wp_admin_bar->add_node( $args );

    }
  }

// remove wordpress logo and menu from admin bar
add_action( 'admin_bar_menu', 'remove_wp_logo', 999 );

function remove_wp_logo( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'wp-logo' );
}
 

// I can't find an easier way to get the admin page title.
// Todo: Find a better way.
add_filter( 'admin_title', 'forge_get_admin_title', 10, 2);

function forge_get_admin_title( $admin_title, $title ) {
	$limit = 5;
	$user_id = get_current_user_id();

	//curent url
	$the_url = $_SERVER['REQUEST_URI'];

	if( isset( $_GET['post'] ) ) {
		$current_post_title = get_the_title($_GET['post']);
	}
	//delete_user_meta( $user_id, '_previous_pages' );


	$user_last = get_user_meta( $user_id, '_previous_pages', true );

	// Don't search page array if array doesn't exist (ie first time here)
	if ( ! empty ( $user_last ) ) {
		// check if url and title are both already in array.
		if ( in_array_r( $the_url, $user_last )  && in_array_r( $title, $user_last ) ) {
			return; 
		}
	}

	// if we're on a post editing page save the post title otherwise save the admin page title.
	if ( isset($current_post_title)){
		$new_entry = array( $current_post_title, $the_url );

	} else {
		$new_entry = array( $title, $the_url );
	}


	// if the array is empty array_unshift won't work so we simply add a new array.
	if ( ! empty( $user_last ) ) {
		array_unshift( $user_last, $new_entry );
	} else {
		$user_last[] = $new_entry; 
	}

	$page_count = count( $user_last );

	if ( $page_count > $limit ) {
		for ( $i = 5 ; $i <= $page_count; $i++ ) {
			unset( $user_last[ $i ] );
		}
	}

	//print_r($user_last); 

	update_user_meta( $user_id, '_previous_pages', $user_last );

    return;
}


// look for a value in a multi-dimensional array. Found here: http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
function in_array_r( $needle, $haystack, $strict = false) {
	foreach ( $haystack as $item ) {
		if ( ( $strict ? $item === $needle : $item == $needle ) || ( is_array( $item ) && in_array_r( $needle, $item, $strict ) ) ) {
			return true;
		}
	}

    return false;
}

?>
