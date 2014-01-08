<?php
class WordPress_Taxonomy_Object extends WordPress_Object implements WordPress_SubObjects {
	
	protected $_object_name = 'taxonomy';
	
	protected $_primary_key = 'name';
	
	
	/* ======== get_instance_data() ======== */
	
	static function get_instance_data( $tax ){
		
		return get_taxonomy( $tax );	
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_SubObjects' implementation 
	========================================================= */
	
	function get_subobject_type(){
		return 'term';
	}
		
	function get_subobject( $term ){
		
		return x_wp_get_term_object( $term, $this->name );	
	}
	
	function get_subobjects(){
		
		return get_terms( $this->name );
	}
	
	
	/* ============================
		(Magic) Method Overrides 
	============================= */
	
	
	/* ============================
			Custom methods
	============================= */

	function get_term_hierarchy( $force_reset = false ){
		
		if ( isset($this->term_hierarchy) && !$force_reset )
			return $this->term_hierarchy;
			
		$taxonomy = $this->name;
		$children = get_option("{$taxonomy}_children");
	
		if ( is_array($children) )
			return $this->term_hierarchy = $children;
		
		$children = array();
		$terms = get_terms($this->name, array('get' => 'all', 'orderby' => 'id', 'fields' => 'id=>parent'));
		foreach ( $terms as $term_id => $parent ) {
			if ( $parent > 0 )
				$children[$parent][] = $term_id;
		}
		update_option("{$taxonomy}_children", $children);
	
		return $this->term_hierarchy = $children;	
	}
	
	function get_term_parents( $term_id ){
		
		$hierarchy = $this->get_term_hierarchy();
		$parents = array();
		
		foreach($hierarchy as $id => $children){
			if ( in_array($term_id, $children) )
				$parents[] = $id;
		}
		
		return $parents;
	}
	
	function get_term_children( $term_id ){
		
		$children = array();
		$terms = $this->get_term_hierarchy();
		
		if ( !isset($terms[$term_id]) ) 
			return null;
		
		foreach ( (array) $terms[$term_id] as $child ) {
			if ( isset($terms[$child]) )
				$children = array_merge($children, get_term_children($child, $this->name));
			else
				$children[] = $child;
		}
		
		return $children;
	}
	
	
	// Misc.
	
	function has_description(){
		return !empty($this->description);	
	}
	
	function is_hierarchical(){
		return $this->hierarchical;	
	}
	
	function is_public(){
		return $this->public;	
	}
	
	function is_builtin(){
		return $this->_builtin;	
	}
	
	function get_caps(){
		return $this->cap;	
	}
	
	// Labels
	
	function get_label( $type = 'name' ){
		return isset($this->labels->$type) ? $this->labels->$type : $this->labels->name;	
	}
	
	function get_singular_name(){
		return $this->get_label('singular_name');	
	}
	
	function get_plural_name(){
		return $this->get_label('name');
	}
	
	// show_*
	
	function show_ui(){
		return $this->show_ui;
	}
	
	function show_in_menu(){
		return $this->show_in_menu;	
	}
	
	function show_in_nav_menus(){
		return $this->show_in_nav_menus;	
	}
	
	function show_tagcloud(){
		return $this->show_tagcloud;	
	}
	
	function show_admin_column(){
		return $this->show_admin_column;
	}
	
	// Rewrite
	
	function is_rewrite_with_front(){
		return $this->rewrite['with_front'];	
	}
	
	function is_rewrite_hierarchical(){
		return $this->rewrite['hierarchical'];	
	}
	
	function get_rewrite_slug(){
		return $this->rewrite['slug'];	
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