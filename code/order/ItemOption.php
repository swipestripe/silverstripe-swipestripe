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
	  'Amount' => 'Money'
	);

	public static $has_one = array(
	  'Item' => 'Item'
	);
	
	public static $defaults = array(
	);
	
	/**
	 * Retrieve the object this item represents (Product)
	 * TODO serialize product object data and save in item row
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  return Dataobject::get_by_id($this->ObjectClass, $this->ObjectID);
	}

}