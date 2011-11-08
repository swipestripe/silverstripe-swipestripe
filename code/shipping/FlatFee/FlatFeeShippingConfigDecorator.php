<?php

class FlatFeeShippingConfigDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'has_many' => array(
			  'FlatFeeShippingCountries' => 'FlatFeeShippingCountry'
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
    
    $flatFeeCountryManager = new ComplexTableField(
      $this->owner,
      'FlatFeeShippingCountries',
      'FlatFeeShippingCountry',
      array(
        'Description' => 'Description',
        'CountryCode' => 'Country Code',
        'AmountSummary'=> 'Amount'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Shop.Shipping.FlatFeeShipping", $flatFeeCountryManager);
	}

}