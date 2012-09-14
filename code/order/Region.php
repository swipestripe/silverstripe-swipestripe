<?php
/**
 * Regions for countries
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Region extends DataObject {
  
  /**
   * Singular name
   * 
   * @var String
   */
  public static $singular_name = 'Region';
  
  /**
   * Plural name
   * 
   * @var String
   */
  public static $plural_name = 'Regions';
   
  /**
   * Fields 
   * 
   * @var Array
   */
  public static $db = array(
		'Code' => "Varchar", 
	  'Title' => 'Varchar'
	);
	
	/**
	 * Managed via the SiteConfig, regions are related to Countries
	 * 
	 * @var Array
	 */
	public static $has_one = array (
    'ShopConfig' => 'ShopConfig',
	  'Country' => 'Country'
  );
  
  /**
   * Summary fields
   * 
   * @var Array
   */
  public static $summary_fields = array(
    'Title' => 'Title',
    'Code' => 'Code',
    'Country.Title' => 'Country'
  );

  /**
   * Convenience function to prevent errors thrown
   */
  public function forTemplate() {
    return;   
  }
  
  /**
   * Retrieve map of shipping regions including Country ID
   * 
   * @return Array 
   */
  public static function shipping_regions() {

    $countryRegions = array();
    $regions = DataObject::get('Region_Shipping');
    if ($regions && $regions->exists()) {

      foreach ($regions as $region) {
        $countryRegions[$region->CountryID][$region->ID] = $region->Title;
      }
    }
	  return $countryRegions;
	}
	
	/**
   * Retrieve map of billing regions including Country ID
   * Not currently used
   * 
   * @return Array 
   */
	public static function billing_regions() {
	  
	  $regions = DataObject::get('Region_Billing');
    if ($regions && $regions->exists()) {
      return $regions->map();
    }
	  return array();
	}

}

/**
 * Shipping regions
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Region_Shipping extends Region {

  /**
   * Fields for CRUD of shipping regions
   * 
   * @see DataObject::getCMSFields()
   */
  function getCMSFields() {

    $fields = parent::getCMSFields();
    $countryField = new DropdownField('CountryID', 'Country', Country::shipping_countries());
    $fields->replaceField('CountryID', $countryField);
    return $fields;
  }
}

/**
 * Billing regions, not currently used
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Region_Billing extends Region {

}

