<?php

class WordPress_Term_Object extends WordPress_Object implements WordPress_Hierarchical {
	
	public $filter; // not DB field
	
	protected $taxonomy_object; // not DB field
	
	protected $_object_name = 'term';
	
	protected $_primary_key = 'term_id';
	
	
	/* ======== get_instance_data() ======== */
	
	static function get_instance_data( $term /** , $tax = 'post_tags' */ ){
		
		$tax = func_get_arg(1);
		
		if ( is_numeric($term) && is_taxonomy_hierarchical($tax) )
			$term = intval($term);	
		
		if ( is_string($term) )
			return get_term_by( 'slug', $term, $tax );
		
		return get_term( $term, $tax );
	}
	
	
	/* ============================
		(Magic) Method Overrides 
	============================= */
	

	/* ============================
			Custom methods
	============================= */

	function get_taxonomy_object(){
		
		if ( !isset($this->taxonomy_object) ){
			$this->taxonomy_object =& x_wp_get_taxonomy_object( $this->taxonomy );
		}
		
		return $this->taxonomy_object;
	}
	
	function get_hierarchy(){
		
		return $this->get_taxonomy_object()->get_term_hierarchy();	
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_Hierarchical' implementation 
	========================================================= */
	
	function is_parent(){
		return !$this->is_child();
	}
	
	function is_child(){
		return 0 != $this->parent;	
	}
	
	function has_parent(){
		$parents = $this->get_parents();
		return !empty($parents);
	}
	
	function has_children(){
		$children = $this->get_children();
		return !empty($children);
	}
	
	function get_parents(){
		
		return $this->get_taxonomy_object()->get_term_parents( $this->term_id );
	}
	
	function get_children(){
		
		return $this->get_taxonomy_object()->get_term_children( $this->term_id );
	}
	
	
	/* =============================
				Filters 
	============================== */
	
	function filter_value( $key, $value ){
		
		return $value;	
	}
	
	function filter_output( $key, $value ){
		
		return $value;	
	}
	
}