<?php
class OrderItemField extends FormField {

	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "OrderItemField";
	
	protected $item;
	
  function __construct($item, $form = null){

		$this->item = $item;
		$name = 'OrderItem' . $item->ID;
		parent::__construct($name, null, '', null, $form);
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

	    /*
	    //These checks are very similar to Item->validate()
  	  $product = $item->Object();
  	  $variation = $item->Variation();
  	  $quantity = $item->Quantity;
  	  
  	  //Check that product is published and exists
  	  if (!$product || !$product->exists() || !$product->isPublished()) {
  	    
    	  $errorMessage = _t('Form.ITEM_IS_NOT_VALID', 'Sorry, this product is no longer available.');
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
  	  
  	  //Check that product variation exists if it is required
  	  if ($product && $product->requiresVariation() && (!$variation || !$variation->isValid())) {
  	    
        $errorMessage = _t('Form.ITEM_IS_NOT_VALID', 'Sorry, this product is no longer available.');
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
  	  
  	  //Check that quantity is greater than 0
  	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
  	    $errorMessage = _t('Form.ITEM_IS_NOT_VALID', 'Quantity of this product should be greater than zero.');
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
  	  */
	  }
	  
	  return $valid;
	}
}