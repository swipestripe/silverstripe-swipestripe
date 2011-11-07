<?php
class QuantityField extends TextField {
	
  function validate($validator) {

	  $valid = true;
		$quantity = $this->Value();
		
    if ($quantity == null || !is_numeric($quantity) || $quantity <= 0) {
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
	  
	  return $valid;
	}
	
}