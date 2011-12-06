<?php
/**
 * Form fields that represent {@link FlatFeeShippingRate}s in the Checkout form.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage shipping
 * @version 1.0
 */
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

  /**
   * Update value of the field according to any matching {@link Modification}s in the 
   * {@link Order}. Useful when the source options have changed, if a matching option cannot
   * be found in a Modification then the first option is set at the value (selected).
   * 
   * @param Order $order
   */
  function updateValue($order) {
    
    //Update the field source based on the shipping address in the current order
    $shippingAddress = $order->ShippingAddress();
    $shippingCountry = $shippingAddress->Country;
    
    $shippingOptions = DataObject::get('FlatFeeShippingRate', "CountryCode = '$shippingCountry'");
    
    $optionsMap = array();
    if ($shippingOptions && $shippingOptions->exists()) {
      $optionsMap = $shippingOptions->map('ID', 'Label');
      $this->setSource($optionsMap);
    }
    
    //If the current modifier value is not in the new options, then set to first option
    $modification = DataObject::get_one('Modification', "ModifierClass = 'FlatFeeShipping' AND OrderID = '" . $order->ID . "'");
    $currentOptionID = ($modification && $modification->exists()) ? $modification->ModifierOptionID : null;
    $newOptions = array_keys($optionsMap);

    if (!in_array($currentOptionID, $newOptions)) {
      $this->setValue(array_shift($newOptions));
    }
    else {
      $this->setValue($currentOptionID);  
    }
  }

  /**
   * Ensure that the value is the ID of a valid {@link FlatFeeShippingRate} and that the 
   * FlatFeeShippingRate it represents is valid for the Shipping country being set in the 
   * {@link Order}.
   * 
   * @see ModifierSetField::validate()
   */
  function validate($validator){

    $valid = true;
    $value = $this->Value();
    $formData = $validator->getForm()->getData();
    $flatFeeShippingRates = DataObject::get('FlatFeeShippingRate');
    $shippingAddressCountry = (isset($formData['Shipping[Country]'])) ? $formData['Shipping[Country]'] : null;

    //If the value is not in the set of shipping countries, error
    //If the shipping country does not match the current shipping country, error

    if (!$flatFeeShippingRates || !$flatFeeShippingRates->exists()) {
       
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
     
    $shippingOption = $flatFeeShippingRates->find('ID', $value);
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