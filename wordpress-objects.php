<?php
/*
Plugin name: WordPress Objects
Description: Prototype for object-oriented WordPress data using existing API. Currently implemented as a plug-in
Author: wells
Version: 0.0.5
*/

// Set global var to hold object keys and aliases
$GLOBALS['_x_wp_object_keys'] = array();

// Setup
add_action('plugins_loaded', '_x_wp_objects_setup');
	
	function _x_wp_objects_setup(){
			
		require_once 'interfaces.php';
		
		if ( function_exists('xpl_autoload') ){
			
			xpl_autoload( 'WordPress', dirname(__FILE__) . '/classes' );	
		} 
		else {	
			include_once 'classes/Object_Factory.php';	
			include_once 'classes/Object.php';
			include_once 'classes/Object_With_Metadata.php';
			// objects
			include_once 'classes/Post_Object.php';
			include_once 'classes/User_Object.php';
			include_once 'classes/Taxonomy_Object.php';
			include_once 'classes/Term_Object.php';
		}
	}

// Initialize
add_action('init', '_x_wp_objects_init');
	
	function _x_wp_objects_init(){
		
		/**
		* Register keys (i.e properties) for the object.
		* @param $object is the "object type"
		* @param $keys an array of 
		* "{$key} => {$alias}" pairs, where $key is an actual
		* WP property of the (to-be) object.
		*/
		x_wp_register_object_keys( 'post', array(
			'ID'				=> 'id',
			'post_author'		=> 'author',
			'post_date'			=> 'date',
			'post_date_gmt'		=> 'date_gmt',
			'post_content'		=> 'content',
			'post_content_filtered'	=> 'content_filtered',
			'post_title'		=> 'title',
			'post_excerpt'		=> 'excerpt',
			'post_status'		=> 'status',
			'post_type'			=> 'type',
			'comment_status'	=> 'comment_status',
			'ping_status'		=> 'ping_status',
			'post_password'		=> 'password',
			'post_name'			=> 'name',
			'to_ping'			=> 'to_ping',
			'pinged'			=> 'pinged',
			'post_modified'		=> 'modified',
			'post_modified_gmt'	=> 'modified_gmt',
			'post_parent'		=> 'parent',
			'menu_order'		=> 'order',
			'guid'				=> 'url',
		));
	
		x_wp_register_object_keys( 'user', array(
			'ID'					=> 'id',
			'user_login'			=> 'login',
			'user_pass'				=> 'pass',
			'user_nicename'			=> 'nicename',
			'user_email'			=> 'email',
			'user_url'				=> 'url',
			'user_registered'		=> 'registered',
			'user_activation_key'	=> 'activation_key',
			'user_status'			=> 'status',
			'display_name'			=> 'name',
		) );
		
		x_wp_register_object_keys( 'taxonomy', array(
			'name'					=> 'name', // Primary Key always first in array
			'labels'				=> 'labels',
			'description'			=> 'description',
			'public'				=> 'public',
			'hierarchical'			=> 'hierarchical',
			'show_ui'				=> 'show_ui',
			'show_in_menu'			=> 'show_in_menu',
			'show_in_nav_menus'		=> 'show_in_nav_menus',
			'show_tagcloud'			=> 'show_tagcloud',
			'meta_box_cb'			=> 'meta_box_callback',
			'rewrite'				=> 'rewrite',
			'query_var'				=> 'query_var',
			'update_count_callback' => 'update_count_cb',
			'_builtin'				=> 'builtin',
			'show_admin_column'		=> 'show_admin_col',
			'cap'					=> 'capabilities',
			'object_type'			=> 'object_types',
			'label'					=> 'label',
		) );
	
		x_wp_register_object_keys( 'term', array(
			'term_id' 			=> 'id',
			'name'				=> 'name',
			'slug'				=> 'slug',
			'term_group'		=> 'group',
			'term_taxonomy_id'	=> 'taxonomy_id',
			'taxonomy' 			=> 'tax',
			'description'		=> 'description',
			'parent'			=> 'parent_term',
			'count'				=> 'count',
		) );
		
	}


/**
* Register keys for an object
* 
* @param string $object The "object type" (e.g. post, user, term)
* @param array $keys Assoc. array of "{key} => {alias}" pairs, where key is an object property
* @return array $keys
*/
function x_wp_register_object_keys( $object, array $keys ){
	
	global $_x_wp_object_keys;
	
	return $_x_wp_object_keys[ $object ] = $keys;
}

/**
* Returns array of keys for an object.
*/
function x_wp_get_object_keys( $object ){
	
	global $_x_wp_object_keys;
	
	if ( isset($_x_wp_object_keys[ $object ]) ){
		$keys = $_x_wp_object_keys[ $object ];	
	}
	else {
		$keys = apply_filters( 'wordpress_object_keys', array(), $object );
	}
		
	return $keys;
}

/**
* Returns an object instance.
* $var is a hack for objects that require a second var (i.e. terms)
*/
function x_wp_get_object( $object, $id, $var = null ){
	
	return WordPress_Object_Factory::get_object( $object, $id, $var );
}

/**
* Returns a Post object instance.
*/
function x_wp_get_post_object( $post_id = null ){
	
	if ( null === $post_id ){
		global $post;
		$post_id = $post->ID;	
	}
	
	return x_wp_get_object( 'post', $post_id );
}

/**
* Returns a User object instance.
*/
function x_wp_get_user_object( $user_id = null ){
	
	if ( null === $user_id ){
		$user_id = get_current_user_ID();	
	}
	
	return x_wp_get_object( 'user', $user_id );
}

function x_wp_get_taxonomy_object( $taxonomy ){
	
	return x_wp_get_object( 'taxonomy', $taxonomy );	
}

function x_wp_get_term_object( $term, $taxonomy ){
	
	return x_wp_get_object( 'term', $term, $taxonomy );	
}
