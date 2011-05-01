<?php

class OrderConfigDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'db' => array(
				'ReceiptSubject' => 'Varchar',
		    'ReceiptBody' => 'Text',
		    'ReceiptFrom' => 'Varchar'
			)
		);
	}

	/**
	 * Fields for sending receipts for orders basically
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {

    $fields->addFieldToTab("Root", new Tab('SimpleCart')); 
    $fields->addFieldToTab('Root.SimpleCart', new EmailField('ReceiptFrom', 'Receipt sender'));
    $fields->addFieldToTab('Root.SimpleCart', new TextField('ReceiptSubject', 'Receipt email subject line'));
    $fields->addFieldToTab('Root.SimpleCart', new TextareaField('ReceiptBody', 'Receipt email body', 15));
	}

}