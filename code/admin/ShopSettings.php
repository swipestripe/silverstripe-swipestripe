<?php

class ShopSettings extends DataObjectDecorator {
  
  private static $license_key;
  
  public static function set_license_key($key) {
    self::$license_key = $key;
  }

	function extraStatics() {

		return array(
			'db' => array(
		    'EmailSignature' => 'HTMLText',
				'ReceiptSubject' => 'Varchar',
		    'ReceiptBody' => 'HTMLText',
		    'ReceiptFrom' => 'Varchar',
		    //'PaidSubject' => 'Varchar',
		    //'PaidBody' => 'HTMLText',
		    //'PaidFrom' => 'Varchar',
				'OrderSubject' => 'Varchar',
		    'OrderBody' => 'HTMLText',
		    'OrderTo' => 'Varchar'
			)
		);
	}

	/**
	 * Fields for sending receipts for orders basically
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
      new Tab('Email'),
      new Tab('ReceiptEmail'),
      //new Tab('PaidEmail'),
      new Tab('OrderEmail')
    );
    $fields->addFieldToTab('Root.Shop.Emails.Email', new HtmlEditorField('EmailSignature', 'Signature for all emails', 15));
    
    $fields->addFieldToTab('Root.Shop.Emails.ReceiptEmail', new EmailField('ReceiptFrom', 'Receipt email sender'));
    $fields->addFieldToTab('Root.Shop.Emails.ReceiptEmail', new TextField('ReceiptSubject', 'Receipt email subject line'));
    $fields->addFieldToTab('Root.Shop.Emails.ReceiptEmail', new HtmlEditorField('ReceiptBody', 'Receipt email body', 15));
    
    //$fields->addFieldToTab('Root.Shop.Emails.PaidEmail', new EmailField('PaidFrom', 'Paid email sender'));
    //$fields->addFieldToTab('Root.Shop.Emails.PaidEmail', new TextField('PaidSubject', 'Paid email subject line'));
    //$fields->addFieldToTab('Root.Shop.Emails.PaidEmail', new HtmlEditorField('PaidBody', 'Paid email body', 15));
    
    $fields->addFieldToTab('Root.Shop.Emails.OrderEmail', new EmailField('OrderTo', 'Order email recipient'));
    $fields->addFieldToTab('Root.Shop.Emails.OrderEmail', new TextField('OrderSubject', 'Order email subject line'));
    $fields->addFieldToTab('Root.Shop.Emails.OrderEmail', new HtmlEditorField('OrderBody', 'Order email body', 15));
	}

}