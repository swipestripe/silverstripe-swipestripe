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

  public function Amount($optionID, $order) {
    $amount = new Money();
	  
	  $currency = Modifier::currency();
	  $amount->setCurrency($currency);
	  
	  $shippingCosts = array(
	    1 => '5.00',
	    2 => '5.00',
	    3 => '10.95'
	  );
	  $amount->setAmount($shippingCosts[$optionID]);
	  return $amount;
  }
  
  public function Description($optionID) {
    $shippingDescriptions = array(
	    1 => 'Flat Fee Shipping',
	    2 => 'Some Other Shipping',
	    3 => 'Air Shipping'
	  );
	  return $shippingDescriptions[$optionID];
  }
	
  function getFormFields($order) {
    
    //TODO use site config to get the countries back, but at the moment
    //site config ID not being set correctly

	  $fields = new FieldSet();
	  $flatFeeShippingCountries = DataObject::get('FLatFeeShippingCountry');

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