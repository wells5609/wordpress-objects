<?php

abstract class WP_Object_With_Metadata extends WP_Object {
	
	protected $metaType;
	
	/**
	* Returns meta value for given key.
	* Meta is stored in $meta property for subsequent use.
	*/
	function get_meta( $key = '', $single = false, $property = 'meta' ){
		
		if ( ( empty($key) && empty($this->{$property}) ) || empty($this->{$property}[$key]) ){
			
			$this->{$property} = array();
			
			if ( $callback = $this->getFunctionCallback(__FUNCTION__) ){
				
				$value = $callback( $this->get_id(), $key, $single );
			}
			else {
				$value = get_metadata( $this->getMetaType(), $this->get_id(), $key, $single );
			}
			
			// value might be array if getting all meta entries
			if ( empty($key) && is_array($value) ){
				foreach($value as $mk => $mv){
					$this->{$property}[ $mk ] = $mv;
				}
			}
			else {
				$this->{$property}[$key] = $value;
			}
		}
		
		if ( empty($key) ){
			return $this->{$property};	
		}
		
		return $this->{$property}[$key];
	}
	
	/**
	* Updates a meta entry in the database and resets object property.
	*/
	function update_meta( $key, $value, $prev_value = null, $property = 'meta' ){
				
		if ( $callback = $this->getFunctionCallback(__FUNCTION__) ){
		
			$callback( $this->get_id(), $key, $value, $prev_value );
		}
		else {
			if ( null !== $prev_value )
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $prev_value );
			else
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value );
		}
		
		$this->{$property}[$key] = $value;
	}
	
	/**
	* Deletes a meta entry from the database and removes from object property.
	*/
	function delete_meta( $key, $value = '', $delete_all = false, $property = 'meta' ){
		
		if ( $callback = $this->getFunctionCallback(__FUNCTION__) ){
		
			$callback( $this->get_id(), $key, $value );
		}
		else {
			delete_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $delete_all );
		}
		
		if ( isset($this->{$property}[$key]) ){
			unset($this->{$property}[$key]);
		}
	}
	
	/**
	* Returns $_meta_type property if set, otherwise $_object_name.
	* Used in *_metadata() functions as 1st parameter ($meta_type).
	*/
	protected function getMetaType(){
		return isset($this->metaType) ? $this->metaType : $this->objectName;	
	}
	
}
