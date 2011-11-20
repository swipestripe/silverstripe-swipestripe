<?php

class FlatFeeShippingConfigDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'has_many' => array(
			  'FlatFeeShippingRates' => 'FlatFeeShippingRate'
			)
		);
	}

  function updateCMSFields(FieldSet &$fields) {

    //$fields->addFieldToTab("Root", new TabSet('Shop')); 
    $fields->addFieldToTab("Root.Shop", 
      new TabSet('Shipping')
    );
    $fields->addFieldToTab("Root.Shop.Shipping", 
      new Tab('FlatFeeShipping')
    );
    
    $flatFeeManager = new ComplexTableField(
      $this->owner,
      'FlatFeeShippingRates',
      'FlatFeeShippingRate',
      array(
        'Title' => 'Label',
        'Description' => 'Description',
        'SummaryOfCountryCode' => 'Country',
        'SummaryOfAmount'=> 'Amount'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Shop.Shipping.FlatFeeShipping", $flatFeeManager);
	}

}