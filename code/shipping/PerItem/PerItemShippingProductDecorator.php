<?php

class PerItemShippingProductDecorator extends DataObjectDecorator {

	function extraStatics() {
		return array(
			'db' => array(
				'ShippingCost' => 'Money',
		    'RandomTest' => 'Text'
			)
		);
	}

	function updateCMSFields($fields) {
	  
	  //This is going to be replaced by a complex table field for PerItemShippingOptions
	  
		$shippingCostField = new MoneyField('ShippingCost', 'Shipping cost for this product');
	  $shippingCostField->setAllowedCurrencies(Product::$allowed_currency);	
	  $fields->addFieldToTab('Root.Content.Shipping', $shippingCostField);
	  
	  
	  $fields->addFieldToTab('Root.Content.Shipping', new TextField('RandomTest'));
	}

}
