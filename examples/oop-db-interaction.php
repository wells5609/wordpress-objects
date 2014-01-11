<?php
/**
* Example: Object-oriented database interaction
*/

/**
* Example 1: Updating an existing object
*/

$post = x_wp_get_post_object( 123 ); // Get the object

$post->set( 'post_author', get_current_user_ID() ); // set the post author to current user

$post->set( 'post_status', 'publish' ); // change the status to 'publish'

if ( $post->update() ) { // returns true if successful
	
	echo 'The new author of this post is ' . get_userdata( $post->post_author )->display_name . '!';
	
	// continue using $post with updated properties
}


/**
* Example 2: Updating a single object property.
*/

$post = x_wp_get_post_object( 123 );

$post->set( 'post_status', 'publish' ); // change post_status to 'publish'

$post->update_var( 'post_status' ); // update the property.

// done.


/** 
* Example 3: Creating a new object programatically
*/

$a = array(); // the post data to import

$new_post = new WordPress_Post_Object( $a ); // MUST pass array variable (by ref)

$new_post->set( 'post_title', 'A New Title' );
$new_post->set( 'post_content', 'This is the content.' );
// etc...

// Will use defaults for properties not set.
// Set terms after insert

// Insert into the DB - returns true if successful
if ( $new_post->insert() ){
	
	// safe to add terms
	$new_post->set_terms( array($term1, $term2), 'post_tags' );
	
	// new post properties are available without re-instantiation.
	$new_post_author = $new_post->get_author();

}