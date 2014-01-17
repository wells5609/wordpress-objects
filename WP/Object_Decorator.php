<?php

abstract class WP_Object_Decorator {
	
	protected $_object;
	
	final public function __construct( WP_Object $object ){
		$this->_object =& $object;
	}
	
	final public function obj(){
		return $this->_object;	
	}
	
	final public function __call( $func, $params ){
		
		return $this->obj()->call( $func, $params, true );	
	}
	
}

class WP_Post_Object_Decorator extends WP_Object_Decorator {
	
	public function the_title( $before = '', $after = '' ){
		
		echo $before . $this->obj()->get( 'post_title' ) . $after;
	}
	
}
