<?php
/** @TODO */
class WP_XXX_Object extends WP_DB_Object /* or WP_Global_Object
	implements ... */ 
{
	
	/** @TODO
	*
	* @var string $_type 
	* Lowercase string representing the object type.
	*/
	protected $_type;
	
	/** @TODO
	*
	* @var string $_uid_property
	* The identifier used to query objects of this type.
	*/
	protected $_uid_property;
	
	/** @TODO (optional) 
	*
	* Additional properties
	*/
	
	
	/* ================================
			get_instance_data() 
	================================ */
	
	/** @TODO
	*
	* Returns array of object data - imported as properties.
	*/
	static public function get_instance_data( $id ){}
	
	
	/** @TODO (optional)
	*
	* Allows classes to add actions and do other setup processes. 
	* Called at start of __construct()
	*/
	protected function objectInit(){}
	
	
	/** @TODO if class extends WP_DB_Object: 
	
	/* ========================================		
			WP_DB_Object abstract methods
	======================================== */
	/*	
	public function get_update_fields(){}
	
	public function update(){}
	
	public function insert(){}
	
	public function delete( $force = false ){}
	
	public function update_var( $key ){}
	
	*/
	
	
	/* ====================================
			WP_XXX_Interface 
	==================================== */
	
	
	
	/* ============================
			Custom methods
	============================= */
		
}