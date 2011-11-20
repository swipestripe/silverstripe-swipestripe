<?php
/**
 * 
 * Flat fee shipping countries
 * 
 * TODO change to FlatFeeShippingRates
 * 
 * @author frankmullenger
 *
 */
class FlatFeeShippingRate extends DataObject {
  
  public static $db = array(
    'Title' => 'Varchar',
    'Description' => 'Varchar',
    'Amount' => 'Money',
    'CountryCode' => 'Varchar(2)' //Two letter country codes for ISO 3166-1 alpha-2
	);
	
	static $has_one = array (
    'SiteConfig' => 'SiteConfig'
  );
	
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $fields->push(new TextField('Title', 'Label'));
    $fields->push(new TextField('Description', 'Description'));
    
    $amountField = new MoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    
    $countryField = new DropdownField('CountryCode', 'Country', Shipping::supported_countries());
    $fields->push($countryField);

    return $fields;
  }
  
  public function Label() {
    return $this->Title . ' ' . $this->SummaryOfAmount();
  }
  
  public function SummaryOfAmount() {
    return $this->Amount->Nice();
  }
  
  public function SummaryOfCountryCode() {
    $supportedCountries = Shipping::supported_countries();
    if (in_array($this->CountryCode, array_keys($supportedCountries))) {
      return $supportedCountries[$this->CountryCode];
    }
    return 'No Country Set';
  }
	
}