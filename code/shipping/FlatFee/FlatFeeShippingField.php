<?php

class FlatFeeShippingField extends ModifierSetField {
  
	/**
	 * Render field with the appropriate template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder() {
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('shop/javascript/FlatFeeShippingField.js');
		return $this->renderWith($this->template);
	}
	
	function validate($validator){
	  
	  $valid = true;
	  $value = $this->Value();
	  $formData = $validator->getForm()->getData();
	  $flatFeeShippingCountries = DataObject::get('FLatFeeShippingCountry');
	  $shippingAddressCountry = (isset($formData['Shipping[Country]'])) ? $formData['Shipping[Country]'] : null;

	  //If the value is not in the set of shipping countries, error
	  //If the shipping country does not match the current shipping country, error

	  if (!$flatFeeShippingCountries || !$flatFeeShippingCountries->exists()) {
	    
	    $errorMessage = _t('Form.FLAT_FEE_SHIPPING_NOT_EXISTS', 'This shipping option is no longer available sorry');
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
	  
	  if (!$shippingAddressCountry) {
	    
	    $errorMessage = _t('Form.SHIPPING_ADDRESS_COUNTRY_NOT_EXISTS', 'Please select a country for the shipping address');
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
	  
	  $shippingOption = $flatFeeShippingCountries->find('ID', $value);
	  if (!$shippingOption || !$shippingOption->exists()) {
	    
	    $errorMessage = _t('Form.FLAT_FEE_SHIPPING_OPTION_NOT_EXISTS', 'This shipping option is no longer available sorry');
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
	  else if ($shippingOption) {

	    if ($shippingAddressCountry != $shippingOption->CountryCode) {
	      
	      $errorMessage = _t('Form.FLAT_FEE_SHIPPING_COUNTRY_NOT_MATCH', 'This shipping option is no longer available for the shipping country you have selected sorry');
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