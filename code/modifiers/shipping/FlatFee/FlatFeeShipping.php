<?php
/**
 * Flat fee shipping, flat fees can be added for each supported shipping country from 
 * the SiteConfig using {@link FlatFeeShippingRate}s.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
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
  
  /**
   * Form fields for displaying on the Checkout form, a dropdown of {@link FlatFeeShippingRate}s
   * that are filtered depending on the shipping country selected.
   * 
   * @see FlatFeeShippingRate
   * @see Modifier::combined_form_fields()
   * @param Order $order
   * @return FieldSet
   */
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
	
	/**
   * Get form requirements for this modifier.
   * 
   * @see Modifier::combined_form_fields()
   * @param Order $order
   * @return FieldSet
   */
	public function getFormRequirements($order) {
	  return new FieldSet();
	}

  /**
   * Get Amount for this modifier so that it can be saved into an {@link Order} {@link Modification}.
   * 
   * @see Modification
   * @param Order $order
   * @param Int $value ID for a {@link FlatFeeShippingRate} 
   * @return Money
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
   * Get Description for this modifier so that it can be saved into an {@link Order} {@link Modification}.
   * 
   * @see Modification
   * @param Order $order
   * @param Mixed $value A value passed from the Checkout form POST data usually
   * @return String
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
  
  /**
   * Add modifider to an {@link Order} .
   * 
   * @see Modifier_Interface::addToOrder()
   * @param Order $order
   * @param Mixed $value
   */
  function addToOrder($order, $value) {
  
    $modification = new Modification();
    $modification->ModifierClass = get_class($this);
    $modification->ModifierOptionID = $value;
    
    $modification->Amount = $this->Amount($order, $value);
    $modification->Description = $this->Description($order, $value);

    $modification->OrderID = $order->ID;
    $modification->write();
  }

}