<?php
/**
 * Adding shop settings to the main {@link SiteConfig}. This will not work with subsites module due to
 * a problem with {@link ComplexTableField} which does not set the {@link SiteConfig} ID on records.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */
class ShopSettings extends DataObjectDecorator {
  
  /**
   * To hold the license key for the shop. Usually set in mysite/_config file.
   * 
   * @see ShopSettings::set_license_key()
   * @var String License key 
   */
  private static $license_key;
  
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
   * Add database fields for shop settings like emails etc.
   * 
   * @see DataObjectDecorator::extraStatics()
   */
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
			)
		);
	}

	/**
	 * Adding fields for shop settings such as email, license key.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {

    $fields->addFieldToTab("Root", new TabSet('Shop')); 
    
    //License key
    $fields->addFieldToTab("Root.Shop", 
      new Tab('LicenseKey')
    );
    $licenseKeyField = new TextField('LicenseKey', 'License Key', self::$license_key);
    $fields->addFieldToTab('Root.Shop.LicenseKey', $licenseKeyField->performReadonlyTransformation());
    
    //TODO include the license here in a text area field and some info about setting the license key perhaps
    
    //Shop emails
    $fields->addFieldToTab("Root.Shop", 
      new TabSet('Emails')
    );
    $fields->addFieldToTab("Root.Shop.Emails", 
      new Tab('Receipt'),
      new Tab('Notification'),
      new Tab('Signature')
    );

    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextField('ReceiptFrom', 'From'));
    $receiptTo = new TextField('ReceiptTo', 'To');
    $receiptTo->setValue('Sent to customer');
    $receiptTo = $receiptTo->performReadonlyTransformation();
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', $receiptTo);
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextField('ReceiptSubject', 'Subject line'));
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextareaField('ReceiptBody', 'Message (order details are included in the email)', 8));
    $fields->addFieldToTab('Root.Shop.Emails.Receipt', new TextareaField('EmailSignature', 'Signature', 8));
    
    $notificationFrom = new TextField('NotificationFrom', 'From');
    $notificationFrom->setValue('Customer email address');
    $notificationFrom = $notificationFrom->performReadonlyTransformation();
    $fields->addFieldToTab('Root.Shop.Emails.Notification', $notificationFrom);
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextField('NotificationTo', 'To'));
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextField('NotificationSubject', 'Subject line'));
    $fields->addFieldToTab('Root.Shop.Emails.Notification', new TextareaField('NotificationBody', 'Message (order details are included in the email)', 10));
    
    //$fields->addFieldToTab('Root.Shop.Emails.Signature', new HtmlEditorField('EmailSignature', 'Signature for all emails', 15));
	}

}

/**
 * Controller to display a shop settings such as the license key publicly.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */
class ShopSettings_Controller extends Page_Controller {

  public function init() {

    header ("content-type: text/xml");
    $licenseKey = ShopSettings::get_license_key();
    $xml = <<<EOS
<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
<SwipeStripe>
	<License>$licenseKey</License>
</SwipeStripe>
EOS;
    echo $xml;
    exit;
  }

}