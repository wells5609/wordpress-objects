<?php

class WordPress_XXX_Object extends WordPress_Object/*_With_Meta implements ... */ {
	
	/**
	* @var string $objectName
	* 
	* Lowercase name/slug/type of the object.
	*/
	protected $objectName;			// @TODO
	
	/**
	* @var string $primaryKey
	* 
	* The primary key used to identify objects of this type.
	*/
	protected $primaryKey;			// @TODO	
	
	/**
	* @TODO if class extends WordPress_Object_With_Metadata
	*/
	//protected $metaType = 'post';
	
	
	// overrides *_metadata() functions
/*	protected $callbacks = array(
		'get_meta'		=> 'get_post_meta',
		'update_meta'	=> 'update_post_meta',
		'delete_meta'	=> 'delete_post_meta',
	);
*/

	
	/* ======== get_instance_data() ======== */
	
	/**
	* Returns array of object data - imported as properties.
	*/
	static public function get_instance_data( $id ){	// @TODO
	}
	
	
	/**
	* Called at start of __construct()
	*/
	protected function preConstruct(&$data){}			// Optional
	
	/**
	* Called at end of __construct()
	*/
	protected function onConstruct(){} 					// Optional
	
	
	/* ======================================================== 
		Interface XXX implementation 
	========================================================= */
	
	
	/* ============================
		(Magic) Method Overrides 
	============================= */
	
	
	/* ============================
			Custom methods
	============================= */
	
		
	/* =============================
				Filters 
	============================== */
	
	/**
	* Filters a property value.
	*/
	protected function filterValue( $key, $value ){
		
		switch($key){
			
			default: return $value;	
		}	
	}
	
	/**
	* Filters a property value for output.
	*/
	protected function filterOutput( $key, $value ){
		
		switch($key){
			
			default: return $value;	
		}
	}
	
	
	
}