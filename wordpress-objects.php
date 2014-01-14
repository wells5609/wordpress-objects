<?php
/*
Plugin name: WordPress Objects
Description: Prototype for object-oriented WordPress data using existing API. Currently implemented as a plug-in
Author: wells
Version: 0.1.0
*/

require_once 'interfaces.php';
require_once 'WP/Object.php';
require_once 'WP/DB_Object_With_Taxonomies.php';
require_once 'WP/Post_Object.php';
require_once 'WP/User_Object.php';
require_once 'WP/Taxonomy_Object.php';
require_once 'WP/Term_Object.php';	
require_once 'WP/Object_Factory.php';

$GLOBALS['wp_object_fields'] = array();

if ( false !== strpos( dirname(__FILE__), WP_PLUGIN_DIR ) || false !== strpos( dirname(__FILE__), WPMU_PLUGIN_DIR ) ){
	// loading as plug-in
	add_action('plugins_loaded', 'create_initial_object_fields');	
} else {
	// loading in core
	create_initial_object_fields();	
}

if ( function_exists('is_admin') && is_admin() ){

	add_action('admin_menu', '_wpobjects_admin_menu');	
	
	function _wpobjects_admin_menu(){
		add_submenu_page('tools.php', 'WP Objects', 'WP Objects', 'manage_options', 'wp-objects', '_wpobjects_admin_page');
	}
	
	function _wpobjects_admin_page(){
		include 'admin/admin-page.php';	
	}

}

/**
* Object fields are used mostly for OO saving of object data to ensure
* that unwanted data is not passed to the method or function.
*/
function create_initial_object_fields(){
	
	global $wp_object_fields;
	
	$GLOBALS['wp_object_factory'] = WP_Object_Factory::instance();
	
	// databased objects
	$wp_object_fields['db'] = array();
	
	$wp_object_fields['db']['post'] = array(
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
		'guid',
	);
	
	$wp_object_fields['db']['user'] = array(
		'ID', 
		'user_login', 
		'user_pass', 
		'user_nicename', 
		'user_email', 
		'user_url', 
		'user_registered', 
		'user_activation_key', 
		'user_status', 
		'display_name',
	);
	
	$wp_object_fields['db']['term'] = array(
		'term_id',
		'name',
		'slug',
		'term_group',
		'term_taxonomy_id',
		'taxonomy',
		'description',
		'parent',
		'count',
	);
	
	// global objects
	$wp_object_fields['global'] = array();

	$wp_object_fields['global']['taxonomy'] = array(
		'name',
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
		'cap',
		'object_type',
		'label',
	);
	
#	$wp_object_fields['global']['posttype'] = array();

}


/**
* Returns base class name for an object type.
*/
function _wp_get_object_base_class( $object_type ){
	return 'WP_' . ucfirst($object_type) . '_Object';
}

/**
* Returns an object instance from array of data.
*/
function wp_create_object_from_data( $object_type, $data = array() ){
	
	return WP_Object_Factory::create_from_data( $object_type, $data );
}

/**
* Returns an object instance.
* $var is a hack for objects that require a second var (i.e. terms)
*/
function wp_get_object( $object_type, $object_id, $var = null ){
	
	return WP_Object_Factory::get( $object_type, $object_id, $var );
}

/**
* Returns a Post object instance.
*/
function wp_get_post_object( $post_id = null ){
	if ( null === $post_id ){
		global $post;
		$post_id = $post->ID;	
	}
	return wp_get_object( 'post', $post_id );
}

/**
* Returns a User object instance.
*/
function wp_get_user_object( $user_id = null ){
	if ( null === $user_id ){
		$user_id = get_current_user_ID();	
	}
	return wp_get_object( 'user', $user_id );
}

/**
* Returns a Taxonomy object instance.
*/
function wp_get_taxonomy_object( $taxonomy ){
	return wp_get_object( 'taxonomy', $taxonomy );	
}

/**
* Returns a Term object instance.
*/
function wp_get_term_object( $term, $taxonomy ){
	return wp_get_object( 'term', $term, $taxonomy );	
}
