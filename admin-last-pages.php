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
		add_action( 'admin_bar_menu', array( $this, 'add_prev_pages_to_toolbar' ), 999 );

		// Get the admin page title.
		add_filter( 'admin_title', array( $this, 'get_admin_title' ), 10, 2);

	}



	/**
	 * add_prev_pages_to_toolbar function.
	 *
	 * Loop through array stored in user meta and display previous admin pages visited. 
	 *  
	 * @access public
	 * @param mixed $wp_admin_bar
	 * @return void
	 */
	function add_prev_pages_to_toolbar( $wp_admin_bar ) {

		// meta key is "_previous_pages" 
		$user_new = get_user_meta( get_current_user_id(), '_previous_pages', true );

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
	
	/**
	 * get_admin_title function.
	 *
	 * Get admin page title or if post get post title. 
	 * 
	 * @access public
	 * @param mixed $admin_title
	 * @param mixed $title
	 * @return void
	 */
	function get_admin_title( $admin_title, $title ) {
		$limit = 5;
		$user_id = get_current_user_id();

		//current url
		$the_url = $_SERVER['REQUEST_URI'];


		if( isset( $_GET['post'] ) ) {
			$current_post_title = get_the_title( $_GET['post'] );
		}
		
		$user_last = get_user_meta( $user_id, '_previous_pages', true );
		
		// loop through saved pages, if current post id is already in the array don't save it. 
		foreach($user_last as $page){
			
			$parsed_url = parse_url($page['url']);  	
			
			parse_str($parsed_url['query'], $query);

			if( isset($query['post']) && isset($_GET['post']) && ($query['post'] ==  $_GET['post'])){
				return;
			} 
		}
		
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


	
	/**
	 * in_array_r function.
	 * 
	 * look for a value in a multi-dimensional array. 
	 * Found here: http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
	 *
	 * @access private
	 * @static
	 * @param mixed $needle
	 * @param mixed $haystack
	 * @param bool $strict (default: false)
	 * @return void
	 */
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
