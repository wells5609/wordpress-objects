wordpress-objects
=================

A prototype project for object-oriented WordPress data types (i.e. posts, users, terms, etc).


### Project Overview

This project aims to provide a consistent and semantic base structure that all WP objects can build upon.

##### Goals:
 
 * minimize redundancy and maximize consistency among objects
 * allow developers to customize objects
 * retain current API functionality
 * enable simple transition to the use of traits, once supported.


## Core Classes

#### `WordPress_Object_Factory`

Objects are created, stored in, and returned via this "factory" object. This means the `new` keyword will not be used to instantiate a WordPress object (except in special cases).

The factory object takes care of mapping object types to the desired class as well as instantiation.

##### Methods
 * `get_object( $object_type, $object_id [, $var = null ] )`
 * `set_object( $object_type, $object_id [, $var = null ] )`


####`abstract WordPress_Object`

The base abstract class; defines core methods used mainly for internal use (not object-specific). All data objects inherit this class.

#####Methods _also see "Magic Methods" below_
 * `get( $var )` - gets a property value
 * `set( $var, $val )` - sets a property value
 * `get_id()` - returns object's identifier
 * `import( array $data )` - imports array of data as properties
 * `get_object_type()` - returns object's `$objectType` property
 * `get_keys()` - returns array of object keys
 * `get_aliases()` - returns assoc. array of alias/key pairs
 * `is_key( $var )`
 * `is_alias( $var )`
 * `get_aliased_key( $var )` - returns key if passed an alias
 * `translate_key( $var )` - returns key if passed a key or alias, otherwise null.
 * `call( $func, $args = array() )` - calls `$this->$func()` using $args as parameters


#### `abstract WordPress_Object_With_Metadata`

**Extends:** `WordPress_Object` 

Adds methods and properties to manipulate object metadata. (trait candidate)

With the classes above, one can begin to construct the actual WP object classes. As a general rule, class methods or properties defined beyond this "point" should be unique to that object.


## Magic Methods

Object classes make use of magic methods, including `__isset()`, `__get()`, `__set()`, and `__call()`. Child classes can override these methods to provide additional functionality (this is actually an essential part of the structure).

The `__call()` method, in particular, is quite important:

 * Allows you to call _non-existant methods_:
 	* `get_{$property}` (get), 
 	* `has_{$property}` (isset), 
 	* `set_{$property}` (set), and 
 	* `the_{$property}` (echo/print).
 * Allows you to call _non-existant methods_ of _non-existant properties_ through the use of **"key aliases"**: 
 	* e.g. `$post->the_title()` will print the object's `$post_title`
 	* e.g. `$post->get_author()` will return the object's `$post_author`
 * Filters return values using object-specific filters. 
 	* For example, calling `->get_name()` on a `WordPress_User_Object` will apply a different filter than calling `->get_name()` on `WordPress_Post_Object`, even though neither object has defined a `get_name()` method. Note we could also call `->get_post_name()` on `WordPress_Post_Object` for the same result.


## Object Classes

#### `WordPress_Post_Object`

This class inherits the `WordPress_Object_With_Metadata` class because posts have metadata.

Each `WordPress_Post_Object` instance represents a single post, _regardless of its post-type_. Users can create custom classes for post objects meeting certain conditions; these will extend the `WordPress_Post_Object` class.

The methods defined in this class are specific to posts, but not all "post-specific" functionality must be defined (more on that below).


## Key Aliases

Sometimes we want to access an object property using a different name (key) than that which is defined by the schema.

For example, WordPress accesses post titles (i.e. `$post_title` property of Post objects) using `get_the_title()` and `the_title()` functions. This creates an issue for magically mapped methods, since `title` and `the_title` are not object properties.

This is solved using key aliases. In the case above, we can set a key alias `title` for `post_title` - this will tell the magic methods to use the `$post_title` property for calls to `*_title()` (e.g. `->has_title()`, `->get_title()`, etc.).

Furthermore, we can override the default magic methods by defining a method using the _key_ name:

```php
// in WordPress_Post_Object:

public function the_post_title( $before = '', $after = '' ){
	echo $before . $this->get_post_title() . $after;
}

```

Because we've added an alias called `title` for the `post_title` property, both of the methods below will print the same result:

```php
$post->the_title( '<em>', '</em>' );

// is identical to:
$post->the_post_title( '<em>', '</em>' );

```


## Examples

All examples below will use the `WordPress_Post_Object` class.

#### Example 1: Getting an Object from ID

```php
$post = x_wp_get_object( 'post', $post_id );
```

The function above will return a `WordPress_Post_Object` instance by calling:
```php
WordPress_Object_Factory::get_object( 'post', $post_id );
```

We could also call `x_wp_get_post_object( $post_id )` for the same result.


#### Example 2: Accessing Object Properties

Using the magic `__call()` method, we can access object properties like so:

```php
// Is $post_title property set?
$post->has_post_title(); // or $post->has_title();

// Return filtered value of $post_title
$post->get_post_title(); // or $post->get_title();

// Set parameter $post_title (not saved to DB)
$post->set_post_title('Something'); // or $post->set_title('Something');

// Print filtered $post_title value filtered for output
$post->the_post_title(); // or $post->the_title();
```

We can also use the methods inherited from the `WordPress_Object_With_Metadata` class:

```php
// Get metadata entry
$post->get_meta( 'metakey' );

// Get all metadata
$post->get_meta();

// Add/update metadata (saved to DB)
$post->update_meta( 'metakey', 'value' );

// Delete metadata (saved to DB)
$post->delete_meta( 'metakey', 'value' );

```

When the `get_meta()` or `update_meta()` methods are called, the result is added to the object.


