<?php
/**
 * An Item for an {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Item extends DataObject {

	private $previousQuantity = 0;

  /**
   * DB fields for an Item, the object this Item represents (e.g. {@link Product}
   * has a version ID saved as well, so if price is changed or something then 
   * a record of the price at time of ordering exists and can be retrieved.
   * 
   * @var Array
   */
	public static $db = array(
	  'Price' => 'Decimal(19,4)',
	  'Quantity' => 'Int',
	  'ProductVersion' => 'Int',
	  'VariationVersion' => 'Int'
	);

	public function Amount() {

		// TODO: Multi currency

		$order = $this->Order();

    $amount = new Price();
    $amount->setAmount($this->Price);
    $amount->setCurrency($order->BaseCurrency);
    $amount->setSymbol($order->BaseCurrencySymbol);
    return $amount;
  }

  /**
   * Display price, can decorate for multiple currency etc.
   * 
   * @return Price
   */
  public function Price() {
    
    $amount = $this->Amount();

    //Transform price here for display in different currencies etc.
    $this->extend('updatePrice', $amount);

    return $amount;
  }

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Order' => 'Order',
		'Product' => 'Product',
		'Variation' => 'Variation'
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
	  'Quantity' => 1
	);
	
	/**
	 * Find item options and delete them to clean up DB.
	 * 
	 * @see DataObject::onBeforeDelete()
	 */
	public function onBeforeDelete() {
	  parent::onBeforeDelete();

	  if (ShopConfig::current_shop_config()->StockManagement == 'strict') {
	  	$this->previousQuantity = $this->Quantity;
		  $this->Quantity = 0;
		  $this->updateStockLevels();
	  }
	  
	  $itemOptions = DataObject::get('ItemOption', 'ItemID = '.$this->ID);
	  if ($itemOptions && $itemOptions->exists()) foreach ($itemOptions as $itemOption) {
	    $itemOption->delete();
	    $itemOption->destroy();
	  }
	}

	public function UnitAmount() {

		$itemAmount = $this->Amount();

		$amount = $itemAmount->getAmount();

	  foreach ($this->ItemOptions() as $itemOption) {
	    $amount += $itemOption->Amount()->getAmount();
	  } 

	  $unitAmount = clone $itemAmount;
	  $unitAmount->setAmount($amount);
	  return $unitAmount;
	}
	
	/**
	 * Get unit price for this Item including price of any {@link ItemOption}s.
	 * 
	 * @return Money Item price inclusive of item options prices
	 */
	public function UnitPrice() {

		$itemPrice = $this->Price();
	  $amount = $itemPrice->getAmount();

	  foreach ($this->ItemOptions() as $itemOption) {
	    $amount += $itemOption->Price()->getAmount();
	  } 

	  // TODO: Multi currency

	  $unitPrice = clone $itemPrice;
	  $unitPrice->setAmount($amount);
	  return $unitPrice;
	}
	
	/**
	 * Get unit price for this item including item options price and quantity.
	 * 
	 * @return Price Item total inclusive of item options prices and quantity
	 */
	public function Total() {

		$unitAmount = $this->UnitAmount();
		$unitAmount->setAmount($unitAmount->getAmount() * $this->Quantity);
		return $unitAmount;
	}

	public function TotalPrice() {

		$unitPrice = $this->UnitPrice();
		$unitPrice->setAmount($unitPrice->getAmount() * $this->Quantity);
		return $unitPrice;
	}

	/**
	 * Get the variation for the item if a Variation exists in the ItemOptions
	 * This assumes only one variation per item.
	 * 
	 * @return Mixed Variation if it exists, otherwise null
	 */
	function Variation() {
		return ($this->VariationID) ? Versioned::get_version('Variation', $this->VariationID, $this->VariationVersion) : null;
	}
	
	/**
	 * Get the product for the item
	 * 
	 * @return Mixed Product if it exists, otherwise null
	 */
	function Product() {
		return Versioned::get_version('Product', $this->ProductID, $this->ProductVersion);
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
	 * @see DataObject::validate()
	 * @return ValidationResult
	 */
	function validate() {

	  $result = new ValidationResult(); 
	  
	  $product = $this->Product();
	  $variation = $this->Variation();
	  $quantity = $this->Quantity;

	  //Check that product is published and exists
	  if (!$product || !$product->exists() || !$product->isPublished()) {
	    $result->error(
	      'Sorry this product is no longer available',
	      'ProductExistsError'
	    );
	  }

	  //Check that variation exists if required, not on first write when ItemOption hasn't had a chance to be written
	  if ($product && $product->requiresVariation() && (!$variation || !$variation->validateForCart()->valid())) {
      $result->error(
	      'Sorry, these product options are no longer available.',
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
	
	/**
	 * Update the quantity of the item. 
	 * previousQuantity starts at 0.
	 * 
	 * @see DataObject::onBeforeWrite()
	 */
  function onBeforeWrite() {
    parent::onBeforeWrite();

    //previousQuantity starts at 0
    if ($this->isChanged('Quantity')) {
  		if(isset($this->original['Quantity'])) {
  			$this->previousQuantity = $this->original['Quantity'];
  		}
    }
  }
	
  /**
   * Update stock levels for {@link Item}.
   * 
   * @see DataObject::onAfterWrite()
   */
	public function onAfterWrite() {
	  parent::onAfterWrite();
	  if (ShopConfig::current_shop_config()->StockManagement == 'strict') $this->updateStockLevels();
	}
	
	/**
	 * Update {@link StockLevel} for {@link Product} - or {@link Variation} if it
	 * exists. 
	 * 
	 * @see Item::onBeforeDelete()
	 * @see Item::onAfterWrite()
	 */
	public function updateStockLevels() {

		$quantityChange = $this->previousQuantity - $this->Quantity;

	  if ($variation = $this->Variation()) {
	    $variation->updateStockBy($quantityChange);
	  }
	  else if ($product = $this->Product()) {
	    if (!$product->requiresVariation()) $product->updateStockBy($quantityChange);
	  }
	}

	public function SummaryOfOptions() {
		$summary = '';

		$options = array();
		if ($variation = $this->Variation()) $options[] = $variation->SummaryOfOptions();

		foreach ($this->ItemOptions()->column('Description') as $description) {
			$options[] = $description;
		}

		$summary .= implode(', ', $options);
		return $summary;
	}
}