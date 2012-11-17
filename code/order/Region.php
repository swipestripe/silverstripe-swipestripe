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
	  'Title' => 'Varchar',
    'SortOrder' => 'Int'
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

  public static $default_sort = 'SortOrder';

  /**
   * Convenience function to prevent errors thrown
   */
  public function forTemplate() {
    return;   
  }
  
  /**
   * Retrieve map of shipping regions including Country code
   * 
   * @return Array 
   */
  public static function shipping_map() {

    $countryRegions = array();
    $regions = Region_Shipping::get();
    if ($regions && $regions->exists()) {

      foreach ($regions as $region) {
        $country = $region->Country();
        $countryRegions[$country->Code][$region->Code] = $region->Title;
      }
    }
	  return $countryRegions;
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

    // $fields = new FieldList(
    //   $rootTab = new TabSet('Root',
    //     $tabMain = new Tab('Region',
    //       TextField::create('Code', _t('Region.CODE', 'Code')),
    //       TextField::create('Title', _t('Region.TITLE', 'Title')),
    //       DropdownField::create('CountryID', 'Country', Country_Shipping::get()->map()->toArray())
    //     )
    //   )
    // );
    // return $fields;

    $fields = parent::getCMSFields();
    $fields->replaceField('CountryID', DropdownField::create('CountryID', 'Country', Country_Shipping::get()->map()->toArray()));
    $fields->removeByName('SortOrder');
    $fields->removeByName('ShopConfigID');
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

