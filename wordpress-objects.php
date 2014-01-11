<?php
/*
Plugin name: WordPress Objects
Description: Prototype for object-oriented WordPress data using existing API. Currently implemented as a plug-in
Author: wells
Version: 0.0.8
*/

// Set global var to hold object keys and aliases
$GLOBALS['_x_wp_object_keys'] = array();

// Setup
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
	include_once 'classes/Taxonomy_Object.php';
	include_once 'classes/Term_Object.php';
}

// Initialize
add_action('init', '_x_wp_objects_init');
	
	function _x_wp_objects_init(){
		
		/**
		* Register keys (i.e properties) for the object.
		*/
		x_wp_register_object_keys( 'post', array(
			# alias =>	key
			'id'			=>	'ID',
			'author'		=>	'post_author',
			'date'			=>	'post_date',
			'date_gmt'		=>	'post_date_gmt',
			'content'		=>	'post_content',
								'post_content_filtered',
			'title'			=>	'post_title',
			'excerpt'		=>	'post_excerpt',
			'status'		=>	'post_status',
			'type'			=>	'post_type',
								'comment_status',
								'ping_status',
			'password'		=>	'post_password',
			'name'			=>	'post_name',
								'to_ping',
								'pinged',
			'modified'		=>	'post_modified',
			'modified_gmt'	=>	'post_modified_gmt',
			'parent'		=>	'post_parent',
								'menu_order',
								'guid',
		));
		
		// add a "modified_date" alias for "post_modified" property
		x_wp_register_object_key_alias( 'post', 'post_modified', 'modified_date' );
	
		x_wp_register_object_keys( 'user', array(
			'id'			=> 	'ID',
			'login'			=>	'user_login',
			'pass'			=>	'user_pass',
			'nicename'		=>	'user_nicename',
			'email'			=>	'user_email',
			'url'			=>	'user_url',
			'registered'	=>	'user_registered',
			'activation_key'=>	'user_activation_key',
			'status'		=>	'user_status',
								'display_name',
		) );
		
		x_wp_register_object_keys( 'taxonomy', array(
								'name', // Primary Key always first in array
								'labels',
								'description',
								'public',
								'hierarchical',
								'show_ui',
								'show_in_menu',
								'show_in_nav_menus',
								'show_tagcloud',
								'meta_box_cb',
								'rewrite',
								'query_var',
								'update_count_callback',
								'_builtin',
								'show_admin_column',
			'capabilities'	=>	'cap',
								'object_type',
								'label',
		) );
	
		x_wp_register_object_keys( 'term', array(
			'id'			=>	'term_id',
								'name',
								'slug',
			'group'			=>	'term_group',
			'taxonomy_id'	=>	'term_taxonomy_id',
								'taxonomy',
								'description',
								'parent',
								'count',
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
	
	if ( !isset($_x_wp_object_keys[ $object ]['keys']) )
		$_x_wp_object_keys[ $object ]['keys'] = array();
	
	foreach($keys as $k => $v){
		
		$_x_wp_object_keys[ $object ]['keys'][] = $v;
		
		if ( !is_int($k) ){ 
			// $k is not an index, its an alias!
			x_wp_register_object_key_alias( $object, $v, $k );
		}
	}
	
	return $_x_wp_object_keys[ $object ];
}

function x_wp_register_object_key_alias( $object, $key, $alias ){
	global $_x_wp_object_keys;
	if ( !isset($_x_wp_object_keys[ $object ]['aliases']) )
		$_x_wp_object_keys[ $object ]['aliases'] = array();
	$_x_wp_object_keys[ $object ]['aliases'][ $alias ] = $key;
}

/**
* Returns array of keys for an object.
*/
function x_wp_get_object_keys( $object, $empty_response = null ){
	
	global $_x_wp_object_keys;
	
	if ( isset($_x_wp_object_keys[ $object ]) ){
		return $_x_wp_object_keys[ $object ]['keys'];
	}
	
	return $empty_response;
}

function x_wp_get_object_key_aliases( $object, $empty_response = null ){
	
	global $_x_wp_object_keys;
	
	if ( isset($_x_wp_object_keys[ $object ]) ){
		return $_x_wp_object_keys[ $object ]['aliases'];
	}

	return $empty_response;
}


/**
* Returns an object instance.
* $var is a hack for objects that require a second var (i.e. terms)
*/
function x_wp_get_object( $object_type, $object_id, $var = null ){
	
	return WordPress_Object_Factory::get_object( $object_type, $object_id, $var );
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

/**
* Returns a Taxonomy object instance.
*/
function x_wp_get_taxonomy_object( $taxonomy ){
	
	return x_wp_get_object( 'taxonomy', $taxonomy );	
}

/**
* Returns a Term object instance.
*/
function x_wp_get_term_object( $term, $taxonomy ){
	
	return x_wp_get_object( 'term', $term, $taxonomy );	
}
