<?php

class PerItemShipping extends Shipping {
  
  /**
   * For setting configuration, should be called from _config.php files only
   */
  public static function enable() {
    Shipping::$supported_methods[] = 'PerItemShipping';
    Object::add_extension('Product', 'PerItemShippingProductDecorator');
  }

  function getFormFields($order) {
	  
	  $fields = new FieldSet();
	  $orderItems = $order->Items();
	  
	  $shippingCost = new Money();
	  $shippingCost->setCurrency(Modifier::currency());
	  
	  if ($orderItems && $orderItems->exists()) foreach ($orderItems as $item) {
	    
	    $product = $item->Object();
	    if ($product) {
	      
	      SS_Log::log(new Exception(print_r($product, true)), SS_Log::NOTICE);
	      
	      $cost = $product->ShippingCost;
	      if ($cost && $cost instanceof Money) {
	        $shippingCost->Amount += $cost->Amount;
	      } 
	    }
	  }

	  $fields->push(new ModifierSetField(
	  	'ItemShipping', 
	  	'Shipping per item (total cost)',
	  	array(
	  	  1 => 'Total item shipping costs' . $shippingCost->Nice()
	  	),
	  	1
	  ));
	  
	  return $fields;
	}
	
	function getFormRequirements() {
	  return;
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

}