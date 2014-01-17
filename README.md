wordpress-objects
=================

A prototype project for object-oriented WordPress data types (i.e. posts, users, terms, etc).


### Project Overview

This project aims to provide a consistent and semantic base structure for all (core) WP objects. It is intended to be a replacement for, not an addition to, the existing object architecture.

#### Primary Goals:
 
 * minimize redundancy and maximize consistency among objects
 * allow developers to customize objects
 * retain current API functionality
 * enable simple transition to the use of traits, once supported.

##### Update 1/11/14
**Began core integration** - began integrating into core starting with `WP_Post` (currently `WP_Post_Object` to avoid clashes); surprisingly easy; little performance loss (especially considering code now duplicated/unused is still intact - likely overall gain). Only error is a notice on some pages in wp-admin.


## Core Classes

#### `WP_Object_Factory`

Objects are created and returned via this "factory" object. This means the `new` keyword will not be used to instantiate a WordPress object except in special cases.

The factory object takes care of mapping object types to the desired class as well as instantiation.


####`abstract WP_Object`

The base abstract class; defines core methods used mainly for internal use (not object-specific). All data objects inherit this class.

All methods are public unless noted otherwise.

#####Methods
 * `get_fields()` (abstract) - returns indexed array of object fields (defined by other abstract objects)
 * `__construct( $data )` (final) - imports array of data as properties, calling `import()`.
 * `get( $var )` (final) - returns a property value. calls magic `__get()`
 * `set( $var, $val )` (final) - sets a property value. calls magic `__set()`
 * `exists( $var )` (final) - returns true if property exists. calls magic `__isset()`
 * `import( $data )` (final) - imports array of data as properties
 * `get_object_type()` (final) - returns object type (e.g. 'post') 
 * `get_identifier()` (final) - returns the identifier for this object type (e.g. 'ID')
 * `get_id()` (final) - returns this object's identifier (e.g. '123')
 * `call( $func, $args = array(), $check_callable = false )` (final) - calls `$this->$func()` using $args as parameters
 * `do_action( $fn, $context, $args = array()` - used for internal object hooks (see examples)
 * `add_action( $fn_context, $callback, $priority = 10, $num_args = 1 )` - used for internal object hooks (see examples)
 * `catch_return_bool( $r )` (final) - if $r is WP_Error, returns false and adds object to property for later debugging. Otherwise returns true.
 * `objectInit()` (protected) - allows child classes to add hooks and do other setup processes.


#### `abstract WP_DB_Object`

**Extends:** `WP_Object` 

Defines the `get_fields()` method and adds additional abstract methods to manipulate data that resides in the database. This class is used by posts, terms, and users (eventually comments, links, etc.).

#####Methods
 * `get_fields()` (final) - returns fields for this object.
 * `update()` (abstract) - updates object data in database using current properties.
 * `insert()` (abstract) - inserts object data into database using current properties (used with `new` keyword).
 * `delete( $force = false )` (abstract) - deletes object data from database.
 * `update_var()` (abstract) - updates a single object field in the database using current property.
 * `get_update_fields()` (abstract) - returns an indexed array of fields to be used in `update()` and related methods.
 * `is_update_field( $field )` - returns true if $field is in array returned by `get_update_fields()` (classes can overwrite).


#### `abstract WP_Global_Object`

**Extends:** `WP_Object`

Defines the `get_fields()` method. Used by taxonomies and post-types (or anything else that is "global" in scope but not kept in the database).


#### `abstract WP_DB_Object_With_Taxonomies`

**Extends:** `WP_DB_Object`

Adds ~20 methods for dealing with the object's taxonomies and terms.



With the classes above, one can begin to construct the actual WP object classes. As a general rule, class methods or properties defined beyond this "point" should be unique to that object.


## Examples

All examples below will use the `WP_Post_Object` class.

#### Example 1: Getting an object from ID

```php
$post = wp_get_object( 'post', $post_id );
```

The function above will return a `WP_Post_Object` instance by calling:
```php
WP_Object_Factory::get_object( 'post', $post_id );
```

We could also call `wp_get_post_object( $post_id )` for the same result.

