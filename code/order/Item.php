<?php

class Item extends DataObject {

	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
	  'Amount' => 'Money',
	  'Quantity' => 'Int',
	  'DownloadCount' => 'Int' //If item represents a downloadable product
	);

	public static $has_one = array(
		'Order' => 'Order'
	);
	
	public static $defaults = array(
	  'Quantity' => 1,
	  'DownloadCount' => 0
	);
	
	/**
	 * Retrieve the object this item represents (Product)
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  return Dataobject::get_by_id($this->ObjectClass, $this->ObjectID);
	}
	
	function DownloadLink() {
	  
	  //TODO better method of calculating download count thresholds
	  if ($this->DownloadCount < 3) {
	  
  	  if ($accountPage = DataObject::get_one('AccountPage')) {
  	    return $accountPage->Link() . 'downloadproduct/?ItemID='.$this->ID;
  	  }
  	  else {
  	    return false;
  	  }
	  
	  }
	  else {
	    return false;
	  }
	}

}