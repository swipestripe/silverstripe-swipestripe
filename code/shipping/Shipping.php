<?php
class Shipping extends DataObject {
	
	public static $supported_methods = array(
		//'FlatFeeShipping',
		//'PerItemShipping'
	);
	
	/**
	 * Countries allowed to be shipped to
	 * 
	 * @see Shipping::set_supported_countries()
	 * @var Array
	 */
	protected static $supported_countries = array(
	  //'NZ' => 'New Zealand'
	);
	
	/**
	 * Get al the fields from all the shipping modules that are enabled
	 * 
	 * @param Order $order
	 */
	static function combined_form_fields($order) {
	  $fields = new FieldSet();
	  
	  foreach (self::$supported_methods as $className) {
	    
	    $method = new $className();
	    $methodFields = $method->getFormFields($order);
	    
	    if ($methodFields && $methodFields->exists()) foreach ($methodFields as $field) {
	      $fields->push($field);
	    } 
	  }
	  return $fields;
	}
	
  function getFormFields($order) {
	  user_error("Please implement getFormFields() on $this->class", E_USER_ERROR);
	}
	
	static function combined_form_requirements($order) {
	  //TODO is this really needed? all modifier fields go into Validation as required to perform validation on
	}
	
	function getFormRequirements() {
	  //TODO is this really needed?
	  user_error("Please implement getFormRequirements() on $this->class", E_USER_ERROR);
	}
	
	function Amount($optionID, $order) {
	  return;
	}
	
	function Description($optionID) {
    return;
	}
	
  public static function set_supported_countries(Array $countries) {
    
    //Check each of the countries before adding them
    foreach ($countries as $countryCode) {
      
      if ($countryName = Geoip::countryCode2name($countryCode)) {
        self::$supported_countries[$countryCode] = $countryName;
      }
      else {
        user_error("Cannot set allowed country, it must be a country code supported by Geoip class.", E_USER_WARNING);
        //TODO return meaningful error to browser in case error not shown
        return; 
      }
    }
	}
	
	public static function supported_countries() {
	  return self::$supported_countries;
	}

}