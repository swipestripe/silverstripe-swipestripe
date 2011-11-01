<?php
/**
 * 
 * Item options for items in the order
 * 
 * @author frankmullenger
 *
 */
class ItemOption extends DataObject {

	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
	  'ObjectVersion' => 'Int',
	  'Amount' => 'Money'
	);

	public static $has_one = array(
	  'Item' => 'Item'
	);
	
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Retrieve the object this item represents (Variation)
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  //return DataObject::get_by_id($this->ObjectClass, $this->ObjectID);
	  return Versioned::get_version($this->ObjectClass, $this->ObjectID, $this->ObjectVersion);
	}
	
	/**
	 * TODO validate before write()
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}
}
