<?php

class OrderConfigDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'db' => array(
		    'EmailSignature' => 'HTMLText',
				'ReceiptSubject' => 'Varchar',
		    'ReceiptBody' => 'HTMLText',
		    'ReceiptFrom' => 'Varchar',
		    'PaidSubject' => 'Varchar',
		    'PaidBody' => 'HTMLText',
		    'PaidFrom' => 'Varchar'
			)
		);
	}

	/**
	 * Fields for sending receipts for orders basically
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {

    $fields->addFieldToTab("Root", new TabSet('SimpleCart')); 
    $fields->addFieldToTab("Root.SimpleCart", 
      new Tab('Email'),
      new Tab('ReceiptEmail'),
      new Tab('PaidEmail')
    );
    
    $fields->addFieldToTab('Root.SimpleCart.Email', new HtmlEditorField('EmailSignature', 'Signature for all emails', 15));
    
    $fields->addFieldToTab('Root.SimpleCart.ReceiptEmail', new EmailField('ReceiptFrom', 'Receipt email sender'));
    $fields->addFieldToTab('Root.SimpleCart.ReceiptEmail', new TextField('ReceiptSubject', 'Receipt email subject line'));
    $fields->addFieldToTab('Root.SimpleCart.ReceiptEmail', new HtmlEditorField('ReceiptBody', 'Receipt email body', 15));
    
    $fields->addFieldToTab('Root.SimpleCart.PaidEmail', new EmailField('PaidFrom', 'Paid email sender'));
    $fields->addFieldToTab('Root.SimpleCart.PaidEmail', new TextField('PaidSubject', 'Paid email subject line'));
    $fields->addFieldToTab('Root.SimpleCart.PaidEmail', new HtmlEditorField('PaidBody', 'Paid email body', 15));
	}

}