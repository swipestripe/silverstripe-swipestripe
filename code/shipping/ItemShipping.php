<?php
/**
 * 
 * Flat fee shipping
 * 
 * @author frankmullenger
 *
 */
class ItemShipping extends Shipping implements Modifier_Interface {

  public function Amount($optionID, $order) {
    $amount = new Money();
	  
	  $currency = Modifier::currency();
	  $amount->setCurrency($currency);
	  
	  $shippingCosts = array(
	    1 => '5.00'
	  );
	  $amount->setAmount($shippingCosts[$optionID]);
	  return $amount;
  }
  
  public function Description($optionID) {
    $shippingDescriptions = array(
	    1 => 'Testing'
	  );
	  return $shippingDescriptions[$optionID];
  }
	
  function getFormFields() {
	  
	  $fields = new FieldSet();

	  $fields->push(new ModifierSetField(
	  	'ItemShipping', 
	  	'Calculate some item shipping and return to the page here',
	  	array(
	  	  1 => 'Testing'
	  	),
	  	1
	  ));
	  
	  return $fields;
	}
	
	function getFormRequirements() {
	  return;
	}
	
  public static function combined_form_fields() {
	  return;
	}

}