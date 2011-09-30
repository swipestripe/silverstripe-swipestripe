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
	  $shippingCost = $this->calculateTotal($order);

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
	
	public function calculateTotal($order) {
	  
	  $orderItems = $order->Items();
	  $shippingCost = new Money();
	  $shippingCost->setCurrency(Modifier::currency());
	  
	  if ($orderItems && $orderItems->exists()) foreach ($orderItems as $item) {
	    
	    $product = $item->Object();
	    if ($product) {
	      $cost = $product->ShippingCost;
	      if ($cost && $cost instanceof Money) $shippingCost->Amount += ($cost->Amount * $item->Quantity);
	    }
	  }
	  
	  return $shippingCost;
	}
	
  public function Amount($optionID, $order) {
    
    return $this->calculateTotal($order);
  }
  
  public function Description($optionID) {
    
    return 'Total shipping costs (per item)';
  }

}