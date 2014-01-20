<?php

class WP_Term_Object extends WP_DB_Object 
	implements 
		WP_Hierarchy_Interface,
		WP_Permalink_Interface 
{
	
	public $filter; // not DB field
	
	protected $_type = 'term';
	
	protected $_uid_property = 'term_id';
	
	protected $taxonomyObject; // not DB field
	
	
	/* ================================
			get_instance_data() 
	================================ */
	
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
		} elseif ( is_string($term) ){
			$exists = term_exists( $term, $taxonomy, $parent );
			if ( !is_array($exists) )
				return array();
			$term = $exists['term_id'];
		} 
		
		global $wpdb;
		
		$_term = $wpdb->get_row( 
			$wpdb->prepare("SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.term_id = %d LIMIT 1", $tax, $term) 
		);
	
		return $_term ? $_term : array();
	}
	
	/* ====================================
			WP_Permalink_Interface 
	==================================== */
	
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
	
	/* ====================================
			WP_Hierarchy_Interface 
	==================================== */
	
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
	
	/* ========================================
			WP_DB_Object abstract methods
	======================================== */
	
	public function get_update_fields(){
		
		return array_merge( $this->get_fields(), array('alias_of') );
	}
	
	public function update(){
		
		$data = array();
		
		foreach($this->get_update_fields() as $key){
			
			if ( 'term_id' == $key || ! isset($this->$key) || empty($this->$key) )
				continue;
			
			$data[ $key ] = $this->get( $key );
		}
		
		$r = wp_update_term( $this->get_id(), $this->taxonomy, $data );
		
		return $this->catch_return_bool( $r );
	}
	
	public function insert(){
		
		if ( ! isset( $this->name ) )
			return false;
		
		$data = array();
		
		foreach($this->get_update_fields() as $key){
			
			if ( 'name' == $key ) 
				continue;
			
			if ( $this->exists( $key ) ){
				$data[ $key ] = $this->get( $key );	
			}
		}
		
		$r = wp_insert_term( $this->name, $this->taxonomy, $data );
		
		if ( is_wp_error($r) ){
			$this->_last_error = $r;
			return false;
		}
		
		$this->import( wp_get_term_object( $r['term_id'], $this->taxonomy )->to_array() );
		
		return true;
	}
	
	public function delete( $force = false ){
		
		if ( ! isset( $this->term_id ) ) 
			return false;
		
		$r = wp_delete_term( $this->term_id, $this->taxonomy );
		
		return $this->catch_return_bool( $r );
	}
	
	public function update_var( $key ){
		
		if ( ! $this->is_update_field( $key ) || ! $this->exists( $key ) )
			return false;
		
		$value = $this->get( $key );
		
		$r = wp_update_term( $this->get_id(), $this->taxonomy, array( $key => $value ) );
		
		return $this->catch_return_bool( $r );
	}


	/* ============================
			Custom methods
	============================= */

	public function get_taxonomy_object(){
		
		if ( !isset($this->taxonomyObject) ){
			$this->taxonomyObject =& wp_get_taxonomy_object( $this->taxonomy );
		}
		
		return $this->taxonomyObject;
	}
	
	public function get_hierarchy(){
		
		$tax = $this->get_taxonomy_object();
		
		if ( ! $tax->is_hierarchical() ){
			return null;
		}
		
		return $tax->get_term_hierarchy();	
	}
	
}