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
	  
	  //TODO need to check that item is correct in here maybe
	  
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