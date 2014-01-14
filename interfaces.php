<?php
/**
* WordPress Objects interfaces
* @package WordPress-Objects
* @subpackage Interfaces
*/

// Metadata object interface
interface WP_Metadata_Interface {
	
	function get_meta( $meta_key = '', $single = false );
	
	function update_meta( $meta_key, $meta_value, $prev_value = null );
	
	function delete_meta( $meta_key = '', $meta_value = '', $delete_all = false );
}

// Hierarchical object interface
interface WP_Hierarchy_Interface {
	
	function is_parent();
	
	function is_child();
	
	function has_parent();
	
	function has_children();
	
	function get_parents();
	
	function get_children();
}

// Permalinked WordPress object interface
interface WP_Permalink_Interface {
	
	function get_permalink();
	
	function the_permalink();
}

// For objects that have "child" objects of a different type (e.g. taxonomies have terms)
interface WP_SubObjects_Interface {
	
	function get_subobject_type(); // returns string, e.g. 'term'
		
	function get_subobject( $id );
	
	function get_subobjects();
}
