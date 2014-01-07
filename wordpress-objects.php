<?php
/*
Plugin name: WordPress Objects
Description: Prototype for object-oriented WordPress data (posts, comments, users, etc.). Uses existing WordPress API (functions) and relies heavily on magic methods.
Author: wells
Version: 0.1.4
*/

require_once 'interfaces.php';

if ( function_exists('xpl_autoload') ){
	
	xpl_autoload( 'WordPress', dirname(__FILE__) . '/classes' );	
} 
else {	
	include_once 'classes/Object_Factory.php';	
	include_once 'classes/Object.php';
	include_once 'classes/Object_With_Metadata.php';
	include_once 'classes/Post_Object.php';
	include_once 'classes/User_Object.php';	
}

// Set global var to hold object keys and aliases
$GLOBALS['_x_wp_object_keys'] = array();

// Initialize
add_action('init', '_x_wp_objects_init');
	
	function _x_wp_objects_init(){
		
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
	
		//x_wp_register_object_keys( 'comment', array() );
		
	}


/**
* Register keys for an object
*/
function x_wp_register_object_keys( $object, array $keys ){
	
	global $_x_wp_object_keys;
	
	foreach($keys as $key => $alias){
		
		$_x_wp_object_keys[ $object ][ $key ] = $alias;
	}
		
	return $_x_wp_object_keys[ $object ];
}

/**
* Returns array of keys for an object.
*/
function x_wp_get_object_keys( $object, $include_primary = true, $include_aliases = true ){
	
	global $_x_wp_object_keys;
	
	if ( isset($_x_wp_object_keys[ $object ]) ){
		$keys = $_x_wp_object_keys[ $object ];	
	}
	else {
		$keys = apply_filters( 'wordpress_object_keys', array(), $object );
	}
	
	if ( !$include_primary ){
		array_shift( $keys );	
	}
	
	if ( !$include_aliases ){
		$keys = array_keys( $keys );	
	}
	
	return $keys;
}

/**
* Returns an object instance.
*/
function x_wp_get_object( $object, $id ){
	
	return WordPress_Object_Factory::get_object( $object, $id );
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
