<?php

abstract class WordPress_Object_With_Metadata extends WordPress_Object {
	
	public $meta = array();
	
	protected $_meta_type;
	
	
	function __isset( $key ){
		
		// Check for a meta value, semi-magically
		if ( $this->is_meta_key($key) ){
			return isset($this->meta[ $this->meta_key_filter($key) ]);	
		}
		
		return isset($this->$key);	
	}
	
	function __get( $key ){
		
		if ( !$this->__isset($key) ) return null;
		
		// Get a meta value, semi-magically
		if ( $this->is_meta_key($key) ){
			return $this->meta[ $this->meta_key_filter($key) ];	
		}
		
		return $this->$key;
	}
	
	function __set( $key, $value ){
		
		// Set a meta value, semi-magically
		if ( $this->is_meta_key($key) ){
			$this->meta[ $this->meta_key_filter($key) ] = $value;	
		}
		else { // setting normal property
			$this->$key = $value;	
		}
	}
	
	/**
	* Returns true if given string is an existing array key in $meta,
	* or if it begins with 'meta_' - used in magic methods.
	*/
	final function is_meta_key( $string ){
		return isset($this->meta[$string]) || 0 === strpos($string, 'meta_');
	}
	
	/**
	* Removes 'meta_' from $string (to use as property key).
	*/
	final function meta_key_filter( $string ){
		return str_replace('meta_', '', $string);
	}
	
	/**
	* Returns callback function string, if set, to use instead of 
	* corresponding *_metadata() function, otherwise false.
	*/
	final function get_meta_callback( $func ){
		if (isset($this->{'_' . $func . '_callback'}) ){
			return $this->{'_' . $func . '_callback'};
		}
		return false;
	}
	
	/**
	* Returns meta value for given key.
	* Meta is stored in $meta property for subsequent use.
	*/
	final function get_meta( $key, $single = false ){
		
		if ( !$this->__isset($key) ){
			
			if ( $callback = $this->get_meta_callback(__FUNCTION__) ){
				$value = $callback( $this->get_id(), $key, $single );
			}
			else {
				$value = get_metadata( $this->getMetaType(), $this->get_id(), $key, $single );
			}
			
			$this->__set( 'meta_' . $key, $value );
		}
		
		return $this->__get($key);
	}
	
	/**
	* Updates a meta entry in the database and resets object property.
	*/
	final function update_meta( $key, $value, $prev_value = null ){
				
		if ( $callback = $this->get_meta_callback(__FUNCTION__) ){
			$callback( $this->get_id(), $key, $value, $prev_value );
		}
		else {
			if ( null !== $prev_value )
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $prev_value );
			else
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value );
		}
		
		$this->__set( 'meta_' . $key, $value );
	}
	
	/**
	* Deletes a meta entry from the database and removes from object property.
	*/
	final function delete_meta( $key, $value = '', $delete_all = false ){
		
		if ( $callback = $this->get_meta_callback(__FUNCTION__) ){
			$callback( $this->get_id(), $key, $value );
		}
		else {
			delete_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $delete_all );
		}
		
		if ( $this->__isset($key) ){
			unset($this->meta[$key]);
		}
	}
	
	/**
	* Returns $_meta_type property if set, otherwise $_object_name.
	* Used in *_metadata() functions as 1st parameter ($meta_type).
	*/
	protected function getMetaType(){
		return isset($this->_meta_type) ? $this->_meta_type : $this->_object_name;	
	}
	
}
