<?php
class ShopConfig extends DataObject {

  public static $singular_name = 'Settings';
  public static $plural_name = 'Settings';

  public static $db = array(
    'LicenceKey' => 'Varchar',

    'BaseCurrency' => 'Varchar(3)',
    'BaseCurrencySymbol' => 'Varchar(10)',

    'CartTimeout' => 'Int',
    'CartTimeoutUnit' => "Enum('minute, hour, day', 'hour')",
    'StockCheck' => 'Boolean',
    'StockManagement' => "Enum('strict, relaxed', 'strict')",

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
    'BillingRegions' => 'Region_Billing',

    'Attributes' => 'Attribute_Default',

    'ExtensionKeys' => 'ShopConfig_ExtensionKey'
  );

  public static $defaults = array(
    'CartTimeout' => 1,
    'CartTimeoutUnit' => 'hour',
    'StockCheck' => false,
    'StockManagement' => 'strict'
  );

  public static function current_shop_config() {

  	//TODO: lazy load this

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

  public static function base_currency_warning() {
    $config = self::current_shop_config();
    $warning = null;

    if (!$config->BaseCurrency) {
     $warning = _t('ShopConfig.BASE_CURRENCY_WARNING','
         Warning: Base currency is not set, please set base currency in the shop settings area before proceeding
     ');
    }
    return $warning;
  }

  /**
   * Setup a default ShopConfig record if none exists
   */
  public function requireDefaultRecords() {

    parent::requireDefaultRecords();

    if(!self::current_shop_config()) {
      $shopConfig = new ShopConfig();
      $shopConfig->write();
      DB::alteration_message('Added default shop config', 'created');
    }
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

    $base = Director::baseFolder() . '/swipestripe-';
    $dirs = glob($base . '*', GLOB_ONLYDIR);
    $extensionLicenseKeys = $config->ExtensionKeys()->map('Title', 'LicenceKey')->toArray();
    //$extensionLicenseKeys = ShopSettings::get_extension_license_keys();
    
    if ($dirs && is_array($dirs)) {

      $data['Extensions'] = array();

      foreach ($dirs as $dir) {
        $extensionName = str_replace($base, '', $dir);

        if ($extensionName){
        	$key = (isset($extensionLicenseKeys[$extensionName])) ? $extensionLicenseKeys[$extensionName] : null;
          $data['Extensions'][]['Extension'] = array(
          	'Name' => $extensionName,
            'Key' => $key
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

class ShopConfig_ExtensionKey extends DataObject {

	public static $singular_name = 'Extension Licence Key';
  public static $plural_name = 'Extension Licence Keys';

	public static $db = array(
		'Title' => 'Varchar',
		'LicenceKey' => 'Varchar'
	);

	public static $has_one = array(
		'ShopConfig' => 'ShopConfig'
	);

	public static $summary_fields = array(
		'Title' => 'Title',
		'LicenceKey' => 'Licence Key'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$exts = array();
		$base = Director::baseFolder() . '/swipestripe-';
    $dirs = glob($base . '*', GLOB_ONLYDIR);

    if ($dirs && is_array($dirs)) foreach ($dirs as $dir) {

      $extensionName = str_replace($base, '', $dir);
      $exts[$extensionName] = $extensionName;
    }

		$fields->replaceField('Title', DropdownField::create('Title', 'Extension', $exts)
			->setRightTitle('Name of the extension for this licence key')
		);

		$fields->removeByName('ShopConfigID');

		return $fields;
	}
}