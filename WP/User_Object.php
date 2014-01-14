<?php

class WP_User_Object extends WP_DB_Object {
	
	protected $_type = 'user';
	
	protected $_uid_property = 'ID';
	
	// from WP_User
	protected static $backCompatKeys;
	
	public $caps; // not DB field
	public $cap_key; // not DB field
	public $roles; // not DB field
	public $filter; // not DB field
	
	/* ================================
			get_instance_data() 
	================================ */
	
	static function get_instance_data( $id ){
		
		return (array) get_userdata( $id )->data;	
	}
	
	// Called before construction
	protected function objectInit(){
	
		$this->add_action( '__construct.after', array($this, 'init') );
		
		if ( ! isset( self::$backCompatKeys ) ) {
			$prefix = $GLOBALS['wpdb']->prefix;
			self::$backCompatKeys = array(
				'user_firstname' => 'first_name',
				'user_lastname' => 'last_name',
				'user_description' => 'description',
				'user_level' => $prefix . 'user_level',
				$prefix . 'usersettings' => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time',
			);
		}	
	}
		
	/* ========================================================
		interface 'WordPress_Updatable' implementation 
	========================================================= */
	
	public function get_update_fields(){
		return array_merge( $this->get_fields, array('first_name', 'last_name', 'description', 'rich_editing', 'role', 'nickname', 'jabber', 'aim', 'yim', 'show_admin_bar_front') );
	}
	
	public function update(){
		
		$data = array();
		$keys = $this->get_update_fields();
		
		foreach($keys as $key){
			if ( $this->exists( $key ) )
				$data[$key] = $this->get( $key );
		}
		
		return wp_update_user( $data );
	}
	
	public function insert(){
		
		$pk = $this->_primary_key;
		
		if ( isset($this->ID) && !empty($this->ID) ){
			// not new => update
			return $this->update();	
		}
		
		$data = array();
		$keys = $this->get_update_keys();
		
		unset($keys[$pk]); // remove primary key
		
		foreach($keys as $key){
			if ( $this->exists( $key ) )
				$data[$key] = $this->get( $key );
		}
		
		return wp_insert_user( (object) $data );
	}
	
	public function delete( $force_delete = false ){
		
		return wp_delete_user( $this->get_id(), $force_delete );
	}
	
	public function update_var( $key ){
		
		if ( ! $this->is_update_field( $key ) ){
			return false;
		}
	
		$val = $this->get( $key );
		
		return wp_update_user( array('ID' => $this->get_id(), $key => $val) );
	}
	
		
	/* ============================
		Magic Method Overrides 
	============================= */
		
	function __isset( $key ) {
		
		if ( 'id' == $key ) return isset($this->ID);
		
		if ( isset( $this->$key ) )
			return true;

		if ( isset( self::$backCompatKeys[ $key ] ) )
			$key = self::$backCompatKeys[ $key ];
				
		return metadata_exists( 'user', $this->get_id(), $key );
	}

	function __get( $key ) {
		
		if ( 'id' == $key ) return isset($this->ID) ? $this->ID : null;
		
		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} else {
			if ( isset( self::$backCompatKeys[ $key ] ) )
				$key = self::$backCompatKeys[ $key ];
			$value = $this->get_meta( $key, true, $key );
		}

		if ( ! empty( $this->filter ) ) {
			$value = sanitize_user_field( $key, $value, $this->get_id(), $this->filter );
		}

		return $value;
	}

	function __set( $key, $value ) {
		
		if ( 'id' == $key )
			$this->ID = $value;
		else
			$this->$key = $value;
	}


	/* ============================
			Custom methods
	============================= */
	
	// Can be re-called to setup user capabilities for different blog... I think ?
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
	
	public function has_cap( $capability ){
		
		if ( isset($this->allcaps[$capability]) )
			return $this->allcaps[$capability];
			
		if ( is_numeric( $cap ) ) {
			_deprecated_argument( __FUNCTION__, '2.0', __('Usage of user levels by plugins and themes is deprecated. Use roles and capabilities instead.') );
			$cap = 'level_' . $cap;
		}

		$args = array_slice( func_get_args(), 1 );
		$args = array_merge( array( $cap, $this->get_id() ), $args );
		$caps = call_user_func_array( 'map_meta_cap', $args );

		// Multisite super admin has all caps by definition, Unless specifically denied.
		if ( is_multisite() && is_super_admin( $this->get_id() ) ) {
			if ( in_array('do_not_allow', $caps) )
				return false;
			return true;
		}

		// Must have ALL requested caps
		$capabilities = apply_filters( 'user_has_cap', $this->allcaps, $caps, $args, $this );
		$capabilities['exist'] = true; // Everyone is allowed to exist
		foreach ( (array) $caps as $cap ) {
			if ( empty( $capabilities[ $cap ] ) )
				return false;
		}
		
		return true;
	}
	
	// Alias
	function can( $cap ){
		return $this->has_cap( $cap );	
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

		$this->initRoleCaps();
	}
	
	// originally get_role_caps() (not semantic name)
	protected function initRoleCaps(){
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
		
}