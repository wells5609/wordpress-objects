wordpress-objects
=================

A prototype project for object-oriented WordPress data types.


## WordPress Today

WordPress data types (which will henceforth be understood to be posts, users, taxonomies, terms, and the like) are largely "procedural" in structure and operation. While this may have been ideal in the past, it now creates inefficiencies, redundancies, and inconsisetencies, as WP has grown to be much more than was originally intended.

The developers of WordPress have [recognized this] (https://core.trac.wordpress.org/ticket/12267), and with the advent of WP_User and WP_Post, seem to be moving towards object-orientation.

However, the current approach is piecemeal (at best) and without a consistent and replicable structure. This project aims to provide that structure.

## Project Overview

This project provides a base (albeit, incomplete so far) on which to build WordPress objects that are consistent and efficient, without losing any of their current functionality. The structure allows for a easy transition to the use of traits, once supported.

#### Core Classes

There is one factory object, `WordPress_Object_Factory`, which is called to retrieve objects. This means the `new` keyword will never be used to instantiate a WordPress object.

There are two abstract/base classes:
	
 * `WordPress_Object` - this is the base abstract class that holds common properties and general methods for data manipulation and operation. All objects inherit this class.
 * `WordPress_Object_With_Metadata` - this abstract class extends `WordPress_Object` to provide additional methods and properties to manipulate object metadata, for those that support it. (Trait candidate)

With these, one can begin to construct the actual WordPress classes. Any class methods or properties defined beyond this point should be unique to that object (i.e. don't repeat yourself - at all).

#### `WordPress_Post_Object`

This class inherits the `WordPress_Object_With_Metadata` because, as you likely guessed, posts have metadata.

Each `WordPress_Post_Object` instance represents a single post, _regardless of its post-type_ (i.e. there will not be a `WordPress_Page_Object` or similar).

The methods defined in this class are specific to posts, but not all "post-specific" functionality must be defined (read on).


## Magic

The classes make heavy use of magic methods, including `__isset()`, `__get()`, `__set()`, and `__call()`. Child classes can override these methods to provide additional functionality (in fact, this is an essential part of the structure).

The `__call()` method, in particular, does some quite magical things:

 * Allows you to call non-existant methods: `get_{$property}` (get), `has_{$property}` (isset), `set_{$property}` (set), and `the_{$property}` (echo/print).
 * Allows you to call non-existant methods of non-existant properties: e.g. `$post->the_title()` will print the object's `$post_title`.
 * Filters return values using object-specific filters. This means `$user->get_name()` will apply a different filter than `$post->get_name()`, even though neither object has a `get_name()` method.


## Examples

Some things are easier shown than said. All examples below will use the only currently available class, `WordPress_Post_Object`.

#### Example 1: Getting an Object from ID

```php
$post = x_wp_get_object( 'post', $post_id );
```

The function above will call `WordPress_Object_Factory::get_object( 'post', $post_id )` and return a `WordPress_Post_Object` instance.

We can can also call `x_wp_get_post_object( $post_id )` for the same result.

#### Example 2: Accessing Object Properties

Using the inherited magic methods, we can access object properties like so:

```php
// Does object have a post_title property set?
$post->has_post_title();

// Get object's post_title
$post->get_post_title();

// Set object's post_title (does not save to DB)
$post->set_post_title('Something');

// Echo object's post_title
$post->the_post_title();
```

We can also use the methods inherited from the `WordPress_Object_With_Metadata` class:

```php
// Get metadata
$post->get_meta( 'metakey' );

// Add/update metadata (saved to DB)
$post->update_meta( 'metakey', 'value' );

// Delete metadata (saved to DB)
$post->delete_meta( 'metakey', 'value' );

```

When the `get_meta()` or `update_meta()` methods are called, the result is added to the object so subsequent calls for the same data skip the database query.


