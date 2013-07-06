<?php

/**
 * Plugin Name: Admin Last Pages
 * Plugin URI: https://github.com/jonjennings/admin-last-pages
 * Description: Add the last five pages visited in the admin to a drop downmenu in the admin bar.
 * Version: 0.1
 * Author: Jon Jennings, Flynn O'Connor
 */


class Admin_Last_Pages {


	function __construct() {

		// Add our menu to the admin bar
		add_action( 'admin_bar_menu', array( $this, 'add_nodes_and_groups_to_toolbar' ), 999 );

		// Remove wordpress logo and menu from admin bar
		add_action( 'admin_bar_menu', array( $this, 'remove_wp_logo' ), 999 );

		// Get the admin page title.
		add_filter( 'admin_title', array( $this, 'get_admin_title' ), 10, 2);

	}


	function add_nodes_and_groups_to_toolbar( $wp_admin_bar ) {

		$user_new = get_user_meta( get_current_user_id(), '_previous_pages', true );

            // debug
            //echo wpautop(var_export($user_new, true));


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
				'id'      => 'prev_page_' . $test_page['title'],
				'title'   => $test_page['title'],
				'href'    => $test_page['url'],
				'parent'  => 'previous_pages'
			);
			$wp_admin_bar->add_node( $args );

		}
	  }


	function remove_wp_logo( $wp_admin_bar ) {
		$wp_admin_bar->remove_node( 'wp-logo' );
	}


	// I can't find an easier way to get the admin page title.
	// Todo: Find a better way.
	function get_admin_title( $admin_title, $title ) {
		$limit = 5;
		$user_id = get_current_user_id();

		//current url
		$the_url = $_SERVER['REQUEST_URI'];

        // We're doing something to a post/page
		if( isset( $_GET['post'] ) ) {
            // If we're editing it, make title helpful
            // TODO: other things can put a post param in the URL - eg moving to trash
            //       maybe some of them shouldn't be remembered
            //       (or are we making things too complicated by looking for special cases?)
            if ( false !== stripos( $the_url, 'action=edit' ) ) {
                $current_post_title = 'Editing: ' . get_the_title( $_GET['post'] );
            } else {
                $current_post_title = get_the_title( $_GET['post'] );
            }
		}
		//delete_user_meta( $user_id, '_previous_pages' );


		$user_last = get_user_meta( $user_id, '_previous_pages', true );

		// Don't search page array if array doesn't exist (ie first time here)
		if ( ! empty( $user_last ) ) {
			// check if url and title are both already in array.
			if ( self::in_array_r( $the_url, $user_last )  && self::in_array_r( $title, $user_last ) ) {
				return;
			}
		}

		// if we're on a post editing page save the post title otherwise save the admin page title.
		if ( isset( $current_post_title ) ) {
			$new_entry = array( 'title' => $current_post_title, 'url' => $the_url );

		} else {
			$new_entry = array( 'title' => $title, 'url' => $the_url );
		}

        //echo "Adding title $current_post_title / $title , url $url<br/>";

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
	private static function in_array_r( $needle, $haystack, $strict = false) {
		foreach ( $haystack as $item ) {
			if ( ( $strict ? $item === $needle : $item == $needle ) || ( is_array( $item ) && self::in_array_r( $needle, $item, $strict ) ) ) {
				return true;
			}
		}

		return false;
	}
}

// Instantiate ourselves
$admin_last_pages = new Admin_Last_Pages();
?>
