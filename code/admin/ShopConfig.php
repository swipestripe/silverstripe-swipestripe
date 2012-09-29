<?php
class ShopConfig extends DataObject {

  public static $singular_name = 'Settings';
  public static $plural_name = 'Settings';

  public static $db = array(
    'LicenceKey' => 'Varchar',
    'EmailSignature' => 'HTMLText',
    'ReceiptSubject' => 'Varchar',
    'ReceiptBody' => 'HTMLText',
    'ReceiptFrom' => 'Varchar',
    'NotificationSubject' => 'Varchar',
    'NotificationBody' => 'HTMLText',
    'NotificationTo' => 'Varchar'
  );

  public static $has_many = array(
    'ShippingCountries' => 'Country_Shipping',
    'BillingCountries' => 'Country_Billing',
    'ShippingRegions' => 'Region_Shipping',
    'BillingRegions' => 'Region_Billing'
  );

  public static function current_shop_config() {
    return ShopConfig::get()->First();
  }

  public static function licence_key_warning() {
    $config = self::current_shop_config();
    $warning = null;

    if (!$config->LicenceKey) {
     $warning = _t('ShopConfig.LICENCE_WARNING','
         Warning: You have SwipeStripe installed without a license key. 
         Please <a href="http://swipestripe.com" target="_blank">purchase a license key here</a> before this site goes live.
     ');
    }
    return $warning;
  }
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
    $config = ShopConfig::current_shop_config();
    $data['Key'] = $config->LicenceKey;

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