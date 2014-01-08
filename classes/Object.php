<?php

abstract class WordPress_Object {
	
	protected $_object_name;
	
	protected $_primary_key;
	
	
	/* ============ Abstract methods (required) ============ */
	
	/**
	* Returns array of object data - imported as properties.
	*/
	abstract static function get_instance_data( $id );
	
	/**
	* Filters a property value.
	*/
	abstract protected function filter_value( $key, $value );
	
	/**
	* Filters a property value for output.
	*/
	abstract protected function filter_output( $key, $value );
	
	
	/* ============ Protected methods (optional) ============ */
	
	/**
	* Called at start of __construct()
	*/
	protected function preConstruct(&$data){}
	
	/**
	* Called at end of __construct()
	*/
	protected function onConstruct(){}
		
	
	/* ============ Magic methods ============ */
	
	// Sets data as properties
	final function __construct( &$data ){
		
		$this->preConstruct( $data );
		
		$this->import( (array) $data );
		
		$this->onConstruct();
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
	* has_*(), get_*(), set_*(), and the_*() methods based on 
	* existance of property.
	*/
	function __call($function, $arguments){
		
		// Checkers follow the pattern 'has_{$property}'
		if ( 0 === strpos($function, 'has_') ) {
			
			$property = substr($function, 4);
			
			if ( $key = $this->translate_key($property) ) 
				$property = $key;
			
			if ( method_exists( $this, 'has_' . $property ) ){
				
				return $this->call( 'has_' . $property, $arguments );
			}
			
			return isset($this->$property);
		}
		
		// Setters follow the pattern 'set_{$property}'
		elseif ( 0 === strpos($function, 'set_') ) {
			
			$property = substr($function, 4);
			
			if ( $key = $this->translate_key($property) ) 
				$property = $key;
			
			$this->$property = $arguments[0];
		}
		
		// Getters follow the pattern 'get_{$property}'
		elseif ( 0 === strpos($function, 'get_') ) {
			
			$property = substr($function, 4);
			
			if ( $key = $this->translate_key($property) ) 
				$property = $key;
			
			if ( method_exists( $this, 'get_' . $property ) ){
				
				return $this->call( 'get_' . $property, $arguments );
			}
			
			return $this->filter_value( $property, $this->get($property) );
		}
		
		// Echo-ers follow the pattern 'the_{$property}'
		elseif ( 0 === strpos($function, 'the_') ) {
			
			$property = substr($function, 4);
			
			if ( $key = $this->translate_key($property) ) 
				$property = $key;
			
			if ( method_exists( $this, 'the_' . $property ) ){
				
				return $this->call( 'the_' . $property, $arguments );
			}
			
			$value = $this->filter_value( $property, $this->get($property) );
			
			if ( $value && is_scalar($value) ){
				
				echo $this->filter_output( $property, $value );
			}
		}
	}
	
	
	/* ============ Basic methods ============ */
	
	/**
	* Returns the "id" based on object's $_primary_key
	*/
	final function get_id(){
		return $this->{$this->_primary_key};	
	}
	
	/**
	* Imports an array of data as object properties and calls ->onImport()
	*/
	final function import( array $data ){
		foreach($data as $k => $v){
			$this->set( $k, $v );
		}
	}
	
	/**
	* Returns an object property.
	* Translates key, applies filters if property does not exist.
	*/
	final function get( $key ){
		
		if ( isset($this->$key) ){
			
			return $this->__get($key);	
		}
		
		$prop = $this->translate_key( $key );
				
		if ( null !== $prop && $this->__isset($prop) ){
			
			return $this->__get($prop);
		}
		
		else {
			return apply_filters( get_class($this) . '/' . $key, null, $prop );
		}
	}
	
	/**
	* Sets an object property.
	*/
	final function set( $key, $value ){
		
		$prop = $this->translate_key( $key );	
		
		$this->__set($prop, $value);
	}
	
	
	/* ============ Key & Alias methods ============ */
	
	/**
	* Returns assoc. array of keys and aliases
	*/
	final function get_keys_and_aliases(){
		static $keys_aliases;
		if ( !isset($keys_aliases) ){
			$keys_aliases = x_wp_get_object_keys( $this->_object_name );
		}
		return $keys_aliases;
	}
	
	/**
	* Returns array of object keys
	*/
	final function get_keys(){
	
		return array_keys( $this->get_keys_and_aliases() );
	}
		
	/**
	* Returns array of object aliases
	*/
	final function get_aliases(){
	
		return array_values( $this->get_keys_and_aliases() );	
	}
	
	/**
	* Returns true if passed string is a key (NOT alias)
	*/
	final function is_key( $key ){
		
		return in_array( $key, $this->get_keys() );
	}
	
	/**
	* Returns true if passed string is an alias (NOT key)
	*/
	final function is_alias( $key ){
		
		return in_array( $key, $this->get_aliases() );	
	}
	
	/**
	* Gets key from alias
	*/
	final function get_aliased_key( $alias ){
		
		return array_search( $alias, $this->get_keys_and_aliases() );
	}
	
	/**
	* Returns the real key from a key or key alias.
	*/
	final function translate_key( $key ){
		
		if ( $this->is_key($key) ){
			return $key;	
		}
		if ( $aliased = $this->get_aliased_key($key) ){
			return $aliased;
		}
		return null;
	}
	
	
	/* ============ Forwarding ============ */
	
	/**
	* Calls $this->$function() using passed $args
	* Does not check if callable.
	*/
	final function call( $function, $args = array() ){
		
		if ( empty($args) ){
			return $this->$function();
		}
		// call_user_func_array() is ~3x slower than direct calls so use as last resort
		switch( count($args) ){
			case 1:
				return $this->$function( $args[0] );
			case 2:
				return $this->$function( $args[0], $args[1] );
			case 3:
				return $this->$function( $args[0], $args[1], $args[2] );
			case 4:
				return $this->$function( $args[0], $args[1], $args[2], $args[3] );
			case 5:
				return $this->$function( $args[0], $args[1], $args[2], $args[3], $args[4] );
			case 6:
				return $this->$function( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5] );
			default:
				return call_user_func_array( array($this, $function), $args );
		}
	}
		
