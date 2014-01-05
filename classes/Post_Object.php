<?php

class WordPress_Post_Object extends WordPress_Object_With_Metadata {
	
	protected $_object_name = 'post';
	
	protected $_primary_key = 'ID';
	
	// makes *_metadata() functions work
	protected $_meta_type = 'post';

	// overrides *_metadata() functions
	protected $_get_meta_callback		= 'get_post_meta';
	protected $_update_meta_callback	= 'update_post_meta';
	protected $_delete_meta_callback	= 'delete_post_meta';
	
	// Called by WordPress_Object_Factory once per id
	static function instance( $id ){
		
		return new self( get_post( $id, ARRAY_A ) );
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
	
	
	/* ======= Method Overrides ======== */
	
	function the_post_title( $before = '', $after = '' ){
		
		echo $before . $this->filter('post_title', $this->post_title) . $after;
	}
	
	function get_date( $d = '' ) {
		
		if ( '' == $d )
			$the_date = mysql2date( get_option('date_format'), $this->post_date );
		else
			$the_date = mysql2date( $d, $this->post_date );
	
		return apply_filters( 'get_the_date', $the_date, $d );
	}
	
	function get_modified_date($d = '') {

		if ( '' == $d )
			$the_time = $this->get_modified_time( get_option('date_format'), null, true);
		else
			$the_time = $this->get_modified_time( $d, null, true );
		
		return apply_filters('get_the_modified_date', $the_time, $d);
	}

	function get_time( $d = 'U', $gmt = false, $translate = false ) { // returns timestamp
		
		if ( $gmt )
			$time = $this->post_date_gmt;
		else
			$time = $this->post_date;
	
		$time = mysql2date( $d, $time, $translate );
		
		return apply_filters( 'get_post_time', $time, $d, $gmt );
	}
	
	function get_modified_time( $d = 'U', $gmt = false, $translate = false ){
		
		if ( $gmt )
			$time = $this->post_modified_gmt;
		else
			$time = $this->post_modified;
		
		$time = mysql2date($d, $time, $translate);
	
		return apply_filters('get_post_modified_time', $time, $d, $gmt);	
	}
	
	
	/* ======= Property Filters ======== */
		
	function filter_output( $key, $value ){
		
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
			
			case 'excerpt':
			case 'post_excerpt':
				return post_password_required() 
					? __( 'There is no excerpt because this is a protected post.' ) 
					: apply_filters( 'get_the_excerpt', $value );
			
			case 'date':
			case 'post_date':
				$the_date = $this->get_date( get_option('date_format') );
				return apply_filters('get_the_date', $the_date, $d);
			
			case 'modified_date':
			case 'post_modified_date':
				$the_time = $this->get_modified_time( get_option('date_format'), false, true );
				return apply_filters( 'get_the_modified_date', $the_time );
			
			default: return $value;
		}
	}
	
}