<?php
/**
 * Represent each {@link Item} in the {@link Order} on the {@link Product} {@link AddToCartForm}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
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
		
    if ($quantity == null || !is_numeric($quantity) || $quantity <= 0) {
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
	  
	  return $valid;
	}
	
}