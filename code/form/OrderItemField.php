<?php
/**
 * Represent each {@link Item} in the {@link Order} on the {@link OrderForm}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class OrderItemField extends FormField {

	/**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "OrderItemField";
	
	/**
	 * Current {@link Item} this field represents.
	 * 
	 * @var Item
	 */
	protected $item;
	
	/**
	 * Construct the form field and set the {@link Item} it represents.
	 * 
	 * @param Item $item
	 * @param Form $form
	 */
  function __construct($item, $form = null){

		$this->item = $item;
		$name = 'OrderItem' . $item->ID;
		parent::__construct($name, null, '', null, $form);
	}
	
	/**
	 * Render the form field with the correct template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	/**
	 * Retrieve the {@link Item} this field represents.
	 * 
	 * @return Item
	 */
	function Item() {
	  return $this->item;
	}
	
	/**
	 * Set the {@link Item} this field represents.
	 * 
	 * @param Item $item
	 */
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
	/**
	 * Validate this form field, make sure the {@link Item} exists, is in the current 
	 * {@link Order} and the item is valid for adding to the cart.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
	function validate($validator) {

	  $valid = true;
	  $item = $this->Item();
	  $currentOrder = CartControllerExtension::get_current_order();
		$items = $currentOrder->Items();
		
	  //Check that item exists and is in the current order
	  if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
	    
	    $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Order.');
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
	  else if ($item) {
	    
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
	  
	  return $valid;
	}
}