<?php
/**
 * For adding amount to {@link Variation}s which alter the overall {@link Product} amount,
 * validates that amount is not negative.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class VariationMoneyField extends MoneyField {
  
	/**
	 * Validate this form field, make sure the {@link Variation} amount is not negative.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
	function validate($validator) {

	  $valid = true;
	  $amount = $this->Value();
	  
	  if (!$amount || !isset($amount['Amount'])) {
	    
	    $errorMessage = _t('Form.VARIATION_PRICE_NOT_EXISTS', 'There is a problem with the variation price.');
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
	  else if ($amount['Amount'] < 0) {
	    
	    $errorMessage = _t('Form.VARIATION_PRICE_NEGATIVE', 'This variation price is negative.');
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

