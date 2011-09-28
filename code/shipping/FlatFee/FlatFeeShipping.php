<?php
/**
 * 
 * Flat fee shipping
 * 
 * @author frankmullenger
 *
 */
class FlatFeeShipping extends Shipping {

  public function Amount($optionID, $order) {
    $amount = new Money();
	  
	  $currency = Modifier::currency();
	  $amount->setCurrency($currency);
	  
	  $shippingCosts = array(
	    1 => '5.00',
	    2 => '5.00',
	    3 => '10.95'
	  );
	  $amount->setAmount($shippingCosts[$optionID]);
	  return $amount;
  }
  
  public function Description($optionID) {
    $shippingDescriptions = array(
	    1 => 'Flat Fee Shipping',
	    2 => 'Some Other Shipping',
	    3 => 'Air Shipping'
	  );
	  return $shippingDescriptions[$optionID];
  }
	
  function getFormFields($order) {
	  
	  $fields = new FieldSet();

	  $fields->push(new ModifierSetField(
	  	'FlatFeeShipping', 
	  	'Flat Fee Shipping',
	  	array(
	  	  1 => 'Flat Fee Shipping $5',
	  	  2 => 'Some other shipping that is $5',
	  	  3 => 'Air shipping $10.95'
	  	),
	  	1
	  ));
	  
	  return $fields;
	}
	
	function getFormRequirements() {
	  return;
	}

}