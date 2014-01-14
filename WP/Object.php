<?php
/**
* class WP_Object
*/
abstract class WP_Object {
	
	protected $_type;
	
	protected $_uid_property;
	
	public $_last_error;
	
	/* ================================
			Abstract methods
	================================ */
	
	abstract function get_fields(); // return indexed array
	
	/* ============================
			Public methods
	============================ */
	
	public function __isset( $key ){
		return isset( $this->$key );
	}
	
	public function __get( $key ){
		return $this->__isset( $key ) ? $this->$key : null;
	}
	
	public function __set( $key, $value ){
		$this->$key = $value;
	}
	
	/**
	* Calls do_action( {called class}.{function} [.{context} ], $args );
	*/
	public function do_action( $fn, $context = '', $args = array() ){
		
		$action = get_class( $this ) . '.' . $fn;
		
		if ( !empty($context) )
			$action .= '.' . $context;
		
		return do_action( $action, $args, $this );
	}
	
	/**
	* Adds an action to be performed when called class does action. 
	* See WP_Post_Object for examples.
	*/
	public function add_action( $fn_context, $callback, $priority = 10, $num_args = 1 ){
		
		add_action( get_class( $this ) . '.' . ltrim( $fn_context, '.' ), $callback, $priority, $num_args );
	}
	
	/* ============================
			Final methods
	============================ */
	
	final public function __construct( $data ){
		
		$this->objectInit();
		
		$this->do_action( __FUNCTION__, 'before', &$data );
		
		$this->import( (array) $data );
		
		$this->do_action( __FUNCTION__, 'after' );
	}
	
	final public function exists( $var ){
		return $this->__isset( $var );	
	}
	
	final public function get( $var ){
		return $this->exists( $var ) ? $this->__get( $var ) : null;	
	}
	
	final public function set( $var, $val ){
		$this->__set( $var, $val );	
		return $this;
	}
	
	/**
	* Returns $_type property string.
	*/
	final public function get_object_type(){
		return $this->_type;	
	}
	
	final public function get_identifier(){
		return $this->_uid_property;	
	}
	
	/**
	* Returns the object identifier based on object's $_uid
	*/
	final public function get_id(){
		return $this->get( $this->_uid_property );	
	}
	
	/**
	* Imports an array of data as object properties.
	*/
	final public function import( $data ){
		
		$this->do_action( __FUNCTION__, 'before', &$data );
		
		foreach($data as $k => $v){
			$this->__set( $k, $v );
		}
		
		$this->do_action( __FUNCTION__, 'after', $data );
	}
	
	/**
	* Calls $this->$function() using passed $args
	*/
	final public function call( $function, $args = array(), $check_callable = false ){
		
		$this->do_action( __FUNCTION__, 'before', array($function, $args, $check_callable) );
		
		if ( $check_callable && ! is_callable( array($this, $function) ) )
			return new WP_Error('uncallable_method', "Cannot call method $function on class " . get_called_class() . '.' );
		
		if ( empty($args) )
			return $this->$function();
		
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
	* If $r is WP_Error, sets as $_last_error property and returns false, otherwise true. 
	* Useful for returning a boolean "success" value.
	*/
	final public function catch_return_bool( $r ){
		
		if ( is_wp_error( $r ) ){
			$this->_last_error = $r;
			return false;
		}
		
		return true;	
	}
	
	
	/* ================================
			Protected methods
	================================ */
	
	/**
	* Allows classes to add actions and do other setup processes. 
	* Called at start of __construct()
	*/
	protected function objectInit(){}
	
}


/**
* class WP_Global_Object
*/
abstract class WP_Global_Object extends WP_Object {
	
	/* ================================
		WP_Object abstract methods
	================================ */
	
	final public function get_fields(){
		return $GLOBALS['wp_object_fields']['global'][ $this->_type ];	
	}
		
}


/**
* class WP_DB_Object
*/
abstract class WP_DB_Object extends WP_Object {
	
	/* ================================
		WP_Object abstract methods
	================================ */
	
	final public function get_fields(){
		return $GLOBALS['wp_object_fields']['db'][ $this->_type ];
	}
	
	/* ================================
			Abstract methods
	================================ */
		
	abstract function update();
	
	abstract function insert();
	
	abstract function delete( $force = false );
	
	abstract function update_var( $key );

	abstract function get_update_fields();
	
	/* ============================
			Public methods
	============================ */
	
	public function is_update_field( $key ){
		return in_array( $key, $this->get_update_fields() );	
	}

}
