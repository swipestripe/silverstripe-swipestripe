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
class FlatFeeTaxField extends ModifierTextField {
  
  /**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "FlatFeeTaxField";
	
	protected $amount;

  /**
   * Render field with the appropriate template.
   *
   * @see FormField::FieldHolder()
   * @return String
   */
  function FieldHolder() {
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
    Requirements::javascript('shop/javascript/FlatFeeTaxField.js');
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
    
    return;

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
    return $valid;

  }
  
  function setAmount(Money $amount) {
    $this->amount = $amount;
  }
  
  function Amount() {
    return $this->amount;
  }
}