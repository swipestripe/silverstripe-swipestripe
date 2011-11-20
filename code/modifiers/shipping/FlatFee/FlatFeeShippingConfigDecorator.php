<?php
/**
 * So that {@link FlatFeeShippingRate}s can be created in {@link SiteConfig}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage shipping
 * @version 1.0
 */
class FlatFeeShippingConfigDecorator extends DataObjectDecorator {

  /**
   * Attach {@link FlatFeeShippingRate}s to {@link SiteConfig}.
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'has_many' => array(
			  'FlatFeeShippingRates' => 'FlatFeeShippingRate'
			)
		);
	}

	/**
	 * Create {@link ComplexTableField} for managing {@link FlatFeeShippingRate}s.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
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