<?php

class WP_Term_Object #extends WP_DB_Object 
#	implements 
#		WP_Hierarchy_Interface,
#		WP_Permalink_Interface 
{
	
	public $filter; // not DB field
	
	protected $objectType = 'term';
	
	protected $primaryKey = 'term_id';
	
	protected $taxonomyObject; // not DB field
	
	
	/* ======== get_instance_data() ======== */
	
	static function get_instance_data( $term /* [, $tax = 'post_tags' ] */ ){
		
		if ( func_num_args() > 1 ){
			$tax = func_get_arg(1);
		} else {
			$tax = 'post_tags';
		}
		
		// Numeric values can be integer or string - term_id can be either.
		// Avoid false integer casting when non-hierarchical term slug/name is a number
		// by casting to int only if tax is hierarchical.
		if ( is_numeric($term) && is_taxonomy_hierarchical($tax) ){
			$term = intval($term);	
		}
		
		if ( is_string($term) ){
			$the_term = get_term_by( 'slug', $term, $tax );
			if ( !$the_term ) // slug failed, try name
				$the_term = get_term_by( 'name', $term, $tax );
			return $the_term;
		}
		
		return get_term( $term, $tax );
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_Permalinked' implementation 
	========================================================= */
	
	public function get_permalink(){
		
		return get_term_link( $this );	
	}
	
	public function the_permalink( $text = null, $desc_as_title = true ){
		
		if ( null !== $text && isset($this->$text) ){
			$text = $this->$text;
		} else {
			$text = $this->name;
		}
		
		$link = '<a href="' . esc_attr( $this->get_permalink() ) . '"';
		
		if ( $desc_as_title )
			$link .= ' title="' . esc_attr( $this->description ) . '"';
		
		$link .= '>' . $text . '</a>';
		
		echo $link;
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_Hierarchical' implementation 
	========================================================= */
	
	public function is_parent(){
		return !$this->is_child();
	}
	
	public function is_child(){
		return 0 != $this->parent;	
	}
	
	public function has_parent(){
		$parents = $this->get_parents();
		return !empty($parents);
	}
	
	public function has_children(){
		$children = $this->get_children();
		return !empty($children);
	}
	
	public function get_parents(){
		
		return $this->get_taxonomy_object()->get_term_parents( $this->get_id() );
	}
	
	public function get_children(){
		
		return $this->get_taxonomy_object()->get_term_children( $this->get_id() );
	}
	
	
	/* ============================
		(Magic) Method Overrides 
	============================= */
	

	/* ============================
			Custom methods
	============================= */

	public function get_taxonomy_object(){
		
		if ( !isset($this->taxonomyObject) ){
			$this->taxonomyObject =& x_wp_get_taxonomy_object( $this->taxonomy );
		}
		
		return $this->taxonomyObject;
	}
	
	public function get_hierarchy(){
		
		$tax = $this->get_taxonomy_object();
		
		if ( !$tax->is_hierarchical() ){
			return null;
		}
		
		return $tax->get_term_hierarchy();	
	}
	
	
	/* =============================
				Filters 
	============================== */
	
	protected function filterValue( $key, $value ){
		
		return $value;	
	}
	
	protected function filterOutput( $key, $value ){
		
		return $value;	
	}
	
}