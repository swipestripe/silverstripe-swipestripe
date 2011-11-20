<?php
/**
 * Flat fee shipping
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage shipping
 * @version 1.0
 */
class FlatFeeShipping extends Modifier implements Modifier_Interface {
  
	/**
   * For setting configuration, should be called from _config.php files only
   */
  public static function enable() {
    Modifier::$supported_methods[] = 'FlatFeeShipping';
    Object::add_extension('SiteConfig', 'FlatFeeShippingConfigDecorator');
  }
  
  public function getFormFields($order) {
    
    //TODO use SiteConfig object to get the countries back, but at the moment
    //SiteConfig ID not being set correctly on country db rows

	  $fields = new FieldSet();
	  $flatFeeShippingRates = DataObject::get('FLatFeeShippingRate');
	  
	  if ($flatFeeShippingRates) {
	    
	    //TODO could probably do this filter in the DataObject::get()
	    //Filter based on shipping address
  	  $shippingCountry = null;
  	  if ($order && $order->exists()) {
  	    $shippingAddress = $order->ShippingAddress();
    	  if ($shippingAddress) $shippingCountry = $shippingAddress->Country;
  	  }
  	  
  	  if ($shippingCountry) foreach ($flatFeeShippingRates as $rate) {
  	    if ($rate->CountryCode != $shippingCountry) $flatFeeShippingRates->remove($rate);
  	  }
  
  	  $fields->push(new FlatFeeShippingField(
  	    $this,
  	  	'Flat Fee Shipping',
  	  	$flatFeeShippingRates->map('ID', 'Label')
  	  	//$flatFeeShippingCountries->First()->ID
  	  ));
	  }
	  
	  return $fields;
	}
	
	public function getFormRequirements($order) {
	  return;
	}

  /**
   * Use the optionID to get the amount for the FlatFeeShippingRate
   * 
   * @see Shipping::Amount()
   * @param $optionID FlatFeeShippingRate ID
   */
  public function Amount($order, $value) {

    $optionID = $value;
    $amount = new Money();
    $currency = Modification::currency();
	  $amount->setCurrency($currency);
    $flatFeeShippingRates = DataObject::get('FlatFeeShippingRate');

    if ($flatFeeShippingRates && $flatFeeShippingRates->exists()) {
      
      $shippingRate = $flatFeeShippingRates->find('ID', $optionID);
      if ($shippingRate) {
        $amount->setAmount($shippingRate->Amount->getAmount());
      }
      else {
        user_error("Cannot find flat fee rate for that ID.", E_USER_WARNING);
        //TODO return meaningful error to browser in case error not shown
        return;
      }
    }
	  return $amount;
  }
  
  /**
   * Use the optionID to get the description summary for the FlatFeeShippingRate
   * This is used as the description of the modifier in the Order, so it should be descriptive 
   * 
   * @see Order::addModifiersAtCheckout()
   * @see Shipping::Description()
   * @param $optionID FlatFeeShippingRate ID
   */
  public function Description($order, $value) {
    
    $optionID = $value;
    $description = null;
    $flatFeeShippingRates = DataObject::get('FlatFeeShippingRate');
    
    if ($flatFeeShippingRates && $flatFeeShippingRates->exists()) {
      
      $shippingRate = $flatFeeShippingRates->find('ID', $optionID);
      if ($shippingRate) {
        $description = $shippingRate->Description;
      }
      else {
        user_error("Cannot find flat fee rate for that ID.", E_USER_WARNING);
        //TODO return meaningful error to browser in case error not shown
        return; 
      }
    }
	  return $description;
  }

}