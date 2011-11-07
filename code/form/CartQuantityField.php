<?php
class CartQuantityField extends TextField {

	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "CartQuantityField";
	
	protected $item;
	
  function __construct($name, $title = null, $value = "", $maxLength = null, $form = null, $item = null){

		$this->item = $item;
		parent::__construct($name, $title, $value, $maxLength, $form);
	}
	
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	function Item() {
	  return $this->item;
	}
	
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
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
	    
  	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
  	    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be at least zero (0).');
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
	  
	  return $valid;
	}
	
}