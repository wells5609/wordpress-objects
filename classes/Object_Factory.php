<?php

class WordPress_Object_Factory {
	
	static $objects = array();
	
	static function get_object( $name, $id, $var = null ){
		
		if ( !isset(self::$objects[$name]) ){
			self::$objects[$name] = array();
		}
		
		if ( !isset(self::$objects[$name][$id]) ){
			
			self::set_object($name, $id, $var);
		}
		
		return self::$objects[$name][$id];
	}
	
	static function set_object( $name, $id, $var = null ){
		
		$base_class = 'WordPress_' . ucfirst($name) . '_Object';
		
		$data = call_user_func( array($base_class, 'get_instance_data'), $id, $var );
		
		$class = apply_filters( 'wordpress_object_class', $base_class, $data );
		
		self::$objects[$name][$id] = new $class( $data );
	}
		
}
