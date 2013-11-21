<?php
/**
 * Mixin to augment the {@link Payment} class.
 * Payment statuses: Incomplete,Success,Failure,Pending
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Payment_Extension extends DataExtension {

	private static $has_one = array(
		'Order' => 'Order' //Need to add Order here for ModelAdmin
	);

	private static $summary_fields = array(
		'Status' => 'Status',
		'SummaryOfAmount' => 'Amount',
		'Method' => 'Method',
		'PaidBy.Name' => 'Customer'
	);

	/**
	 * Cannot create {@link Payment}s in the CMS.
	 * 
	 * @see DataObjectDecorator::canCreate()
	 * @return Boolean False always
	 */
	function canCreate($member = null) {
		return false;
	}

	/**
	 * Cannot delete {@link Payment}s in the CMS.
	 * 
	 * @see DataObjectDecorator::canDelete()
	 * @return Boolean False always
	 */
	function canDelete($member = null) {
		return false;
	}
	
	/**
	 * Helper to get a nicely formatted amount for this {@link Payment}
	 * 
	 * @return String Payment amount formatted with Nice()
	 */
	function SummaryOfAmount() {
		return $this->owner->dbObject('Amount')->Nice();
	}
	
	/**
	 * Fields to display this {@link Payment} in the CMS, removed some of the 
	 * unnecessary fields.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 * @return FieldList
	 */
	function updateCMSFields(FieldList $fields) {

		$fields->removeByName('OrderID');
		$fields->removeByName('HTTPStatus');
		$fields->removeByName('Amount');

		$str = $this->owner->dbObject('Amount')->Nice();
		$fields->insertBefore(TextField::create('Amount_', 'Amount', $str), 'Method');

		return $fields;
	}

	/**
	 * After payment success process onAfterPayment() in {@link Order}.
	 * 
	 * @see Order::onAfterPayment()
	 * @see DataObjectDecorator::onAfterWrite()
	 */
	function onAfterWrite() {

		$order = $this->owner->Order();

		if ($order && $order->exists()) {
			$order->PaymentStatus = ($order->getPaid()) ? 'Paid' : 'Unpaid';
			$order->write();
		}
	}
}

class Payment_ProcessorExtension extends Extension {

	public function onBeforeRedirect() {

		$order = $this->owner->payment->Order();
		if ($order && $order->exists()) {
			$order->onAfterPayment();
		}
	}
}