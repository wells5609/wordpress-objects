<?php

class WordPress_Post_Object extends WordPress_Object_With_Metadata implements WordPress_Updatable, WordPress_Hierarchical {
	
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
	
	public function is_type( $post_types ){
		if ( !is_array($post_types) )
			return $this->post_type === $post_types;
		return in_array($this->post_type, $post_types);	
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
	
	public function get_post_type_object(){
		return get_post_type_object( $this->post_type );
	}
	
	public function is_hierarchical(){
		return $this->get_post_type_object()->hierarchical;	
	}
	
	public function password_required(){
		return post_password_required( $this );
	}
	
	public function get_permalink( $leavename = false ){
		return get_permalink( $this, $leavename );	
	}
	
	public function the_permalink(){
		echo esc_url( apply_filters( 'the_permalink', $this->get_permalink() ) );	
	}
	
	
	/* ======= WordPress_Hierarchical methods ======== */
	
	function is_parent(){
		return 0 === $this->post_parent;	
	}
	
	function is_child(){
		return !$this->is_parent();	
	}
	
	function has_parent(){
		return 0 !== $this->post_parent;	
	}
	
	function has_children(){
		$childs = $this->get_children();
		return !empty($childs) ? true : false;
	}
	
	function get_parents(){
		
		if ( !$this->has_parent() )
			return false;
		
		return x_wp_get_post_object( $this->post_parent );
	}
	
	function get_children( $args = '',  $output = OBJECT ){
		
		if ( !isset($this->children) ){
			
			$args = wp_parse_args( $args, array('post_parent' => $this->get_id()) );
			
			$this->children = get_children( $args, $output );
		}
		
		return $this->children;	
	}
	
	
	/* ======= WordPress_Updatable methods ======== */
	
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
	
	function delete( $force_delete = false ){
		
		return wp_delete_post( $this->get_id(), $force_delete );
	}
	
	function update_var( $key ){
		
		if ( ! $key = $this->translate_key($key) ){
			return false;
		}
	
		$val = $this->$key;
		
		return wp_update_post( array('ID' => $this->ID, $key => $val) );
	}
	
	
	/* ======= (Magic) Method Overrides ======== */
	
	public function has_post_parent(){
		return 0 !== $this->post_parent;	
	}
	
	function the_post_title( $before = '', $after = '' ){
		
		// Calling ->get_post_title() means the value will be run through filter_value() 
		echo $before . $this->get_post_title() . $after;
	}
	
	function the_post_content( $more_link_text = null, $strip_teaser = false) {
		$content = $this->get_post_content( $more_link_text, $strip_teaser );
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		echo $content;
	}
	
	function get_post_content( $more_link_text = null, $strip_teaser = false ) {
		
		global $page, $more, $preview, $pages, $multipage;
	
		if ( null === $more_link_text )
			$more_link_text = __( '(more&hellip;)' );
	
		$output = '';
		$has_teaser = false;
	
		// If post password required and it doesn't match the cookie.
		if ( $this->password_required() )
			return get_the_password_form( $this );
	
		if ( $page > count( $pages ) ) // if the requested page doesn't exist
			$page = count( $pages ); // give them the highest numbered page that DOES exist
	
		$content = $pages[$page - 1];
		if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
			$content = explode( $matches[0], $content, 2 );
			if ( ! empty( $matches[1] ) && ! empty( $more_link_text ) )
				$more_link_text = strip_tags( wp_kses_no_null( trim( $matches[1] ) ) );
			$has_teaser = true;
		} else {
			$content = array( $content );
		}
	
		if ( false !== strpos( $this->post_content, '<!--noteaser-->' ) && ( ! $multipage || $page == 1 ) )
			$strip_teaser = true;
	
		$teaser = $content[0];
	
		if ( $more && $strip_teaser && $has_teaser )
			$teaser = '';
	
		$output .= $teaser;
	
		if ( count( $content ) > 1 ) {
			if ( $more ) {
				$output .= '<span id="more-' . $this->get_id() . '"></span>' . $content[1];
			} else {
				if ( ! empty( $more_link_text ) )
					$output .= apply_filters( 'the_content_more_link', ' <a href="' . get_permalink( $this->get_id() ) . "#more-{$post->ID}\" class=\"more-link\">$more_link_text</a>", $more_link_text );
				$output = force_balance_tags( $output );
			}
		}
	
		if ( $preview ) // preview fix for javascript bug with foreign languages
			$output =	preg_replace_callback( '/\%u([0-9A-F]{4})/', '_convert_urlencoded_to_entities', $output );
	
		return $output;
	}
		
		function get_the_excerpt(){
			return $this->get_post_excerpt();	
		}
		
		function get_the_content( $more_text_link = null, $strip_teaser = false ){
			return $this->get_post_content( $more_text_link, $strip_teaser );	
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
		/** Alias for more consistent method names */ 
		function get_modified_date( $d = '' ){
			return $this->get_post_modified( $d );	
		}
			
	
	/* ======= Time - used by date methods ======== */
	
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