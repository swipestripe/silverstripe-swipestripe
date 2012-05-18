<?php
/**
 * So that {@link FlatFeeShippingRate}s can be created in {@link SiteConfig}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
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

    $fields->findOrMakeTabSet('Root.Shop.Shipping');
    $fields->addFieldToTab("Root.Shop.Shipping", 
      new Tab('FlatFeeShipping')
    );
     
    $managerClass = (class_exists('DataObjectManager')) ? 'DataObjectManager' : 'ComplexTableField';
    $flatFeeManager = new $managerClass(
      $this->owner,
      'FlatFeeShippingRates',
      'FlatFeeShippingRate',
      array(
        'Title' => 'Label',
        'Description' => 'Description',
        'Country.Title' => 'Country',
        'SummaryOfAmount'=> 'Amount'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Shop.Shipping.FlatFeeShipping", $flatFeeManager);
	}

}