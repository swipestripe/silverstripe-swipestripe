<?php
/**
 * Represent each {@link Item} in the {@link Order} on the {@link Product} {@link AddToCartForm}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class QuantityField extends TextField {
	
  /**
   * Validate the quantity is above 0.
   * 
   * @see FormField::validate()
   * @return Boolean
   */
  function validate($validator) {

	  $valid = true;
		$quantity = $this->Value();
		
    if ($quantity == null || !is_numeric($quantity)) {
	    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be a number');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
	    $valid = false;
	  }
	  else if ($quantity <= 0) {
	    $errorMessage = _t('Form.ITEM_QUANTITY_LESS_ONE', 'The quantity must be at least 1');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
	    $valid = false;
	  }
	  else if ($quantity > 2147483647) {
	    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be less than 2,147,483,647');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
	    $valid = false;
	  }


	  return $valid;
	}
	
}