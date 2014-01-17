<?php
/**
* class WP_Post_Object
*/
class WP_Post_Object extends WP_DB_Object_With_Taxonomies 
	implements 
		WP_Metadata_Interface, 
		WP_Permalink_Interface,
		WP_Hierarchy_Interface 
{
	
	protected $_type = 'post';
	protected $_uid_property = 'ID';
	
	public $meta = array();
	
	
	/* ================================
			get_instance_data() 
	================================ */
	
	/**
	* Returns an array of data to be imported on object instantiation.
	*/
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
	
	/**
	* Setup processes and actions.
	* 
	* With the action added below, $this->beforeUpdate() will be called
	* prior to saving data in $this->update()
	* Note action callbacks must be public (as always).
	*/
	protected function objectInit(){
		
		$this->add_action( 'update.before', array($this, 'beforeUpdate') );	
	}
		
	function beforeUpdate(){
		$this->page_template	= $this->get_page_template();
		$this->post_category	= $this->_get_post_category();
		$this->tags_input		= $this->_get_tags_input();
	}
	
	/* ====================================
			WP_Hierarchy_Interface 
	==================================== */
	
	/**
	* Returns true if $post_parent is 0
	*/
	public function is_parent(){
		return 0 === $this->post_parent;	
	}
	
	/**
	* Returns true if $post_parent is not 0 
	*/
	public function is_child(){
		return $this->has_parent();	
	}
	
	/**
	* Returns true if $post_parent is not 0 
	*/
	public function has_parent(){
		return 0 !== $this->post_parent;	
	}
	
	/**
	* Returns true if object has child objects
	*/
	public function has_children(){
		$childs = $this->get_children();
		return !empty($childs) ? true : false;
	}
	
	/**
	* Returns object's parent object(s)
	*/
	public function get_parents(){
		
		return $this->has_parent() 
			? wp_get_object( 'post', $this->post_parent ) 
			: false;
	}
	
	/**
	* Returns object's child objects
	*/
	public function get_children( $args = '',  $output = OBJECT ){
		
		if ( !isset($this->children) ){
			
			$args = wp_parse_args( $args, array('post_parent' => $this->get_id()) );
			
			$this->children = get_children( $args, $output );
		}
		
		return $this->children;	
	}
	
	/* ====================================
			WP_Permalink_Interface 
	==================================== */
	
	/**
	* Returns object's permalink (URL)
	*/
	final public function get_permalink(){
		return get_permalink( $this );	
	}
	
	/**
	* Prints object's permalink (HTML)
	*/
	final public function the_permalink(){
		echo esc_url( apply_filters( 'the_permalink', $this->get_permalink() ) );	
	}
	
	/* ====================================
			WP_Metadata_Interface
	==================================== */
	
	/**
	* Returns meta value for given key, or all meta if no key given.
	*/
	public function get_meta( $meta_key = '', $single = false ){
		
		if ( empty($meta_key) )
			return $this->meta = get_post_meta( $this->get_id(), $meta_key, $single );
		
		if ( !isset( $this->meta[ $meta_key ] ) ) 
			$this->meta[ $meta_key ] = get_post_meta( $this->get_id(), $meta_key, $single );
		
		return $this->meta[ $meta_key ];
	}
	
	/**
	* Updates meta value for given key.
	*/
	public function update_meta( $meta_key, $meta_value, $prev_value = null ){
		
		update_post_meta( $this->get_id(), $meta_key, $meta_value, $prev_value );
		
		return $this->meta[ $meta_key ] = $meta_value;	
	}
	
	/**
	* Deletes meta value for given key, or all meta if no key given.
	*/
	public function delete_meta( $meta_key = '', $meta_value = '', $delete_all = false ){
		
		delete_post_meta( $this->get_id(), $meta_key, $meta_value );
		
		if ( isset( $this->meta[ $meta_key ] ) )
			unset( $this->meta[ $meta_key ] );
	}
	
	/* ========================================
			WP_DB_Object abstract methods
	======================================== */
		
	/**
	* Returns indexed array of object properties to be 
	* passed to object update function (e.g. wp_update_post()).
	*/
	public function get_update_fields(){
		
		return array_merge( $this->get_fields(), array('post_category', 'tags_input', 'tax_input', 'page_template') );
	}
	
	/**
	* Updates object data, returns true if successful.
	*/
	final public function update(){
		
		$data = array();
		$keys = $this->get_update_fields();
		
		foreach($keys as $key){
			$data[$key] = $this->get( $key );
		}
		
		$this->do_action( __FUNCTION__, 'before', $data );
		
		$updated = wp_update_post( $data );
		
		$this->do_action( __FUNCTION__, 'after', $updated );
		
		return $this->catch_return_bool( $updated );
	}
	
	/**
	* Inserts new object, returns true if successful.
	*
	* $this will automatically be synced with the new properties,
	* including those set by default (e.g. post ID).
	*/
	final public function insert(){
		
		if ( isset( $this->ID ) ){
			return $this->update();	// not new
		}
		
		$data = array();
		$keys = $this->get_update_fields();
		
		unset($keys['ID']); // remove primary key
		
		foreach($keys as $key){
			$data[$key] = $this->get( $key );
		}
		
		$this->do_action( __FUNCTION__, 'before', $data );
		
		$inserted = wp_insert_post( $data, true );
		
		$this->do_action( __FUNCTION__, 'after', $inserted );
		
		if ( is_wp_error($inserted) ){
			$this->_last_error = $inserted;
			return false;
		}
	
		// no re-instantiation required !
		$this->import( wp_get_post_object( $inserted )->to_array() );
		
		return true;
	}
	
	/**
	* Deletes object data.
	*/
	final public function delete( $force = false ){
		
		$this->do_action( __FUNCTION__, 'before', $force );
		
		$deleted = wp_delete_post( $this->get_id(), $force );
		
		$this->do_action( __FUNCTION__, 'after', $deleted );
		
		return $this->catch_return_bool( $deleted );
	}
	
	/**
	* Updates a single object field using the value as currently
	* defined by the object (i.e. use ->set( 'somekey', ... ) before calling)
	*/
	final public function update_var( $key ){
		
		if ( ! $this->is_update_field( $key ) ){
			return false;
		}
	
		$val = $this->get( $key );
		
		$this->do_action( __FUNCTION__, 'before', $force );
		
		$updated = wp_update_post( array('ID' => $this->get_id(), $key => $val) );
		
		$this->do_action( __FUNCTION__, 'after', $updated );
		
		return $this->catch_return_bool( $updated );
	}
	
	/* ============================
			WP_Post methods
	============================ */
	
	/**
	* Applies sanitize_* filters
	*/
	final public function filter( $filter ) {
		if ( $this->filter == $filter )
			return $this;

		if ( $filter == 'raw' )
			return wp_get_object( 'post', $this->get_id() );

		return sanitize_post( $this, $filter );
	}
	
	/**
	* Returns object properties as array, including some "extras".
	*/
	final public function to_array() {
		$post = get_object_vars( $this );
		foreach ( array( 'ancestors', 'page_template', 'post_category', 'tags_input' ) as $key ) {
			if ( $this->exists( $key ) )
				$post[ $key ] = $this->get( $key );
		}
		return $post;
	}
	
	
	/* ============================
			Custom methods
	============================ */
	
	/**
	* Returns ancestors.
	*
	* @rewritten
	* @see get_ancestors()
	*/
	final public function get_ancestors(){
		
		if ( ! $this->is_parent() )
			return array();
	
		$ancestors = array();
		$id = $ancestors[] = $this->post_parent;
	
		while ( $ancestor = wp_get_post_object( $id ) ) {
			// Loop detection: If the ancestor has been seen before, break.
			if ( empty( $ancestor->post_parent ) || ( $ancestor->post_parent == $this->ID ) || in_array( $ancestor->post_parent, $ancestors ) )
				break;
	
			$id = $ancestors[] = $ancestor->post_parent;
		}
	
		return $ancestors;	
	}
	
	/* ======= Date methods ======== */
		
	/**
	* returns $post_date
	* @rewritten
	* @see get_the_date()
	*/
	final public function get_date( $format = '' ){
		
		if ( empty( $format ) )
			$format = get_option('date_format');
		
		return apply_filters( 'get_the_date', $this->get_time( $format, false, true ), $format );
	}
	
	/**
	* returns $post_modified
	* @rewritten
	* @see get_the_modified_date()
	*/
	final public function get_modified_date( $format = '' ){
		
		if ( empty( $format ) )
			$format = get_option('date_format');
		
		return apply_filters( 'get_the_modified_date', $this->get_modified_time( $format, false, true ), $format );	
	}
	
	/* ======= Time - used by date methods ======== */
	
	/**
	* returns timestamp
	* @rewritten
	* @uses $post_date, $post_date_gmt
	*/
	final public function get_time( $d = 'U', $gmt = false, $translate = false ) { 
		
		$time = $gmt ? $this->post_date_gmt : $this->post_date;
		
		return apply_filters( 'get_post_time', mysql2date( $d, $time, $translate ), $d, $gmt );
	}
	
	/**
	* returns timestamp
	* @rewritten
	* @uses $post_date, $post_date_gmt
	*/
	final public function get_modified_time( $d = 'U', $gmt = false, $translate = false ){
		
		$time = $gmt ? $this->post_modified_gmt : $this->post_modified;
		
		return apply_filters( 'get_post_modified_time', mysql2date( $d, $time, $translate ), $d, $gmt );	
	}
	
	/* ======== Post-type info ======== */
	
	/**
	* Returns true if post-type is = string or in array.
	*/
	final public function is_post_type( $post_types ){
		
		if ( ! is_array( $post_types ) )
			return $this->post_type === $post_types;
		
		return in_array( $this->post_type, $post_types );	
	}
	
	/**
	* Returns true if post-type is 'revision'.
	*/
	final public function is_revision(){
		
		return $this->is_post_type( 'revision' );	
	}
	
	/**
	* Returns true if post-type is 'page'.
	*/
	final public function is_page(){
		
		return $this->is_post_type( 'page' );	
	}
	
	/**
	* Returns true if post-type is 'attachment'.
	*/
	final public function is_attachment(){
		
		return $this->is_post_type( 'attachment' );
	}
	
	/**
	* Returns true if custom post-type.
	*/
	final public function is_post_type_custom(){
		
		return ! $this->is_post_type( array('post', 'page', 'attachment', 'revision', 'nav_menu_item') );	
	}
	
	/**
	* Returns post-type object.
	*/
	final public function get_post_type_object(){
		
		return get_post_type_object( $this->post_type );
	}
	
	/**
	* Returns true if post-type is hierarchical.
	*/
	final public function is_post_type_hierarchical(){
		
		return $this->get_post_type_object()->hierarchical;	
	}
	
	/**
	* Returns link to post-type archive.
	*/
	final public function get_archive_link(){		
		
		return get_post_type_archive_link( $this->post_type );	
	}
		
	/* ======= Page template ======== */
	
	/**
	* Returns page template if is a page, otherwise null.
	*/
	final public function get_page_template(){
		
		return $this->is_page() ? $this->get_meta( '_wp_page_template', true ) : null;
	}
		
	/* ======== Password ======== */
	
	/**
	* Returns true if password required.
	*/
	final public function password_required(){
		
		return post_password_required( $this );
	}
	
	/* ======= Post formats ======== */
	
	/**
	* Returns post format.
	*/
	final public function get_format() {
		
		if ( ! post_type_supports( $this->post_type, 'post-formats' ) )
			return false;
		
		$_format = $this->get_terms( 'post_format' );
		
		if ( empty( $_format ) )
			return false;
		
		$format = array_shift( $_format );
		
		return str_replace('post-format-', '', $format->slug );
	}
	
	/**
	* Returns true if has post format.
	*/
	final public function has_format( $format = array() ) {
		
		$prefixed = array();
		if ( $format ) {
			foreach ( (array) $format as $single ) {
				$prefixed[] = 'post-format-' . sanitize_key( $single );
			}
		}
		
		return $this->has_term( $prefixed, 'post_format' );
	}
	
	/**
	* Sets the post format.
	*/
	final public function set_format( $format ) {

		if ( ! empty( $format ) ) {
			$format = sanitize_key( $format );
			if ( 'standard' === $format || ! in_array( $format, get_post_format_slugs() ) )
				$format = '';
			else
				$format = 'post-format-' . $format;
		}
		return $this->set_terms( $format, 'post_format' );
	}
	
	/* ======= Thumbnails ======== */
	
	/**
	* Returns thumbnail's ID, if set.
	*/
	final public function get_thumbnail_id() {

		return $this->get_meta( '_thumbnail_id', true );
	}
	
	/**
	* Returns true if has thumbnail.
	*/
	final public function has_thumbnail() {

		return (bool) $this->get_thumbnail_id();
	}

	/**
	* Returns the thumbnail HTML.
	*/
	final public function get_thumbnail( $size = 'post-thumbnail', $attr = '' ) {
		
		$post_id			= $this->get_id();
		$post_thumbnail_id	= $this->get_thumbnail_id();
		$size 				= apply_filters( 'post_thumbnail_size', $size );
		
		if ( $post_thumbnail_id ) {
		
			do_action( 'begin_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size );
		
			if ( in_the_loop() )
				update_post_thumbnail_cache();
		
			$html = wp_get_attachment_image( $post_thumbnail_id, $size, false, $attr );
		
			do_action( 'end_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size );
		} 
		else {
			$html = '';
		}
		
		return apply_filters( 'post_thumbnail_html', $html, $post_id, $post_thumbnail_id, $size, $attr );
	}
	
	/**
	* Prints the thumbnail HTML.
	*/
	final public function the_thumbnail( $size = 'post-thumbnail', $attr = '' ) {

		echo $this->get_thumbnail( $size, $attr );
	}
	
	/* ======= Sticky post ======== */
	
	/**
	* Returns true if is a sticky post.
	*/
	final public function is_sticky() {
		
		$stickies = get_option('sticky_posts');
		
		if ( !is_array($stickies) ) return false;
		
		return in_array( $this->get_id(), $stickies );
	}
	
	/**
	* Unsticky the post.
	*/
	final public function unstick(){
		
		$stickies = get_option('sticky_posts');
		
		if ( !is_array($stickies) || ! in_array($this->get_id(), $stickies) )
			return;
		
		$offset = array_search($this->get_id(), $stickies);
		
		if ( false === $offset )
			return;
		
		array_splice($stickies, $offset, 1);
		
		update_option('sticky_posts', $stickies);
	}
	
	/* ======== Pre-update callbacks ======== */
	
	/**
	* Called before update to set 'post_category' property.
	*/
	final public function _get_post_category(){

		if ( $this->in_taxonomy( 'category' ) )
			$terms = $this->get_terms( 'category' );

		if ( empty($terms) ) return array();

		return wp_list_pluck( $terms, 'term_id' );	
	}
	
	/**
	* Called before update to set 'tags_input' property.
	*/
	final public function _get_tags_input(){
	
		if ( $this->in_taxonomy( 'post_tag' ) )
			$terms = $this->get_terms( 'post_tag' );

		if ( empty($terms) ) return array();

		return wp_list_pluck( $terms, 'name' );	
	}
	
}
