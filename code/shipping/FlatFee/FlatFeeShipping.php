<?php
/**
 * 
 * Flat fee shipping
 * 
 * @author frankmullenger
 *
 */
class FlatFeeShipping extends Shipping {
  
	/**
   * For setting configuration, should be called from _config.php files only
   */
  public static function enable() {
    Shipping::$supported_methods[] = 'FlatFeeShipping';
    Object::add_extension('SiteConfig', 'FlatFeeShippingConfigDecorator');
  }

  /**
   * Use the optionID to get the amount for the FlatFeeShippingCountry
   * 
   * @see Shipping::Amount()
   * @param $optionID FlatFeeShippingCountry ID
   */
  public function Amount($optionID, $order) {

    $amount = new Money();
    $currency = Modifier::currency();
	  $amount->setCurrency($currency);
    $flatFeeShippingCountries = DataObject::get('FLatFeeShippingCountry');

    if ($flatFeeShippingCountries && $flatFeeShippingCountries->exists()) {
      
      $shippingCountry = $flatFeeShippingCountries->find('ID', $optionID);
      if ($shippingCountry) {
        $amount->setAmount($shippingCountry->Amount->getAmount());
      }
      else user_error("Cannot find flat fee country for that ID.", E_USER_WARNING);
    }
	  return $amount;
  }
  
  /**
   * Use the optionID to get the description summary for the FlatFeeShippingCountry
   * 
   * @see Shipping::Description()
   * @param $optionID FlatFeeShippingCountry ID
   */
  public function Description($optionID) {
    
    $description = null;
    $flatFeeShippingCountries = DataObject::get('FLatFeeShippingCountry');
    
    if ($flatFeeShippingCountries && $flatFeeShippingCountries->exists()) {
      
      $shippingCountry = $flatFeeShippingCountries->find('ID', $optionID);
      if ($shippingCountry) {
        $description = $shippingCountry->DescriptionSummary();
      }
      else user_error("Cannot find flat fee country for that ID.", E_USER_WARNING);
    }
	  return $description;
  }
	
  function getFormFields(Order $order) {
    
    //TODO use SiteConfig object to get the countries back, but at the moment
    //SiteConfig ID not being set correctly on country db rows

	  $fields = new FieldSet();
	  $flatFeeShippingCountries = DataObject::get('FLatFeeShippingCountry');
	  
	  //Filter based on shipping address
	  $shippingCountry = null;
	  if ($order && $order->exists()) {
	    $shippingAddress = $order->ShippingAddress();
  	  if ($shippingAddress) $shippingCountry = $shippingAddress->Country;
	  }
	  
	  if ($shippingCountry) foreach ($flatFeeShippingCountries as $country) {
	    if ($country->CountryCode != $shippingCountry) $flatFeeShippingCountries->remove($country);
	  }

	  $fields->push(new ModifierSetField(
	  	'FlatFeeShipping', 
	  	'Flat Fee Shipping',
	  	$flatFeeShippingCountries->map('ID', 'DescriptionSummary'),
	  	$flatFeeShippingCountries->First()->ID
	  ));
	  
	  return $fields;
	}
	
	function getFormRequirements() {
	  return;
	}

}