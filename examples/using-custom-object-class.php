<?php
/**
* WordPress Object examples
* @package WordPress-Objects
*/

/**
* EXAMPLE 1: Using a custom object class
*/

/**
* Step 1: Add filter on 'wordpress_object_class'
*
* @param string $class The class to use for object instantiation
* @param array $data The object data
*/ 
add_filter('wordpress_object_class', '_switch_object_class', 10, 2);

	function _switch_object_class( $class, $data ){
		
		/**
		* Step 2: Return class string if data matches desired conditions.
		*
		* In this case, we use a custom 'WordPress_Page_Object' class for 'page' post-types.
		*/
		if ( isset($data['post_type']) && 'page' === $data['post_type'] ){
			
			return 'WordPress_Page_Object';
		}
		
		return $class;
	}
	
	
class WordPress_Page_Object extends WordPress_Post_Object {
	
	
	function am_i_a_page(){
		
		echo 'Yes!';	
	}
		
}

