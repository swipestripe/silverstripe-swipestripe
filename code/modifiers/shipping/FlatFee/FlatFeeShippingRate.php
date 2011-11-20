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
    'CountryCode' => 'Varchar(2)', //Two letter country codes for ISO 3166-1 alpha-2
    'Amount' => 'Money',
    'Description' => 'Varchar'
	);
	
	static $has_one = array (
    'SiteConfig' => 'SiteConfig'
  );
	
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $amountField = new MoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    
    $countryField = new DropdownField('CountryCode', 'Country', Shipping::supported_countries());
    $fields->push($countryField);
    
    $fields->push(new TextField('Description', 'Description (for displaying on checkout form)'));

    return $fields;
  }
  
  public function SummaryOfAmount() {
    return $this->Amount->Nice();
  }
  
  public function SummaryOfDescription() {
    return $this->Description . ' ' . $this->Amount->Nice();
  }
	
}