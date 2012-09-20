<?php
class ShopConfig extends DataObject {

  public static $singular_name = 'Settings';
  public static $plural_name = 'Settings';
  
  /**
   * To hold the license key for SwipeStripe. Usually set in mysite/_config file.
   * 
   * @see ShopSettings::set_license_key()
   * @var String License key 
   */
  private static $license_key;
  
  /**
   * To hold the license keys for SwipeStripe extensions. Usually set in mysite/_config file.
   * 
   * @see ShopSettings::set_extension_license_keys()
   * @var String License key 
   */
  private static $extension_license_keys;
  
  /**
   * Set the license key, usually called in mysite/_config.
   * 
   * @param String $key License key
   */
  public static function set_license_key($key) {
    self::$license_key = $key;
  }
  
	/**
   * Get the license key
   * 
   * @return String License key
   */
  public static function get_license_key() {
    return self::$license_key;
  }
  
  /**
   * Set extension license keys, usually called in mysite/_config.
   * 
   * @param Array $key 
   */
  public static function set_extension_license_keys(Array $keys) {
    self::$extension_license_keys = $keys;
  } 

  /**
   * Get extension license keys
   * 
   * @return Array Extension license keys
   */
  public static function get_extension_license_keys() {
    return self::$extension_license_keys;
  }

  static $db = array(
    'EmailSignature' => 'HTMLText',
    'ReceiptSubject' => 'Varchar',
    'ReceiptBody' => 'HTMLText',
    'ReceiptFrom' => 'Varchar',
    'NotificationSubject' => 'Varchar',
    'NotificationBody' => 'HTMLText',
    'NotificationTo' => 'Varchar'
  );

  static $has_many = array(
    'ShippingCountries' => 'Country_Shipping',
    'BillingCountries' => 'Country_Billing',
    'ShippingRegions' => 'Region_Shipping',
    'BillingRegions' => 'Region_Billing'
  );
}
