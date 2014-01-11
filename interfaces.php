<?php
/**
* WordPress Objects interfaces
* @package WordPress-Objects
* @subpackage Interfaces
*/

// Updatable object interface
interface WordPress_Updatable {
	
	function update();
	
	function insert();
	
	function delete( $force_delete = false );
	
	function update_var( $key );
		
}

// Hierarchical object interface
interface WordPress_Hierarchical {
	
	function is_parent();
	
	function is_child();
	
	function has_parent();
	
	function has_children();
	
	function get_parents();
	
	function get_children();
	
}

// Permalinked WordPress object interface
interface WordPress_Permalinked {
	
	function get_permalink();
	
	function the_permalink();
	
}

// For objects that have "child" objects of a different type (e.g. taxonomies have terms)
interface WordPress_SubObjects {
	
	function get_subobject_type(); // returns string, e.g. 'term'
		
	function get_subobject( $id );
	
	function get_subobjects();
		
}