<?php

class CartConfigDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'db' => array(
		    'EmailSignature' => 'HTMLText',
				'ReceiptSubject' => 'Varchar',
		    'ReceiptBody' => 'HTMLText',
		    'ReceiptFrom' => 'Varchar',
		    'PaidSubject' => 'Varchar',
		    'PaidBody' => 'HTMLText',
		    'PaidFrom' => 'Varchar',
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

    $fields->addFieldToTab("Root", new TabSet('StripeyCart')); 
    $fields->addFieldToTab("Root.StripeyCart", 
      new TabSet('Emails')
    );
    $fields->addFieldToTab("Root.StripeyCart.Emails", 
      new Tab('Email'),
      new Tab('ReceiptEmail'),
      new Tab('PaidEmail'),
      new Tab('OrderEmail')
    );
    
    $fields->addFieldToTab('Root.StripeyCart.Emails.Email', new HtmlEditorField('EmailSignature', 'Signature for all emails', 15));
    
    $fields->addFieldToTab('Root.StripeyCart.Emails.ReceiptEmail', new EmailField('ReceiptFrom', 'Receipt email sender'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.ReceiptEmail', new TextField('ReceiptSubject', 'Receipt email subject line'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.ReceiptEmail', new HtmlEditorField('ReceiptBody', 'Receipt email body', 15));
    
    $fields->addFieldToTab('Root.StripeyCart.Emails.PaidEmail', new EmailField('PaidFrom', 'Paid email sender'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.PaidEmail', new TextField('PaidSubject', 'Paid email subject line'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.PaidEmail', new HtmlEditorField('PaidBody', 'Paid email body', 15));
    
    $fields->addFieldToTab('Root.StripeyCart.Emails.OrderEmail', new EmailField('OrderTo', 'Order email recipient'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.OrderEmail', new TextField('OrderSubject', 'Order email subject line'));
    $fields->addFieldToTab('Root.StripeyCart.Emails.OrderEmail', new HtmlEditorField('OrderBody', 'Order email body', 15));
	}

}