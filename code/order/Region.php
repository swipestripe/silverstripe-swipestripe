<?php
/**
 * Countries for shipping and billing addresses.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 * @version 1.0
 */
class Region extends DataObject {
   
  public static $db = array(
		'Code' => "Varchar", 
	  'Title' => 'Varchar'
	);
	
	public static $has_one = array (
    'SiteConfig' => 'SiteConfig',
	  'Country' => 'Country'
  );
  
  public static $summary_fields = array(
    'Title' => 'Title',
    'Code' => 'Code',
    'Country.Title' => 'Country'
  );
  
  public function forTemplate() {
    return;   
  }
  
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
	
	public static function billing_regions() {
	  
	  $regions = DataObject::get('Region_Billing');
    if ($regions && $regions->exists()) {
      return $regions->map();
    }
	  return array();
	}

}

class Region_Shipping extends Region {

  function getCMSFields() {

    $fields = parent::getCMSFields();
    $countryField = new DropdownField('CountryID', 'Country', Country::shipping_countries());
    $fields->replaceField('CountryID', $countryField);
    return $fields;
  }
}

class Region_Billing extends Region {

}

