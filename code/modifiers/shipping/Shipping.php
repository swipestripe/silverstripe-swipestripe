<?php
class Shipping extends DataObject {

	/**
	 * Countries allowed to be shipped to
	 * 
	 * @see Shipping::set_supported_countries()
	 * @var Array
	 */
	protected static $supported_countries = array(
	  //'NZ' => 'New Zealand'
	);
	
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