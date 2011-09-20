<?php

class Item extends DataObject {

	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
		'ObjectVersion' => 'Int',
	  'Amount' => 'Money',
	  'Quantity' => 'Int',
	  'DownloadCount' => 'Int' //If item represents a downloadable product,
	);

	public static $has_one = array(
		'Order' => 'Order'
	);
	
	public static $has_many = array(
	  'ItemOptions' => 'ItemOption'
	);
	
	public static $defaults = array(
	  'Quantity' => 1,
	  'DownloadCount' => 0
	);
	
	/**
	 * Retrieve the object this item represents (Product)
	 * TODO serialize product object data and save in item row
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  //return DataObject::get_by_id($this->ObjectClass, $this->ObjectID);
	  return Versioned::get_version($this->ObjectClass, $this->ObjectID, $this->ObjectVersion);
	}
	
	/**
	 * Find item options and delete them
	 * 
	 * @see DataObject::onBeforeDelete()
	 */
	public function onBeforeDelete() {
	  parent::onBeforeDelete();
	  
	  $itemOptions = DataObject::get('ItemOption', 'ItemID = '.$this->ID);
	  if ($itemOptions && $itemOptions->exists()) foreach ($itemOptions as $itemOption) {
	    $itemOption->delete();
	  } 
	}
	
	/**
	 * Get unit price for this item including item options price
	 * 
	 * @return Money
	 */
	public function UnitPrice() {

	  $amount = $this->Amount->getAmount();
	  foreach ($this->ItemOptions() as $itemOption) {
	    $amount += $itemOption->Amount->getAmount();
	  } 
	  
	  $unitPrice = new Money();
	  $unitPrice->setAmount($amount);
	  $unitPrice->setCurrency($this->Amount->getCurrency());
	  return $unitPrice;
	}
	
	/**
	 * Get unit price for this item including item options price and 
	 * quantity
	 * 
	 * @return Money
	 */
	public function Total() {
	  
	  $amount = $this->Amount->getAmount();
	  foreach ($this->ItemOptions() as $itemOption) {
	    $amount += $itemOption->Amount->getAmount();
	  } 
	  $amount = $amount * $this->Quantity;
	  
	  $subTotal = new Money();
	  $subTotal->setAmount($amount);
	  $subTotal->setCurrency($this->Amount->getCurrency());
	  return $subTotal;
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
	  return $this->getDownloadLimit() - $this->DownloadCount;
	}

}