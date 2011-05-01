<?php
/**
 * Mixin to augment the Payment class
 * Payment statuses: Incomplete,Success,Failure,Pending
 * 
 * @author frankmullenger
 */
class PaymentDecorator extends DataObjectDecorator {

	function extraStatics() {

		return array(
			'has_one' => array(
				'Order' => 'Order' //Need to add Order here for ModelAdmin
			),
			'summary_fields' => array(
			  'ID' => 'Payment ID',
			  'SummaryAmount' => 'Amount',
			  'SummaryType' => 'Type',
			  'PaidBy.Name' => 'Customer'
			)
		);
	}

	/**
	 * Cannot create payments in the CMS
	 * 
	 * @see DataObjectDecorator::canCreate()
	 */
	function canCreate($member = null) {
		return false;
	}

	/**
	 * Cannot delete payments in the CMS
	 * 
	 * @see DataObjectDecorator::canDelete()
	 */
	function canDelete($member = null) {
		return false;
	}
	
	/**
	 * Helper to get a nicely formatted amount for this payment
	 * 
	 * @return String
	 */
	function SummaryAmount() {
	  return $this->owner->dbObject('Amount')->Nice();
	}
	
	/**
	 * Helper to get type of payment depending on payment class (system) used
	 * 
	 * @return String
	 */
	function SummaryType() {
	  return implode(' ', preg_split('/(?<=\\w)(?=[A-Z])/', $this->owner->ClassName));
	}
	
	/**
	 * Fields to display this payment in the CMS
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
  function updateCMSFields(FieldSet &$fields) {
    
    $fields->removeByName('Status');
    
    $toBeRemoved = array(
      'IP',
      'ProxyIP',
      'PaidForID',
      'PaidForClass',
      'PaymentDate',
      'ExceptionError',
      'Token',
      'PayerID',
      'RecurringPaymentID'
    );
	  foreach($toBeRemoved as $field) {
			$fields->removeByName($field);
		}
		
		$toBeReadOnly = array(
		  'TransactionID',
		  'PaidByID'
		);
		foreach ($toBeReadOnly as $field) {
		  if ($fields->fieldByName($field)) {
		    $fields->makeFieldReadonly($field);
		  }
		}
    
    return $fields;
	}

	/**
	 * After payment success process Order::onAfterPayment()
	 * 
	 * @see DataObjectDecorator::onAfterWrite()
	 */
	function onAfterWrite() {

	  $order = $this->owner->PaidObject();

		if($this->owner->Status == 'Success' && $order) {
		  $order->onAfterPayment();
		}
	}
}