<?php

class PerItemShipping extends Shipping {
  
  public static function enable() {
    //Set all the configuration stuff in here
    
    Shipping::$supported_methods[] = 'PerItemShipping';
    
  }

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
	
  function getFormFields($order) {
	  
	  $fields = new FieldSet();
	  $orderItems = $order->Items();
	  
	  SS_Log::log(new Exception(print_r($order->Items(), true)), SS_Log::NOTICE);
	  
	  $shippingCost = new Money();
	  $shippingCost->setCurrency(Modifier::currency());
	  
	  if ($orderItems && $orderItems->exists()) foreach ($orderItems as $item) {
	    
	    $product = $item->Object();
	    if ($product) {
	      $cost = $product->ShippingCost;
	      if ($cost && $cost instanceof Money) {
	        $shippingCost->Amount += $cost->Amount;
	      } 
	    }
	  }

	  $fields->push(new ModifierSetField(
	  	'ItemShipping', 
	  	'Shipping per item',
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

}