<?php
class ShopConfig extends DataObject {

  public static $singular_name = 'Settings';
  public static $plural_name = 'Settings';
  
  /**
   * To hold the license key for SwipeStripe. Usually set in mysite/_config file.
   * 
   * @see ShopConfig::set_license_key()
   * @var String License key 
   */
  private static $license_key;
  
  /**
   * To hold the license keys for SwipeStripe extensions. Usually set in mysite/_config file.
   * 
   * @see ShopConfig::set_extension_license_keys()
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

/**
 * Controller to display a shop settings such as the license key publicly.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopConfig_Controller extends Page_Controller {

  /**
   * Output license keys in XML format
   * 
   * @see Page_Controller::init()
   */
  public function init() {

    $data = array();
    $data['Key'] = ShopConfig::get_license_key();
    
    //Find folders that start with swipestripe_, get their license keys
    $base = Director::baseFolder() . '/swipestripe_';
    $dirs = glob($base . '*', GLOB_ONLYDIR);
    $extensionLicenseKeys = ShopConfig::get_extension_license_keys();
    
    if ($dirs && is_array($dirs)) {
      $data['Extensions'] = array();
      foreach ($dirs as $dir) {
        $extensionName = str_replace($base, '', $dir);
        if ($extensionName){
          $data['Extensions'][]['Extension'] = array(
            'Name' => $extensionName,
            'Key' => $extensionLicenseKeys[$extensionName]
          );
        } 
      }
    }

    $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><SwipeStripe></SwipeStripe>");
    $this->array_to_xml($data, $xml);
    
    header ("content-type: text/xml");
    print $xml->asXML();
    exit;
  }
  
  /**
   * Helper to convert arrays into xml.
   * 
   * @param Array $data
   * @param SimpleXMLElement $xml
   */
  public function array_to_xml($data, &$xml) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        if (!is_numeric($key)){
          $subnode = $xml->addChild("$key");
          self::array_to_xml($value, $subnode);
        }
        else{
          self::array_to_xml($value, $xml);
        }
      }
      else {
        $xml->addChild("$key","$value");
      }
    }
  }
}