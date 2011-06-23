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
	    
	    //If order is not paid do not provide access to download
	    $order = $this->Order();
	    if (!$order->getPaid()) {
	      return false;
	    }
	  
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
	
	/**
	 * Calculate remaining number of downloads for this item
	 * 
	 * @return Int
	 */
	function RemainingDownloadLimit() {
	  
	  SS_Log::log(new Exception(print_r($this->getDownloadLimit() - $this->DownloadCount, true)), SS_Log::NOTICE);
	  
	  return $this->getDownloadLimit() - $this->DownloadCount;
	}

}