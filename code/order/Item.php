<?php
/**
 * An Item for an {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 * @version 1.0
 */
class Item extends DataObject {

  /**
   * DB fields for an Item, the object this Item represents (e.g. {@link Product}
   * has a version ID saved as well, so if price is changed or something then 
   * a record of the price at time of ordering exists and can be retrieved.
   * 
   * @var Array
   */
	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
		'ObjectVersion' => 'Int',
	  'Amount' => 'Money',
	  'PreviousQuantity' => 'Int',
	  'Quantity' => 'Int',
	  'DownloadCount' => 'Int' //If item represents a downloadable product,
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Order' => 'Order'
	);
	
	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_many = array(
	  'ItemOptions' => 'ItemOption'
	);
	
	/**
	 * Default values for this class
	 * 
	 * @var Array
	 */
	public static $defaults = array(
	  'PreviousQuantity' => 0,
	  'Quantity' => 1,
	  'DownloadCount' => 0
	);
	
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Retrieve the object this item represents (e.g. {@link Product}). Uses versioning
	 * so that the product that was bought can be retrieved with all the correct details.
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  return Versioned::get_version($this->ObjectClass, $this->ObjectID, $this->ObjectVersion);
	}
	
	/**
	 * Find item options and delete them to clean up DB.
	 * 
	 * @see DataObject::onBeforeDelete()
	 */
	public function onBeforeDelete() {
	  parent::onBeforeDelete();

	  $this->PreviousQuantity = $this->Quantity;
	  $this->Quantity = 0;
	  $this->updateStockLevels();
	  
	  $itemOptions = DataObject::get('ItemOption', 'ItemID = '.$this->ID);
	  if ($itemOptions && $itemOptions->exists()) foreach ($itemOptions as $itemOption) {
	    $itemOption->delete();
	    $itemOption->destroy();
	  }
	}
	
	/**
	 * Get unit price for this Item including price or any {@link ItemOption}s.
	 * 
	 * @return Money Item price inclusive of item options prices
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
	 * Get unit price for this item including item options price and quantity.
	 * 
	 * @return Money Item total inclusive of item options prices and quantity
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
	 * Get the variation for the item if a Variation exists in the ItemOptions
	 * This assumes only one variation per item.
	 * 
	 * @return Mixed Variation if it exists, otherwise null
	 */
	function Variation() {
	  $itemOptions = $this->ItemOptions();
	  $variation = null;
	  
	  if ($itemOptions && $itemOptions->exists()) foreach ($itemOptions as $itemOption) {
	    
	    if ($itemOption->ObjectClass == 'Variation') {
	      $variation = $itemOption->Object();
	    }
	  } 
	  return $variation;
	}
	
	/**
	 * Get the product for the item
	 * 
	 * @return Mixed Product if it exists, otherwise null
	 */
	function Product() {
	  
	  $product = $this->Object();
	  if ($product && $product->exists() && $product instanceof Product) {
	    return $product;
	  }
	  return null;
	}
	
	/**
	 * Validate this Item to make sure it can be added to a cart.
	 * 
	 * @return ValidationResult
	 */
	function validateForCart() {
	  return $this->validate();
	}
	
	/**
	 * Validate that product exists and is published, variation exists for product if necessary
	 * and quantity is greater than 0
	 * 
	 * TODO remove the check for $firstWrite when transactions are implemented
	 * 
	 * @see DataObject::validate()
	 * @return ValidationResult
	 */
	function validate() {

	  $result = new ValidationResult(); 
	  $firstWrite = !$this->ID;
	  
	  $product = $this->Object();
	  $variation = $this->Variation();
	  $quantity = $this->Quantity;
	  
	  //Check that product is published and exists
	  if (!$product || !$product->exists() || !$product->isPublished()) {
	    $result->error(
	      'Sorry this product is no longer available',
	      'ProductExistsError'
	    );
	  }
	  
	  //TODO need to change checks for variation so that variation is checked properly when transactions are implemented
	  //Check that variation exists if required, not on first write when ItemOption hasn't had a chance to be written
	  if ($product && $product->requiresVariation() && (!$variation || !$variation->validateForCart()->valid()) && !$firstWrite) {
      $result->error(
	      'Sorry, these product options are no longer available',
	      'VariationExistsError'
	    );
	  }
	  //If a variation does exist, check that it is valid
	  else if ($variation && !$variation->validateForCart()->valid()) {
	    $result->error(
	      'Sorry, these product options are no longer available',
	      'VariationIncorrectError'
	    );
	  }
	  
	  //Check that quantity is correct
	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
	    $result->error(
	      'Quantity for this product needs to be greater than 0',
	      'QuantityError'
	    );
	  }

	  return $result;
	}
	
  function onBeforeWrite() {
    parent::onBeforeWrite();

    //PreviousQuantity starts at 0
    if ($this->isChanged('Quantity')) {
  		if(isset($this->original['Quantity'])) {
  			$this->PreviousQuantity = $this->original['Quantity'];
  		}
    }
  }
	
	public function onAfterWrite() {
	  parent::onAfterWrite();
	  $this->updateStockLevels();
	}
	
	public function updateStockLevels() {
	  //Get variation, update stock level
	  //If no variation get the product and update the stock level
	  //Keep in mind calling this from onAfterDelete() as well

	  $quantityChange = $this->PreviousQuantity - $this->Quantity;

	  if ($variation = $this->Variation()) {
	    $variation->updateStockBy($quantityChange);
	  }
	  else if ($product = $this->Product()) {
	    if (!$product->requiresVariation()) $product->updateStockBy($quantityChange);
	  }
	}
}