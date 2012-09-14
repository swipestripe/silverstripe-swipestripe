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

class ShopConfig_Form extends Form {

  public function EmailSettings() {

    $controller = Controller::curr();

    $fields = new FieldList();
    $fields->push(new TextField('Email', 'Email'));

    $actions = new FieldList();
    $actions->push(FormAction::create('saveEmailSettings', _t('GridFieldDetailForm.Save', 'Save'))
      ->setUseButtonTag(true)
      ->addExtraClass('ss-ui-action-constructive')
      ->setAttribute('data-icon', 'add'));


    //Need to render the entire model admin with this new form here

    $form = new ShopConfig_Form(
      $this,
      'EmailSettings',
      $fields,
      $actions
    );

    return $form->forTemplate();
  }

  public function saveEmailSettings() {
    SS_Log::log(new Exception(print_r('getting into saveEmailSettings()', true)), SS_Log::NOTICE);
  }

  public function Link($action = null) {
    return null;
    return "ShopConfig/edit/$action";
  }

  public function securityTokenEnabled() {
    return false;
  }

}