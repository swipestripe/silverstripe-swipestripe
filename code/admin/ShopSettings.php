<?php
/**
 * Adding shop settings to the main {@link SiteConfig}. This will not work with subsites module due to
 * a problem with {@link ComplexTableField} which does not set the {@link SiteConfig} ID on records.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopSettings extends DataExtension {
  
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

  /**
   * Add database fields for shop settings like emails etc.
   * 
   * @see DataObjectDecorator::extraStatics()
   *
	function extraStatics() {

		return array(
			'db' => array(
		    'EmailSignature' => 'HTMLText',
				'ReceiptSubject' => 'Varchar',
		    'ReceiptBody' => 'HTMLText',
		    'ReceiptFrom' => 'Varchar',
				'NotificationSubject' => 'Varchar',
		    'NotificationBody' => 'HTMLText',
		    'NotificationTo' => 'Varchar'
			),
			'has_many' => array(
			  'ShippingCountries' => 'Country_Shipping',
		    'BillingCountries' => 'Country_Billing',
			  'ShippingRegions' => 'Region_Shipping',
			  'BillingRegions' => 'Region_Billing'
			)
		);
	}
  */

	/**
	 * Adding fields for shop settings such as email, license key.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {

    $fields->findOrMakeTabSet('Root.Shop');
    
    //License key
    $fields->addFieldToTab("Root.Shop", 
      new Tab('LicenseKey')
    );
    $licenseKeyField = new TextField('LicenseKey', _t('ShopSettings.LICENSEKEY', 'License Key'), self::$license_key);
    $fields->addFieldToTab('Root.Shop.LicenseKey', $licenseKeyField->performReadonlyTransformation());
    
    //TODO include the license here in a text area field and some info about setting the license key perhaps
    
    //Shop emails
    $fields->addFieldToTab("Root.Shop", 
      new TabSet('Emails')
    );
    $fields->addFieldToTab("Root.Shop.Emails", 
      new Tab('Receipt'),
      new Tab('Notification')
    );

    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextField('ReceiptFrom', _t('ShopSettings.FROM', 'From')));
    $receiptTo = new TextField('ReceiptTo', _t('ShopSettings.TO', 'To'));
    $receiptTo->setValue(_t('ShopSettings.RECEIPT_TO', 'Sent to customer'));
    $receiptTo = $receiptTo->performReadonlyTransformation();
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', $receiptTo);
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextField('ReceiptSubject', _t('ShopSettings.SUBJECT_LINE', 'Subject line')));
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextareaField('ReceiptBody', _t('ShopSettings.MESSAGE', 'Message (order details are included in the email)'), 8));
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextareaField('EmailSignature', _t('ShopSettings.SIGNATURE', 'Signature'), 8));
    
    $notificationFrom = new TextField('NotificationFrom', _t('ShopSettings.FROM', 'From'));
    $notificationFrom->setValue(_t('ShopSettings.NOTIFICATION_FROM', 'Customer email address'));
    $notificationFrom = $notificationFrom->performReadonlyTransformation();
    $fields->addFieldToTab('Root.Shop.Emails.Notification', $notificationFrom);
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextField('NotificationTo', _t('ShopSettings.TO', 'To')));
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextField('NotificationSubject', _t('ShopSettings.SUBJECT_LINE', 'Subject line')));
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextareaField('NotificationBody', _t('ShopSettings.MESSAGE', 'Message (order details are included in the email)'), 10));
    
    //Shipping
    $fields->findOrMakeTabSet('Root.Shop.Shipping');
    $fields->addFieldToTab("Root.Shop.Shipping", 
      new Tab('Countries')
    );
    $fields->addFieldToTab("Root.Shop.Shipping", 
      new Tab('Regions')
    );
     
    $managerClass = (class_exists('DataObjectManager')) ? 'DataObjectManager' : 'ComplexTableField';
    $manager = new $managerClass(
      $this->owner,
      'ShippingCountries',
      'Country_Shipping'
    );
    $fields->addFieldToTab("Root.Shop.Shipping.Countries", $manager);
    
    $managerClass = (class_exists('DataObjectManager')) ? 'DataObjectManager' : 'ComplexTableField';
    $manager = new $managerClass(
      $this->owner,
      'ShippingRegions',
      'Region_Shipping'
    );
    $fields->addFieldToTab("Root.Shop.Shipping.Regions", $manager);
    
    
    if (file_exists(BASE_PATH . '/swipestripe') && ShopSettings::get_license_key() == null) {
      
      $warning = _t('ShopSettings.LICENCE_WARNING','
        Warning: You have SwipeStripe installed without a license key. 
        Please <a href="http://swipestripe.com" target="_blank">purchase a license key here</a> before this site goes live.
			');
      
			$fields->addFieldToTab("Root.Main", new LiteralField("SwipeStripeLicenseWarning", 
				'<p class="message warning">'.$warning.'</p>'
			), "Title");
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
class ShopSettings_Controller extends Page_Controller {

  /**
   * Output license keys in XML format
   * 
   * @see Page_Controller::init()
   */
  public function init() {

    $data = array();
    $data['Key'] = ShopSettings::get_license_key();
    
    //Find folders that start with swipestripe_, get their license keys
    $base = Director::baseFolder() . '/swipestripe_';
    $dirs = glob($base . '*', GLOB_ONLYDIR);
    $extensionLicenseKeys = ShopSettings::get_extension_license_keys();
    
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