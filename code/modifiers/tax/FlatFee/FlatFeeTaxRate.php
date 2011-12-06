<?php
/**
 * Shipping rates that can be set in {@link SiteConfig}. Several flat rates can be set 
 * for any supported shipping country.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage shipping
 * @version 1.0
 */
class FlatFeeTaxRate extends DataObject {
  
  /**
   * Fields for this shipping rate
   * 
   * @var Array
   */
  public static $db = array(
    'Title' => 'Varchar',
    'Description' => 'Varchar',
    'Amount' => 'Money',
    'CountryCode' => 'Varchar(2)', //Two letter country codes for ISO 3166-1 alpha-2
  	'Rate' => 'Decimal(18,2)'
	);
	
	/**
	 * Shipping rates are associated with SiteConfigs.
	 * 
	 * TODO The CTF in SiteConfig does not save the SiteConfig ID correctly so this is moot
	 * 
	 * @var unknown_type
	 */
	static $has_one = array (
    'SiteConfig' => 'SiteConfig'
  );
	
  /**
   * Field for editing a {@link FlatFeeShippingRate}.
   * 
   * @return FieldSet
   */
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $fields->push(new TextField('Title', 'Label'));
    $fields->push(new TextField('Description', 'Description'));
    
    $amountField = new MoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    
    $countryField = new DropdownField('CountryCode', 'Country', Shipping::supported_countries());
    $fields->push($countryField);
    
    $rateField = new NumericField('Rate', 'Tax rate as a percentage');
    $fields->push($rateField);

    return $fields;
  }
  
  /**
   * Label for using on {@link FlatFeeShippingField}s.
   * 
   * @see FlatFeeShippingField
   * @return String
   */
  public function Label() {
    return $this->Title . ' ' . $this->SummaryOfRate();
  }
  
  /**
   * Summary of the amount
   * 
   * @return String Amount formatted with Nice()
   */
  public function SummaryOfAmount() {
    return $this->Amount->Nice();
  }
  
  public function SummaryOfRate() {
    return $this->Rate . ' %';
  }
  
  /**
   * Country name for a given country code
   * 
   * @return String Name of country
   */
  public function SummaryOfCountryCode() {
    $supportedCountries = Shipping::supported_countries();
    if (in_array($this->CountryCode, array_keys($supportedCountries))) {
      return $supportedCountries[$this->CountryCode];
    }
    return 'No Country Set';
  }
	
}