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
	
	/**
	 * Return the link that should be used for downloading the 
	 * virtual product represented by this item.
	 * 
	 * @return Mixed URL to download or false
	 */
	function DownloadLink() {

	  if ($this->DownloadCount < $this->getDownloadLimit()) {
	  
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
	
	/**
	 * Number of times this item can be downloaded for this order
	 * 
	 * @return Int
	 */
	function getDownloadLimit() {
	  return VirutalProductDecorator::$downloadLimit * $this->Quantity;
	}
	
	function RemainingDownloadLimit() {
	  return $this->getDownloadLimit() - $this->DownloadCount;
	}

}