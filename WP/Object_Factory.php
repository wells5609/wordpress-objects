<?php

class WP_Object_Factory {
	
	static protected $objects = array();
	
	static protected $_instance;
	
	static final function instance(){
		if ( !isset(self::$_instance) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	/**
	* Returns a WordPress object by type and identifier, creating if necessary.
	*
	* @param string $object_type The object type (e.g. 'post', 'user')
	* @param int|string $object_id The identifier for the object (e.g. post ID, tax name)
	* @param mixed $var An extra variable, currently only for terms (to pass taxonomy)
	* @return object An instance of WordPress_Object.
	*/
	static public function get( $object_type, $object_id, $var = null ){
		
		if ( ! isset( self::$objects[ $object_type ] ) ){
			self::$objects[ $object_type ] = array();
		}
		
		if ( ! isset( self::$objects[ $object_type ][ $object_id ] ) ){
		
			$_this = self::instance();
			$object = $_this->create( $object_type, $object_id, $var );
			
			self::set( $object );
		}
		
		return self::$objects[ $object_type ][ $object_id ];
	}
	
	/**
	* Adds a WP_Object to $objects property.
	* 
	* @param WordPress_Object $object A WordPress_Object instance
	* @return void
	*/
	static public function set( WP_Object &$object ){
		
		return self::$objects[ $object->get_object_type() ][ $object->get_id() ] = $object;
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
	public function create( $object_type, $object_id, $var = null ){
		
		$_this = self::instance();
		
		return $_this->create_from_data( $object_type, $_this->get_data( $object_type, $object_id, $var ) );
	}
	
	/**
	* Retrieves object data from base class.
	* 
	* @param string $object_type The object type (e.g. 'post', 'user')
	* @param int|string $object_id The identifier for the object (e.g. post ID, tax name)
	* @param mixed $var An extra variable, currently only for terms (to pass taxonomy)
	* @return array|object Object data for import.
	*/
	public function get_data( $object_type, $object_id, $var = null ){
			
		return is_scalar($object_id) 
			? call_user_func( array( _wp_get_object_base_class( $object_type ), 'get_instance_data' ), $object_id, $var )
			: $object_id;		
	}
	
	/**
	* Creates class instance from data.
	* 
	* @param string $object_type The object type (e.g. 'post', 'user')
	* @param array $data The object's data to import.
	* @return WP_Object A WP_Object instance.
	*/
	public function create_from_data( $object_type, $data = array() ){
			
		$class = apply_filters( 'wordpress_object_class', _wp_get_object_base_class( $object_type ), &$data );
		
		$object = new $class( $data );
		
		return self::set( $object );
	}
	
}