	/**
	* Returns callback function string, if set, to use instead of given function.
	*/
	protected function getFunctionCallback( $func ){
		return isset($this->{'_' . $func . '_callback'})
			? $this->{'_' . $func . '_callback'}
			: false;
	}
	
	
	/* ============ WordPress API: Taxonomy ============ */
	
	/**
	* Is the object in the given taxonomy?
	*/
	function in_taxonomy( $taxonomy ){
		
		return in_array( $taxonomy, $this->get_object_taxonomies('names') );	
	}
	
	/**
	* Is the object in the given term?
	*/
	function in_term( $taxonomy, $terms = null ){
		
		return is_object_in_term( $this->get_id(), $taxonomy, $terms );
	}
	
	/**
	* Does the object have the given term?
	*/
	function has_term( $term = '', $taxonomy = '' ){

		$r = $this->in_term( $taxonomy, $term );
		
		if ( is_wp_error($r) )
			return false;
	
		return $r;
	}
	
	/**
	* Returns the object's taxonomies for output
	*/
	function get_the_taxonomies( $args = array() ){
		return get_the_taxonomies( $this->get_id(), $args );	
	}
	
	/**
	* Returns array of object's taxonomy names (default) or objects.
	*/
	function get_object_taxonomies( $output = 'names' ){
		return get_object_taxonomies( $this, $output );	
	}
	
	/**
	* Get the object terms
	* Rewrite to avoid the post check in get_the_terms() function 
	* @see category-template.php line 1084
	*/
	function get_the_terms( $taxonomy ){
	
		$terms = get_object_term_cache( $this->get_id(), $taxonomy );
		
		if ( false === $terms ) {
			
			$terms = $this->get_object_terms( $taxonomy );
			
			wp_cache_add($this->get_id(), $terms, $taxonomy . '_relationships');
		}
	
		$terms = apply_filters( 'get_the_terms', $terms, $this->get_id(), $taxonomy );
	
		if ( empty( $terms ) )
			return false;
		
		return $terms;	
	}
	
	/**
	* Return a list of terms for output
	*/
	function get_the_term_list( $taxonomy, $before = '', $sep = '', $after = '' ){
		
		$terms = $this->get_the_terms( $taxonomy );
	
		if ( is_wp_error( $terms ) ) return $terms;
		if ( empty( $terms ) ) return false;
	
		foreach ( $terms as $term ) {
			$link = get_term_link( $term, $taxonomy );
			if ( is_wp_error( $link ) ) return $link;
			$term_links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . $term->name . '</a>';
		}
	
		$term_links = apply_filters( "term_links-$taxonomy", $term_links );
	
		return $before . join( $sep, $term_links ) . $after;
	}
	
	/**
	* Print a list of terms
	*/
	function the_terms( $taxonomy, $before = '', $sep = ', ', $after = '' ){
		
		$term_list = $this->get_the_term_list( $taxonomy, $before, $sep, $after );
	
		if ( is_wp_error($term_list) ) return false;
	
		echo apply_filters('the_terms', $term_list, $taxonomy, $before, $sep, $after);
	}

	// Tags

	function get_the_tags(){
		return apply_filters( 'get_the_tags', $this->get_the_terms( 'post_tag' ) );
	}

	function get_the_tag_list( $before = '', $sep = '', $after = '' ){
		return apply_filters( 'the_tags', $this->get_the_term_list('post_tag', $before, $sep, $after), $before, $sep, $after, $this->get_id() );
	}

	function the_tags( $before = null, $sep = ', ', $after = '' ) {
		if ( null === $before ) $before = __('Tags: ');
		echo $this->get_the_tag_list( $before, $sep, $after );
	}
	
	function has_tag( $tag = '' ) {
		return $this->has_term( $tag, 'post_tag' );
	}
	
	// Categories

	function get_the_category(){
		$categories = $this->get_the_terms( 'category' );
		if ( ! $categories || is_wp_error( $categories ) )
			$categories = array();
		$categories = array_values( $categories );
		foreach ( array_keys( $categories ) as $key ) {
			_make_cat_compat( $categories[$key] );
		}
		return apply_filters( 'get_the_categories', $categories );
	}

	function get_the_category_list( $separator = '', $parents='' ){
		return get_the_category_list( $separator, $parents, $this->get_id() );
	}
	
	function the_category( $separator = '', $parents='' ){
		echo $this->get_the_category_list( $separator, $parents );
	}
	
	function in_category( $category ){
		if ( empty( $category ) ) return false;
		return $this->has_category( $category );
	}
	
	function has_category( $category = '' ){
		return $this->has_term( $category, 'category' );
	}
	
	/** The following methods query/save to the DB */
	
	// NOTE: does not use cache - prefer get_the_terms()
	function get_object_terms( $taxonomies, $args = array() ){
		return wp_get_object_terms( $this->get_id(), $taxonomies, $args );
	}
	
	function set_object_terms( $terms, $taxonomy, $append = false ){
		return wp_set_object_terms( $this->get_id(), $terms, $taxonomy, $append );
	}
	
	function add_object_terms( $terms, $taxonomy ){
		return wp_set_object_terms( $this->get_id(), $terms, $taxonomy, true );
	}
	
	function remove_object_terms( $terms, $taxonomy ){
		return wp_remove_object_terms( $this->get_id(), $terms, $taxonomy );	
	}
	
}