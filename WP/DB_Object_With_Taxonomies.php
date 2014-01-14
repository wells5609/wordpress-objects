<?php
/**
* class WP_DB_Object_With_Taxonomies
*/
abstract class WP_DB_Object_With_Taxonomies extends WP_DB_Object {
	
	/* ====================================
			Taxonomy/term methods
	==================================== */
	
	/**
	* Returns array of object's taxonomy OBJECTS (not default behavior).
	* For names, use array keys.
	*
	* @rewritten
	* @see taxonomy.php get_object_taxonomies()
	*/
	final public function get_taxonomies(){
		
		if ( 'attachment' === $this->post_type )
			return get_attachment_taxonomies( $this );
		
		global $wp_taxonomies;
		$post_types = array( $this->post_type );
		$taxonomies = array();
		
		foreach ( $wp_taxonomies as $tax_name => $tax_obj ) {
			if ( array_intersect( $post_types, (array) $tax_obj->object_type ) ) {
				$taxonomies[ $tax_name ] = $tax_obj;
			}
		}
		
		return $taxonomies;
	}
	
	/**
	* Is the object in the given taxonomy?
	*
	* @see taxonomy.php is_object_in_taxonomy()
	*/
	final public function in_taxonomy( $taxonomy ){
		
		$taxonomies = $this->get_taxonomies();
		
		return empty($taxonomies) ? false : isset( $taxonomies[ $taxonomy ] );
	}
	
	/**
	* Get the object's terms
	* 
	* @rewritten
	* @see category-template.php get_the_terms()
	*/
	final function get_terms( $taxonomy ){
		
		$terms = get_object_term_cache( $this->get_id(), $taxonomy );
		
		if ( false === $terms ) {
			
			$terms = $this->get_object_terms( $taxonomy );
			
			wp_cache_add( $this->get_id(), $terms, $taxonomy . '_relationships' );
		}
	
		$terms = apply_filters( 'get_the_terms', $terms, $this->get_id(), $taxonomy );
	
		return $terms;
	}

	/**
	* Is the object in the given term?
	*
	* @rewritten
	* @see taxonomy.php is_object_in_term()
	*/
	final public function in_term( $taxonomy, $terms = null ) {
		
		$object_terms = $this->get_terms( $taxonomy );
		
		if ( is_wp_error($object_terms) )	return $object_terms;
		if ( empty( $object_terms ) )		return false;
		if ( empty( $terms ) )				return !empty( $object_terms );
		
		$terms = (array) $terms;
		
		if ( $ints = array_filter( $terms, 'is_int' ) )
			$strs = array_diff( $terms, $ints );
		else
			$strs =& $terms;
	
		foreach ( $object_terms as $object_term ) {
			if ( $ints && in_array( $object_term->term_id, $ints ) ) return true; // If int, check against term_id
			if ( $strs ) {
				if ( in_array( $object_term->term_id, $strs ) ) return true;
				if ( in_array( $object_term->name, $strs ) )    return true;
				if ( in_array( $object_term->slug, $strs ) )    return true;
			}
		}
		
		return false;
	}

	/**
	* Does object have the given term?
	* 
	* @see category-template.php has_term()
	*/
	final public function has_term( $term = '', $taxonomy = '' ){

		$in_term = $this->in_term( $taxonomy, $term );
		
		return is_wp_error($in_term) ? false : $in_term;
	}
	
	/* ============================================
			wp_*_object_terms() functions
	============================================ */
	
	/**#!#
	* Get terms
	*/
	final public function get_object_terms( $taxonomies, $args = array() ){
		
		return wp_get_object_terms( $this->get_id(), $taxonomies, $args );
	}
	
	/**#!#
	* Set terms
	*/
	final public function set_object_terms( $terms, $taxonomy, $append = false ){
		
		return wp_set_object_terms( $this->get_id(), $terms, $taxonomy, $append );
	}
	
	/**
	* Add terms
	*/
	final public function add_object_terms( $terms, $taxonomy ){
		
		return $this->set_object_terms( $terms, $taxonomy, true );
	}
	
	/**#!#
	* Remove terms
	*/
	final public function remove_object_terms( $terms, $taxonomy ){
		
		return wp_remove_object_terms( $this->get_id(), $terms, $taxonomy );
	}
	
	
	/* ========================================
			Output rendering functions
	======================================== */
	
	/**#!#
	* Returns the object's taxonomies for output
	*
	* @see get_the_taxonomies()
	*/
	public function get_taxonomy_list( $args = array() ){
		
		return get_the_taxonomies( $this->get_id(), $args );	
	}
		
	/**
	* Return a list of terms for output
	*
	* @rewritten
	* @see get_the_term_list()
	*/
	public function get_term_list( $taxonomy, $before = '', $sep = '', $after = '' ){
		
		$terms = $this->get_terms( $taxonomy );
	
		if ( is_wp_error($terms) )		return $terms;
		if ( empty($terms) )			return false;
	
		foreach ( $terms as $term ) {
			
			$link = get_term_link( $term, $taxonomy );
			
			if ( is_wp_error($link) ) {
				return $link;
			}
			
			$term_links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . $term->name . '</a>';
		}
	
		$term_links = apply_filters( "term_links-$taxonomy", $term_links );
	
		return $before . join( $sep, $term_links ) . $after;
	}
	
	/**
	* Print a list of terms
	*
	* @see the_terms()
	*/
	public function the_terms( $taxonomy, $before = '', $sep = ', ', $after = '' ){
		
		$term_list = $this->get_term_list( $taxonomy, $before, $sep, $after );
	
		if ( is_wp_error($term_list) ) return false;
	
		echo apply_filters('the_terms', $term_list, $taxonomy, $before, $sep, $after);
	}

	/* ==== Tags ==== */

	final public function get_tags(){
		
		return apply_filters( 'get_the_tags', $this->get_terms( 'post_tag' ) );
	}

	final public function get_tag_list( $before = '', $sep = '', $after = '' ){
		
		$tags = $this->get_term_list('post_tag', $before, $sep, $after);
		
		return apply_filters( 'the_tags', $tags, $before, $sep, $after, $this->get_id() );
	}

	public function the_tags( $before = null, $sep = ', ', $after = '' ) {
		
		if ( null === $before ) {
			$before = __('Tags: ');
		}
		
		echo $this->get_tag_list( $before, $sep, $after );
	}
	
	final public function has_tag( $tag = '' ) {
		
		return $this->has_term( $tag, 'post_tag' );
	}
	
	
	/* ==== Categories ==== */
	
	/**
	* Returns object categories
	*
	* @rewritten
	* @see category-template.php get_the_category()
	*/
	final public function get_category(){
		
		$categories = $this->get_terms( 'category' );
		
		if ( ! $categories || is_wp_error( $categories ) )
			$categories = array();
		
		$categories = array_values( $categories );
		
		foreach ( array_keys( $categories ) as $key ) {
			_make_cat_compat( $categories[$key] );
		}
		
		return apply_filters( 'get_the_categories', $categories );
	}
	
	/**#!#
	* Returns the object's categories for output
	*
	* @see category-template.php get_the_category_list()
	*/
	final public function get_category_list( $separator = '', $parents='' ){
		
		return get_the_category_list( $separator, $parents, $this->get_id() );
	}
	
	final public function the_category( $separator = '', $parents='' ){
		
		echo $this->get_category_list( $separator, $parents );
	}
	
	final public function has_category( $category = '' ){
		
		return $this->has_term( $category, 'category' );
	}
	
	final public function in_category( $category = '' ){
		
		return empty($category) ? false : $this->has_category( $category );
	}
		
}
