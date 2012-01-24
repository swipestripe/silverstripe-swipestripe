<?php
/**
 * A convenience class to set shipping supported countries in a centralised location.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage modifiers
 * @version 1.0
 */
class Shipping extends DataObject {

	/**
	 * Countries allowed to be shipped to, these will be options in the shipping address
	 * of the Checkout form.
	 * 
	 * @see Shipping::set_supported_countries()
	 * @var Array List of countries that goods can be shipped to e.g:'NZ' => 'New Zealand'
	 */
	protected static $supported_countries = array(
	);
	
	/**
	 * Set countries that are supported for shipping to.
	 * 
	 * @see Shipping::$supported_countries
	 * @param array $countries
	 */
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
	
	/**
	 * Get countries supported for shipping.
	 * 
	 * @see Shipping::$supported_countries
	 * @var Array List of countries that goods can be shipped to e.g:'NZ' => 'New Zealand'
	 */
	public static function supported_countries() {
	  return self::$supported_countries;
	}
  
}