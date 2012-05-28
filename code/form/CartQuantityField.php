<?php
/**
 * Quantity field for displaying each {@link Item} in an {@link Order} on the {@link CartPage}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class CartQuantityField extends TextField {

	/**
	 * Template for rendering the field
	 *
	 * @var String
	 */
	protected $template = "CartQuantityField";
	
	/**
	 * Current {@link Item} represented by this field.
	 * 
	 *  @var Item
	 */
	protected $item;
	
	/**
	 * Construct the field and set the current {@link Item} that this field represents.
	 * 
	 * @param String $name
	 * @param String $title
	 * @param String $value
	 * @param Int $maxLength
	 * @param Form $form
	 * @param Item $item
	 */
  function __construct($name, $title = null, $value = "", $maxLength = null, $form = null, $item = null){

		$this->item = $item;
		parent::__construct($name, $title, $value, $maxLength, $form);
	}
	
	/**
	 * Render the field with the appropriate template.
	 * 
	 * @see FormField::FieldHolder()
	 */
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	/**
	 * Retrieve the current {@link Item} this field represents. Used in the template.
	 * 
	 * @return Item
	 */
	function Item() {
	  return $this->item;
	}
	
	/**
	 * Set the current {@link Item} this field represents
	 * 
	 * @param Item $item
	 */
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
	/**
	 * Validate this field, check that the current {@link Item} is in the current 
	 * {@Link Order} and is valid for adding to the cart.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
  function validate($validator) {

	  $valid = true;
	  $item = $this->Item();
    $currentOrder = CartControllerExtension::get_current_order();
		$items = $currentOrder->Items();
		$quantity = $this->Value();

		$removingItem = false;
		if ($quantity == 0) {
		  $removingItem = true;
		}
		
	  //Check that item exists and is in the current order
	  if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
	    
	    $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Cart.');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
	    
	    $validator->validationError(
				$this->Name(),
				$errorMessage,
				"error"
			);
			$valid = false;
	  }
	  else if ($item && !$removingItem) {
	    
	    //If quantity is zero the item is removed already @see CartPage::saveCart()
  	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
  	    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be at least one (1).');
  			if ($msg = $this->getCustomValidationMessage()) {
  				$errorMessage = $msg;
  			}
  			
  			$validator->validationError(
  				$this->Name(),
  				$errorMessage,
  				"error"
  			);
  	    $valid = false;
  	  }

	    $validation = $item->validateForCart();
	    if (!$validation->valid()) {
	      
	      $errorMessage = $validation->message();
  			if ($msg = $this->getCustomValidationMessage()) {
  				$errorMessage = $msg;
  			}
  			
  			$validator->validationError(
  				$this->Name(),
  				$errorMessage,
  				"error"
  			);
  	    $valid = false;
	    }
	  }
	  
	  //Check that quantity for an item is not being pushed beyond available stock levels for a product
	  $quantityChange = $quantity - $item->Quantity;
	  
	  if ($item) {
	    $variation = $item->Variation();
	    $product = $item->Product();
	    $stockLevel = 0;
	    if ($variation) {
	      $stockLevel = $variation->StockLevel()->Level;
	    }
	    else {
	      $stockLevel = $product->StockLevel()->Level;
	    }
	    if ($quantityChange > 0 && $quantityChange > $stockLevel && $stockLevel > -1) {
	      //If the change in quantity is greater than the remaining stock level then there is a problem
	      $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'Quantity is greater than remaining stock.');
  			if ($msg = $this->getCustomValidationMessage()) {
  				$errorMessage = $msg;
  			}
  			
	      $validator->validationError(
  				$this->Name(),
  				$errorMessage,
  				"error"
  			);
  	    $valid = false;
	    }
	  }
	  
	  return $valid;
	}
	
	/**
	 * Get a form action to remove an order item.
	 * 
	 * @return RemoveItemAction Type of FormAction
	 */
	function RemoveItemAction() {
	  return new RemoveItemAction('removeItem', $this->Item()->ID, null, null, 'remove-item-action');
	}
	
}