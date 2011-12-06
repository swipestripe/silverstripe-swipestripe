<?php
/**
 * So that {@link FlatFeeTaxRate}s can be created in {@link SiteConfig}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage shipping
 * @version 1.0
 */
class FlatFeeTaxConfigDecorator extends DataObjectDecorator {

  /**
   * Attach {@link FlatFeeTaxRate}s to {@link SiteConfig}.
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'has_many' => array(
			  'FlatFeeTaxRates' => 'FlatFeeTaxRate'
			)
		);
	}

	/**
	 * Create {@link ComplexTableField} for managing {@link FlatFeeTaxRate}s.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {

    //$fields->addFieldToTab("Root", new TabSet('Shop')); 
    $fields->addFieldToTab("Root.Shop", 
      new TabSet('Tax')
    );
    $fields->addFieldToTab("Root.Shop.Tax", 
      new Tab('FlatFeeTax')
    );
    
    $flatFeeManager = new ComplexTableField(
      $this->owner,
      'FlatFeeTaxRates',
      'FlatFeeTaxRate',
      array(
        'Title' => 'Label',
        'Description' => 'Description',
        'SummaryOfCountryCode' => 'Country',
        'SummaryOfAmount'=> 'Amount',
        'SummaryOfRate' => 'Rate'
      ),
      'getCMSFields_forPopup'
    );
    $fields->addFieldToTab("Root.Shop.Tax.FlatFeeTax", $flatFeeManager);
	}

}