<?php

abstract class WordPress_Object {
	
	protected $_object_name;
	
	protected $_primary_key;
	
	protected $_keys = array();
	
	abstract static function instance( $id );
	
	
	function __construct( &$data ){
		$this->import_properties( (array) $data );
	}
	
	// basic __isset()
	function __isset( $key ){
		return isset($this->$key);
	}
	
	// basic __get()
	function __get( $key ){
		return $this->__isset($key) ? $this->$key : null;
	}
	
	// basic __set()
	function __set( $key, $value ){
		$this->$key = $value;	
	}
	
	/**
	* Magic has_*(), get_*(), set_*(), and the_*() methods 
	* based on existance of property.
	*/
	function __call($function, $arguments){
		
		// Getters follow the pattern 'get_{$property}'
		if ( 0 === strpos($function, 'get_') ) {
			
			$property = substr($function, 4);
			return $this->filter( $property, $this->get($property) );
		}
		
		// Checkers follow the pattern 'has_{$property}'
		elseif ( 0 === strpos($function, 'has_') ) {
			
			$property = substr($function, 4);
			return $this->get($property) ? true : false;
		}
		
		// Setters follow the pattern 'set_{$property}'
		elseif ( 0 === strpos($function, 'set_') ) {
			
			$property = substr($function, 4);
			$this->set( $property, $arguments[0] );
		}
		
		// Echo-ers follow the pattern 'the_{$property}'
		elseif ( 0 === strpos($function, 'the_') ) {
			
			$property = substr($function, 4);
			$value = $this->get($property);
			
			if ( $value && is_scalar($value) ){
				
				if ( !$key = $this->translate_key($property) ) 
					return;
			
				if ( is_callable(array($this, 'the_' . $key)) ){
					return $this->{'the_' . $key}();
				}
				
				echo $this->filter( $key, $value );
			}
		}
	}
	
	/**
	* Returns an object property.
	* Translates key, applies filters if property does not exist.
	*/
	final function get( $key ){
		
		$prop = $this->translate_key( $key );	
				
		if ( null !== $prop && $this->__isset($prop) ){
			
			return $this->__get($prop);
		}
		else {
			return apply_filters( get_class($this) . '/' . $key, null, $prop );
		}
	}
		
	final function set( $key, $value ){
		$this->__set( $key, $value );
	}
	
	final function get_keys(){
		// 2nd param (false) => don't return primary key
		return x_wp_get_object_keys( $this->_object_name, false );
	}
	
	final function is_key( $key ){
		
		$keys = $this->get_keys();	
		
		return isset($keys[$key]);
	}
	
	final function get_aliased_key( $alias ){
		
		$key = x_wp_get_aliased_object_key( $this->_object_name, $alias );
		
		return $key ? $key : null;
	}
	
	/**
	* Returns the real key from a key or key alias.
	*/
	final function translate_key( $key ){
		
		if ( $this->is_key($key) ){
			return $key;	
		}
		if ( $real_key = $this->get_aliased_key($key) ){
			return $real_key;
		}
		return null;
	}
	
	/**
	* Returns the "id" based on object's $_primary_key
	*/
	final function get_id(){
		return $this->{$this->_primary_key};	
	}
	
	/**
	* Imports an array of data as object properties.
	*/
	final function import_properties( array $data ){
		foreach($data as $k => $v){
			$this->set( $k, $v );
		}	
	}
	
	/**
	* Filters a property for output.
	*/
	protected function filter( $key, $value ){
	
		if ( is_callable(array($this, 'filter_output')) ){
			$value = $this->filter_output( $key, $value );
		}
				
		return $value;
	}
	
	
	/* ============ WordPress API ============ */
	
		
	/* ==== Taxonomy ==== */
	
	function get_the_taxonomies( $args = array() ){
		return get_the_taxonomies( $this->get_id(), $args );	
	}
	
	function get_taxonomies( $output = 'names' ){
		return get_object_taxonomies( $this, $output );	
	}		
		/** Alias */ 
		function get_object_taxonomies( $output = 'names' ){
			return $this->get_taxonomies( $output );	
		}
		/** Extra */
		function get_taxonomy_objects(){
			return get_object_taxonomies( $this, 'objects' );	
		}
	
	function is_in_taxonomy( $taxonomy ){
		return is_object_in_taxonomy( $this->_object_name, $taxonomy );	
	}
	
	function is_in_term( $taxonomy, $terms = null ){
		return is_object_in_term( $this->get_id(), $taxonomy, $terms );
	}
	
	function set_terms( $terms, $taxonomy, $append = false ){
		return wp_set_object_terms( $this->get_id(), $terms, $taxonomy, $append );
	}
	
	function add_terms( $terms, $taxonomy ){
		return wp_set_object_terms( $this->get_id(), $terms, $taxonomy, true );
	}
	
	function remove_terms( $terms, $taxonomy ){
		return wp_remove_object_terms( $this->get_id(), $terms, $taxonomy );	
	}
	
	
	/* ==== Taxonomy ==== */
	
	
	
}