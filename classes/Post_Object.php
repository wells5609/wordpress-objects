<?php

class WordPress_Post_Object extends WordPress_Object_With_Metadata 
	implements 
		WordPress_Updatable, 
		WordPress_Hierarchical, 
		WordPress_Permalinked 
{
	
	public $filter; // not DB field
	
	protected $objectType = 'post';
	
	protected $primaryKey = 'ID';
	
	// makes *_metadata() functions work
	protected $metaType = 'post';

	// overrides *_metadata() functions
	protected $callbacks = array(
		'get_meta'		=> 'get_post_meta',
		'update_meta'	=> 'update_post_meta',
		'delete_meta'	=> 'delete_post_meta',
	);
	
	/* ======== get_instance_data() ======== */
	
	static function get_instance_data( $id ){
		global $wpdb;
		
		$post_id = (int) $id;
		
		if ( ! $post_id ) return false;

		$_post = wp_cache_get( $post_id, 'posts' );

		if ( ! $_post ) {
			$_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );
			
			if ( ! $_post ) return false;
			
			$_post = sanitize_post( $_post, 'raw' );
			
			wp_cache_add( $_post->ID, $_post, 'posts' );
		} 
		elseif ( empty( $_post->filter ) ) {
			
			$_post = sanitize_post( $_post, 'raw' );
		}
		
		return $_post;
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_Permalinked' implementation 
	========================================================= */
	
	public function get_permalink(){
		return get_permalink( $this );	
	}
	
	public function the_permalink(){
		echo esc_url( apply_filters( 'the_permalink', $this->get_permalink() ) );	
	}
	
	
	/* ======================================================== 
		Interface 'WordPress_Hierarchical' implementation 
	========================================================= */
	
	public function is_parent(){
		return 0 === $this->post_parent;	
	}
	
	public function is_child(){
		return !$this->is_parent();	
	}
	
	public function has_parent(){
		return 0 !== $this->post_parent;	
	}
	
	public function has_children(){
		$childs = $this->get_children();
		return !empty($childs) ? true : false;
	}
	
	public function get_parents(){
		
		if ( !$this->has_parent() )
			return false;
		
		return x_wp_get_post_object( $this->post_parent );
	}
	
	public function get_children( $args = '',  $output = OBJECT ){
		
		if ( !isset($this->children) ){
			
			$args = wp_parse_args( $args, array('post_parent' => $this->get_id()) );
			
			$this->children = get_children( $args, $output );
		}
		
		return $this->children;	
	}
		
	/* ========================================================
		interface 'WordPress_Updatable' implementation 
	========================================================= */
	
	public function update(){
		
		$data = array();
		$keys = $this->get_keys();
		
		foreach($keys as $key){
			$data[$key] = $this->$key;
		}
		
		$r = wp_update_post( $data );
		
		return $r ? true : false;
	}
	
	public function insert(){
		
		$pk = $this->_primary_key;
		
		if ( isset($this->{$pk}) && !empty($this->{$pk}) ){
			// not a new post => update
			return $this->update();	
		}
		
		$data = array();
		$keys = $this->get_keys();
		
		unset($keys[$pk]); // remove primary key
		
		foreach($keys as $key){
			$data[$key] = $this->$key;
		}
		
		$post_id = wp_insert_post( $data, false );
		
		if ( 0 !== $post_id ){
			
			WordPress_Object_Factory::create_and_set_object( 'post', $post_id );
			
			$data = x_wp_get_post_object( $post_id )->to_array();
			
			$this->import( $data );
			
			return true;
		}
		
		return false;
	}
	
	public function delete( $force_delete = false ){
		
		return wp_delete_post( $this->get_id(), $force_delete );
	}
	
	public function update_var( $key ){
		
		if ( ! $key = $this->translate_key($key) ){
			return false;
		}
	
		$val = $this->$key;
		
		$r = wp_update_post( array('ID' => $this->get_id(), $key => $val) );
		
		return $r ? true : false;
	}
		
	/* ============================
		(Magic) Method Overrides 
	============================= */
	
	/**
	* Returns true if post has parent
	* @alias has_parent()
	*/
	public function has_post_parent(){
		return 0 !== $this->post_parent;	
	}
	
	/**
	* Prints the $post_title
	* @alias the_title()
	*/
	public function the_post_title( $before = '', $after = '' ){
		
		// Calling ->get_post_title() means the value will be run through filterValue() 
		echo $before . $this->get_post_title() . $after;
	}
	
	/**
	* Prints the $post_content
	* @alias the_content()
	*/
	public function the_post_content( $more_link_text = null, $strip_teaser = false) {
		$content = $this->get_post_content( $more_link_text, $strip_teaser );
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		echo $content;
	}
	
	/**
	* Returns the $post_content
	* @alias get_content()
	*/
	public function get_post_content( $more_link_text = null, $strip_teaser = false ) {
		// from get_the_content()
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
					$output .= apply_filters( 'the_content_more_link', ' <a href="' . $this->get_permalink() . "#more-{$this->get_id()}\" class=\"more-link\">$more_link_text</a>", $more_link_text );
				$output = force_balance_tags( $output );
			}
		}
	
		if ( $preview ) // preview fix for javascript bug with foreign languages
			$output =	preg_replace_callback( '/\%u([0-9A-F]{4})/', '_convert_urlencoded_to_entities', $output );
	
		return $output;
	}

	// for consistency with WP API
	function get_the_content( $more_text_link = null, $strip_teaser = false ){
		return $this->get_post_content( $more_text_link, $strip_teaser );	
	}

	// for consistency with WP API
	function get_the_excerpt(){
		return $this->get_post_excerpt();	
	}
	
	// for consistency with WP API
	function get_the_title(){
		return $this->get_post_title();
	}
		
	/**
	* returns $post_date
	* @alias get_date()
	*/
	public function get_post_date( $d = '' ){
		$the_date = '';
		
		if ( '' == $d )
			$d = get_option('date_format');
		
		$the_date .= $this->get_time( $d, false, true );
	
		return apply_filters('get_the_date', $the_date, $d);
	}
	
	/**
	* returns $post_modified
	* @alias get_modified()
	*/
	public function get_post_modified( $d = '' ){
		if ( '' == $d )
			$d = get_option('date_format');
		
		$the_time = $this->get_modified_time( $d, false, true );
		
		return apply_filters('get_the_modified_date', $the_time, $d);	
	}
		/** Alias for more consistent method names */ 
		function get_modified_date( $d = '' ){
			return $this->get_post_modified( $d );	
		}
	
	
	/* ============================
			Custom methods
	============================= */
	
	// from WP_Post
	public function filter( $filter ) {
		if ( $this->filter == $filter )
			return $this;

		if ( $filter == 'raw' )
			return x_wp_get_post_object( $this->get_id() );

		return sanitize_post( $this, $filter );
	}
	
	// from WP_Post
	public function to_array() {
		
		$post = get_object_vars( $this );
		foreach ( array( 'ancestors', 'page_template', 'post_category', 'tags_input' ) as $key ) {
			if ( $this->__isset( $key ) )
				$post[ $key ] = $this->__get( $key );
		}
		return $post;
	}
	
	/* ======== Post-type info ======== */
	
	public function is_post_type( $post_types ){
		if ( !is_array($post_types) )
			return $this->post_type === $post_types;
		return in_array($this->post_type, $post_types);	
	}
	
	public function is_revision(){
		return $this->is_post_type('revision');	
	}
	
	public function is_page(){
		return $this->is_post_type('page');	
	}
	
	public function is_attachment(){
		return $this->is_post_type('attachment');
	}
	
	public function is_post_type_custom(){
		return ! $this->is_post_type(array('post', 'page', 'attachment', 'revision', 'nav_menu_item'));	
	}
	
	public function get_post_type_object(){
		return get_post_type_object( $this->post_type );
	}
	
	public function is_hierarchical(){
		return $this->get_post_type_object()->hierarchical;	
	}
	
	/* ======== Post-type Archive links ======== */
	
	public function get_post_type_archive_link(){		
		return get_post_type_archive_link( $this->post_type );	
	}
	
	public function get_post_type_archive_feed_link( $feed = '' ){
		return get_post_type_archive_feed_link( $this->post_type, $feed );
	}
	
	/* ======== Password ======== */
	
	public function password_required(){
		return post_password_required( $this );
	}
	
	/* ======== Modified author ======== */
		
	public function get_the_modified_author() {
		
		if ( $last_id = $this->get_meta( '_edit_last', true ) ) {
			$last_user = get_userdata($last_id);
			return apply_filters('the_modified_author', $last_user->display_name);
		}
	}
	
	/* ======= Time - used by (magic) date methods ======== */
	
	/**
	* returns timestamp
	* @uses $post_date, $post_date_gmt
	*/
	public function get_time( $d = 'U', $gmt = false, $translate = false ) { 
		
		$time = $gmt ? $this->post_date_gmt : $this->post_date;
	
		$time = mysql2date( $d, $time, $translate );
		
		return apply_filters( 'get_post_time', $time, $d, $gmt );
	}
	
	/**
	* returns timestamp
	* @uses $post_date, $post_date_gmt
	*/
	public function get_modified_time( $d = 'U', $gmt = false, $translate = false ){
		
		$time = $gmt ? $this->post_modified_gmt : $this->post_modified;
		
		$time = mysql2date( $d, $time, $translate );
	
		return apply_filters( 'get_post_modified_time', $time, $d, $gmt );	
	}
	
	
	/* ======= Sticky post ======== */
	
	public function is_sticky() {
		$stickies = get_option('sticky_posts');
		if ( !is_array( $stickies ) )
			return false;
		if ( in_array( $this->get_id(), $stickies ) )
			return true;
		return false;
	}
	
	public function unstick_post(){
		$stickies = get_option('sticky_posts');
		if ( !is_array($stickies) || ! in_array($this->get_id(), $stickies) )
			return;
		$offset = array_search($this->get_id(), $stickies);
		if ( false === $offset )
			return;
		array_splice($stickies, $offset, 1);
		update_option('sticky_posts', $stickies);
	}
	
	/* ======= Page template ======== */
	
	public function get_page_template(){
		return $this->is_page() ? $this->get_meta( '_wp_page_template', true ) : null;
	}
	
		
	/* =============================
				Filters 
	============================== */
	
	protected function filterValue( $key, $value ){
		
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
				return $this->password_required() 
					? __( 'There is no excerpt because this is a protected post.' ) 
					: apply_filters( 'get_the_excerpt', $value );
			
			default: return $value;
		}
	}
	
	protected function filterOutput( $key, $value ){
		
		switch($key){
			
			default: return $value;	
		}	
		
	}
	
}