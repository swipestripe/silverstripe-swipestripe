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

    //$fields->addFieldToTab("Root", new TabSet('StripeyCart')); 
    $fields->addFieldToTab("Root.StripeyCart", 
      new TabSet('Shipping')
    );
    $fields->addFieldToTab("Root.StripeyCart.Shipping", 
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
    $fields->addFieldToTab("Root.StripeyCart.Shipping.FlatFeeShipping", $flatFeeCountryManager);
	}

}