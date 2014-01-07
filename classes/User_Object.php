<?php

class WordPress_User_Object extends WordPress_Object_With_Metadata {
	
	public $caps; // not DB field
	
	public $cap_key; // not DB field
	
	public $roles; // not DB field
	
	public $filter; // not DB field
	
	protected $_object_name = 'user';
	
	protected $_primary_key = 'ID';
	
	// makes *_metadata() functions work
	protected $_meta_type = 'user';

	// overrides *_metadata() functions
	protected $_get_meta_callback		= 'get_user_meta';
	protected $_update_meta_callback	= 'update_user_meta';
	protected $_delete_meta_callback	= 'delete_user_meta';
	
	// from WP_User
	protected static $back_compat_keys;
	
	
	/**
	* Returns the object data to the factory for object instantiation
	* The object will not necessarily use this class directly - may be child.
	*/
	static function get_instance_data( $id ){
		
		return (array) get_userdata( $id )->data;	
	}
	
	// Called by constructor
	protected function onImport(){
		
		if ( ! isset( self::$back_compat_keys ) ) {
			$prefix = $GLOBALS['wpdb']->prefix;
			self::$back_compat_keys = array(
				'user_firstname' => 'first_name',
				'user_lastname' => 'last_name',
				'user_description' => 'description',
				'user_level' => $prefix . 'user_level',
				$prefix . 'usersettings' => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time',
			);
		}
		
		$this->init();
	}
	
	// Can be re-called to setup user capabilities for different blog ?
	public function init( $blog_id = '' ){
		
		$this->for_blog( $blog_id );	
	}
	
	
	function __isset( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1', __( 'Use <code>WP_User->ID</code> instead.' ) );
			$key = 'ID';
		}

		if ( isset( $this->$key ) )
			return true;

		if ( isset( self::$back_compat_keys[ $key ] ) )
			$key = self::$back_compat_keys[ $key ];
				
		return metadata_exists( 'user', $this->get_id(), $key );
	}

	/**
	 * Magic method for accessing custom fields
	 * @since 3.3.0
	 */
	function __get( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', '2.1', __( 'Use <code>WP_User->ID</code> instead.' ) );
			return $this->get_id();
		}

		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			if ( isset( self::$back_compat_keys[ $key ] ) )
				$key = self::$back_compat_keys[ $key ];
			$value = $this->get_meta( $key, true, $key );
		}

		if ( $this->filter ) {
			$value = sanitize_user_field( $key, $value, $this->get_id(), $this->filter );
		}

		return $value;
	}

	/**
	 * Magic method for setting custom fields
	 * @since 3.3.0
	 */
	function __set( $key, $value ) {
		if ( 'id' == $key ) {
			_deprecated_argument( '->id', '2.1', __( "Use <code>{$this->_primary_key}</code> instead." ) );
			$this->ID = $value;
			return;
		}

		$this->$key = $value;
	}


	/* ======= Roles/Capabilities ======== */
	
	public function for_blog( $blog_id = '' ) {
		global $wpdb;
		
		if ( ! empty( $blog_id ) )
			$cap_key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
		else
			$cap_key = '';
		
		$this->initCaps( $cap_key );
	}
	
	// originally WP_User::_init_caps()
	protected function initCaps( $cap_key = '' ){
		global $wpdb;

		if ( empty($cap_key) )
			$this->cap_key = $wpdb->get_blog_prefix() . 'capabilities';
		else
			$this->cap_key = $cap_key;

		$this->caps = $this->get_meta( $this->cap_key, true );

		if ( ! is_array($this->caps) )
			$this->caps = array();

		$this->get_role_caps();
	}
	
	public function get_role_caps(){
		global $wp_roles;

		if ( ! isset($wp_roles) )
			$wp_roles = new WP_Roles();

		//Filter out caps that are not role names and assign to $this->roles
		if ( is_array($this->caps) )
			$this->roles = array_filter( array_keys($this->caps), array($wp_roles, 'is_role') );

		//Build $allcaps from role caps, overlay user's $caps
		$this->allcaps = array();
		foreach ( (array) $this->roles as $role ) {
			$the_role = $wp_roles->get_role( $role );
			$this->allcaps = array_merge( (array) $this->allcaps, (array) $the_role->capabilities );
		}
		$this->allcaps = array_merge( (array) $this->allcaps, (array) $this->caps );

		return $this->allcaps;		
	}
	
		
	/* ======= Filters ======== */
	
	function filter_value( $key, $value ){
		
		switch($key){
			
			default: return $value;	
		}	
	}
	
	function filter_output( $key, $value ){
		
		switch($key){
			
			default: return $value;	
		}		
	}
	
	
	
}