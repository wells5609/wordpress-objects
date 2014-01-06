<?php

abstract class WordPress_Object_With_Metadata extends WordPress_Object {
	
	public $meta = array();
	
	protected $_meta_type;
	
	
	/**
	* Returns meta value for given key.
	* Meta is stored in $meta property for subsequent use.
	*/
	function get_meta( $key = '', $single = false ){
		
		if ( empty($this->meta[$key]) ){
			
			if ( $callback = $this->getMetaCallback('get_meta') ){
				$value = $callback( $this->get_id(), $key, $single );
			}
			else {
				$value = get_metadata( $this->getMetaType(), $this->get_id(), $key, $single );
			}
			
			// value might be array because (1) $single = false or (2) getting all meta entries
			if ( is_array($value) && (empty($key) || $single) ){
				foreach($value as $mk => $mv){
					$this->meta[$mk] = $mv;
				}
			}
			else {
				$this->meta[$key] = $value;
			}
		}
		
		if ( !empty($key) ){
			return $this->meta[$key];	
		}
		
		return $this->meta;
	}
	
	/**
	* Updates a meta entry in the database and resets object property.
	*/
	function update_meta( $key, $value, $prev_value = null ){
				
		if ( $callback = $this->getMetaCallback(__FUNCTION__) ){
			$callback( $this->get_id(), $key, $value, $prev_value );
		}
		else {
			if ( null !== $prev_value )
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $prev_value );
			else
				update_metadata( $this->getMetaType(), $this->get_id(), $key, $value );
		}
		
		$this->meta[$key] = $value;
	}
	
	/**
	* Deletes a meta entry from the database and removes from object property.
	*/
	function delete_meta( $key, $value = '', $delete_all = false ){
		
		if ( $callback = $this->getMetaCallback(__FUNCTION__) ){
			$callback( $this->get_id(), $key, $value );
		}
		else {
			delete_metadata( $this->getMetaType(), $this->get_id(), $key, $value, $delete_all );
		}
		
		if ( isset($this->meta[$key]) ){
			unset($this->meta[$key]);
		}
	}
	
	/**
	* Returns callback function string, if set, to use instead of 
	* corresponding *_metadata() function, otherwise false.
	*/
	protected function getMetaCallback( $func ){
		if ( isset($this->{'_' . $func . '_callback'}) ){
			return $this->{'_' . $func . '_callback'};
		}
		return false;
	}
	
	/**
	* Returns $_meta_type property if set, otherwise $_object_name.
	* Used in *_metadata() functions as 1st parameter ($meta_type).
	*/
	protected function getMetaType(){
		return isset($this->_meta_type) ? $this->_meta_type : $this->_object_name;	
	}
	
}
