<?php

class WordPress_Object_Factory {
	
	static protected $objects = array();
	
	/**
	* Returns a WordPress object.
	* 
	* Returns an object by type and identifier, creating if necessary.
	*
	* @param string $object_type The object type (e.g. 'post', 'user')
	* @param int|string $object_id The identifier for the object (e.g. post ID, tax name)
	* @param mixed $var An extra variable, currently only for terms (to pass taxonomy)
	* @return object An instance of WordPress_Object.
	*/
	static function get_object( $object_type, $object_id, $var = null ){
		
		if ( ! isset( self::$objects[ $object_type ] ) ){
			self::$objects[ $object_type ] = array();
		}
		
		if ( ! isset( self::$objects[ $object_type ][ $object_id ] ) ){
			
			$object = self::create_object( $object_type, $object_id, $var );
			
			self::set_object( $object );
		}
		
		return self::$objects[ $object_type ][ $object_id ];
	}
	
	/**
	* Adds a WordPress_Object to $objects property.
	* 
	* @param WordPress_Object $object A WordPress_Object instance
	* @return void
	*/
	static function set_object( WordPress_Object &$object ){
		
		self::$objects[ $object->get_object_type() ][ $object->get_id() ] = $object;
	}
	
	/**
	* Creates a WordPress object.
	* 
	* First gets data from base class (e.g. 'WordPress_Post_Object' for any post-type)
	* by calling get_instance_data().
	* Filters class name and then creates and object of that class.
	*
	* @param string $object_type The object type (e.g. 'post', 'user')
	* @param int|string $object_id The identifier for the object (e.g. post ID, tax name)
	* @param mixed $var An extra variable, currently only for terms (to pass taxonomy)
	* @return object An object instance.
	*/
	static function create_object( $object_type, $object_id, $var = null ){
		
		$base_class = 'WordPress_' . ucfirst($object_type) . '_Object';
		
		$data = call_user_func( array($base_class, 'get_instance_data'), $object_id, $var );
		
		$Class = apply_filters( 'wordpress_object_class', $base_class, $data );
		
		return new $Class( $data );
	}
	
	/**
	* Calls create_object() and set_object().
	* @see create_object, set_object
	*/
	static function create_and_set_object( $object_type, $object_id, $var = null ){
	
		$object = self::create_object( $object_type, $object_id, $var );
		
		self::set_object( $object );
	}
	
}
