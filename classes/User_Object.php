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
	
	static function get_instance_data( $id ){
		
		return (array) get_userdata( $id )->data;	
	}
	
	// Called by constructor
	protected function onImport(){
	
		$this->init();
	}
	
	// Can be re-called to setup user capabilities for different blog
	public function init( $blog_id = '' ){
		
		$this->for_blog( $blog_id );	
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

		$this->caps = get_user_meta( $this->get_id(), $this->cap_key, true );

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