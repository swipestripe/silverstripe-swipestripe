<?php

class Item extends DataObject {

	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
	  'Amount' => 'Money',
	  'Quantity' => 'Int'
	);

	public static $has_one = array(
		'Order' => 'Order'
	);
	
	public static $defaults = array(
	  'Quantity' => 1
	);
	
	/**
	 * Retrieve the object this item represents (Product)
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  return Dataobject::get_by_id($this->ObjectClass, $this->ObjectID);
	}

}