<?php
/*
Plugin name: WordPress Objects
Description: Prototype for object-oriented WordPress data (posts, comments, users, etc.). Uses existing WordPress API (functions) and relies heavily on magic methods.
Author: wells
Version: 0.1-alpha
*/

if ( function_exists('xpl_autoload') ){
	
	xpl_autoload( 'WordPress', dirname(__FILE__) . '/classes' );	
} 
else {
	
	include_once 'classes/Object_Factory.php';	
	include_once 'classes/Object.php';
	include_once 'classes/Object_With_Metadata.php';
	include_once 'classes/Post_Object.php';	
}

add_action('init', '_wp_objects_init');
	
	function _wp_objects_init(){
		
		x_wp_register_object_keys( 'post', array(
			'ID',
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_content_filtered',
			'post_title',
			'post_excerpt',
			'post_status',
			'post_type',
			'comment_status',
			'ping_status',
			'post_password',
			'post_name',
			'to_ping',
			'pinged',
			'post_modified',
			'post_modified_gmt',
			'post_parent',
			'menu_order',
			'guid'
		));
		
		x_wp_register_object_key_aliases( 'post', array(
			'author'			=> 'post_author',
			'date'				=> 'post_date',
			'date_gmt'			=> 'post_date_gmt',
			'content'			=> 'post_content',
			'content_filtered'	=> 'post_content_filtered',
			'title'				=> 'post_title',
			'excerpt'			=> 'post_excerpt',
			'status'			=> 'post_status',
			'type'				=> 'post_type',
			'password'			=> 'post_password',
			'name'				=> 'post_name',
			'date_modified' 	=> 'post_modified',
			'date_modified_gmt' => 'post_modified_gmt',
			'parent'			=> 'post_parent'
		));
		
		//x_wp_register_object_keys( 'user', array() );
		//x_wp_register_object_keys( 'comment', array() );
		
	}

$GLOBALS['_x_wp_object_keys'] = array();
$GLOBALS['_x_wp_object_key_aliases'] = array();

/**
* Register keys for an object
*/
function x_wp_register_object_keys( $object, array $keys ){
	
	global $_x_wp_object_keys;
	
	return $_x_wp_object_keys[ $object ] = $keys;
}

/**
* Register key aliases for an object.
*/
function x_wp_register_object_key_aliases( $object, array $aliases ){
	
	global $_x_wp_object_key_aliases;
	
	return $_x_wp_object_key_aliases[$object] = $aliases;
}

/**
* Returns array of keys for an object.
*/
function x_wp_get_object_keys( $object, $include_primary = true ){
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
	
	return $keys;
}

/**
* Returns an object key alias, if set.
*/
function x_wp_get_aliased_object_key( $object, $key ){
	
	global $_x_wp_object_key_aliases;
	
	if ( isset($_x_wp_object_key_aliases[$object]) && isset($_x_wp_object_key_aliases[$object][$key]) ){
		
		return $_x_wp_object_key_aliases[$object][$key];
	}
	
	return false;
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
