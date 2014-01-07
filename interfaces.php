<?php
/**
* WordPress Objects interfaces
* @package WordPress-Objects
* @subpackage Interfaces
*/

// Updatable interface
interface WordPress_Updatable {
	
	function update();
	
	function insert();
	
	function delete( $force_delete = false );
	
	function update_var( $key );
		
}

// Hierarchical interface
interface WordPress_Hierarchical {
	
	function is_parent();
	
	function is_child();
	
	function has_parent();
	
	function has_children();
	
	function get_parents();
	
	function get_children();
	
}