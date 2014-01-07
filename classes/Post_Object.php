<?php

class WordPress_Post_Object extends WordPress_Object_With_Metadata {
	
	public $filter; // not DB field
	
	protected $_object_name = 'post';
	
	protected $_primary_key = 'ID';
	
	// makes *_metadata() functions work
	protected $_meta_type = 'post';

	// overrides *_metadata() functions
	protected $_get_meta_callback		= 'get_post_meta';
	protected $_update_meta_callback	= 'update_post_meta';
	protected $_delete_meta_callback	= 'delete_post_meta';
	
	// Called by WordPress_Object_Factory once per id
	static function get_instance_data( $id ){
		
		return get_post( $id, ARRAY_A );
	}
	
	public function is_page(){
		return 'page' === $this->post_type;	
	}
	
	public function is_attachment(){
		return 'attachment' === $this->post_type;
	}
	
	public function is_custom_post_type(){
		return !in_array($this->post_type, array('post', 'page', 'attachment', 'revision', 'nav_menu_item'));	
	}
	
	public function password_required(){
		return post_password_required( $this );
	}
	
	public function has_post_parent(){
		return 0 !== $this->post_parent;	
	}
	
	
	/* ======= Database methods ======== */
	
	function update(){
		
		$data = array();
		$keys = $this->get_keys();
		
		foreach($keys as $key){
			$data[$key] = $this->$key;
		}
		
		return wp_update_post( $data );
	}
	
	function insert(){
		
		$pk = $this->_primary_key;
		
		if ( isset($this->$pk) && !empty($this->$pk) ){
			// not a new post => update
			return $this->update();	
		}
		
		$data = array();
		$keys = $this->get_keys();
		
		unset($keys[$pk]); // remove primary key
		
		foreach($keys as $key){
			$data[$key] = $this->$key;
		}
		
		return wp_insert_post( $data );
	}
	
	
	/* ======= (Magic) Method Overrides ======== */
	
	function the_post_title( $before = '', $after = '' ){
		
		// Calling ->get_post_title() means the value will be run through filter_value() 
		echo $before . $this->get_post_title() . $after;
	}
	
	function get_post_date( $d = '' ){
		$the_date = '';
		
		if ( '' == $d )
			$d = get_option('date_format');
		
		$the_date .= $this->get_time( $d, false, true );
	
		return apply_filters('get_the_date', $the_date, $d);
	}
	
	function get_post_modified( $d = '' ){
		if ( '' == $d )
			$d = get_option('date_format');
		
		$the_time = $this->get_modified_time( $d, false, true );
		
		return apply_filters('get_the_modified_date', $the_time, $d);	
	}
	
		/** Alias for consistent method names */ 
		function get_modified_date( $d = '' ){
			return $this->get_post_modified( $d );	
		}
			
	
	/* ======= Time ======== */
	
	// returns timestamp
	function get_time( $d = 'U', $gmt = false, $translate = false ) { 
		
		$time = $gmt ? $this->post_date_gmt : $this->post_date;
	
		$time = mysql2date( $d, $time, $translate );
		
		return apply_filters( 'get_post_time', $time, $d, $gmt );
	}
	
	// returns timestamp
	function get_modified_time( $d = 'U', $gmt = false, $translate = false ){
		
		$time = $gmt ? $this->post_modified_gmt : $this->post_modified;
		
		$time = mysql2date( $d, $time, $translate );
	
		return apply_filters( 'get_post_modified_time', $time, $d, $gmt );	
	}
	
	
	/* ======= Sticky post ======== */
	
	function is_sticky() {
		
		$stickies = get_option('sticky_posts');
	
		if ( !is_array( $stickies ) )
			return false;
	
		if ( in_array( $this->get_id(), $stickies ) )
			return true;
	
		return false;
	}
	
	function unstick_post(){
		
		$stickies = get_option('sticky_posts');
	
		if ( !is_array($stickies) || ! in_array($this->get_id(), $stickies) )
			return;
	
		$offset = array_search($this->get_id(), $stickies);
		
		if ( false === $offset )
			return;
		
		array_splice($stickies, $offset, 1);
	
		update_option('sticky_posts', $stickies);
	}
	
	
	function get_page_template(){
		return $this->get_meta( '_wp_page_template', true );	
	}
	
		
	/* ======= Filters ======== */
	
	function filter_value( $key, $value ){
		
		switch($key){
			
			case 'post_title':
				// From get_the_title() {post-template.php line 102}
				if ( ! is_admin() ) {
					if ( ! empty($this->post_password) ){
						$format = apply_filters( 'protected_title_format', __( 'Protected: %s' ) );
					} 
					else if ( isset($this->post_status) && 'private' == $this->post_status ){
						$format = apply_filters( 'private_title_format', __( 'Private: %s' ) );
					}
					if ( isset($format) ) $value = sprintf( $format, $value );
				}
				return apply_filters( 'the_title', $value, $this->get_id() );
			
			case 'guid':
				return apply_filters( 'get_the_guid', $value );
			
			case 'post_excerpt':
				return post_password_required() 
					? __( 'There is no excerpt because this is a protected post.' ) 
					: apply_filters( 'get_the_excerpt', $value );
			
			default: return $value;
		}
	}
	
	function filter_output( $key, $value ){
		
		switch($key){
			
			
			default: return $value;	
		}	
		
	}
	
}