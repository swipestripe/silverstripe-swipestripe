<?php
/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details 
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class CheckoutForm extends Form {
  
  /**
   * The current {@link Order} 
   * 
   * @var Order
   */
  public $currentOrder;

  
  /**
   * Construct the form, get the grouped fields and set the fields for this form appropriately,
   * the fields are passed in an associative array so that the fields can be grouped into sets 
   * making it easier for the template to grab certain fields for different parts of the form.
   * 
   * @param Controller $controller
   * @param String $name
   * @param Array $groupedFields Associative array of fields grouped into sets
   * @param FieldList $actions
   * @param Validator $validator
   * @param Order $currentOrder
   */
  function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null, Order $currentOrder = null) {

		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setTemplate('CheckoutForm');
		$this->currentOrder = $currentOrder;
  }

  public function getShippingAddressFields() {
  	return $this->Fields()->fieldByName('ShippingAddress');
  }

  public function getBillingAddressFields() {
  	return $this->Fields()->fieldByName('BillingAddress');
  }

  public function getPersonalDetailsFields() {
  	return $this->Fields()->fieldByName('PersonalDetails');
  }

  public function getItemsFields() {
  	return $this->Fields()->fieldByName('ItemsFields')->FieldList();
  }

  public function getSubTotalModificationsFields() {
  	return $this->Fields()->fieldByName('SubTotalModificationsFields')->FieldList();
  }

  public function getTotalModificationsFields() {
  	return $this->Fields()->fieldByName('TotalModificationsFields')->FieldList();
  }

  public function getNotesFields() {
  	return $this->Fields()->fieldByName('NotesFields');
  }

  public function getPaymentFields() {
  	return $this->Fields()->fieldByName('PaymentFields');
  }
  
  /**
   * Helper function to return the current {@link Order}, used in the template for this form
   * 
   * @return Order
   */
  function Cart() {
    return $this->currentOrder;
  }
	
	/**
	 * Overloaded so that form error messages are displayed.
	 * 
	 * @see OrderFormValidator::php()
	 * @see Form::validate()
	 */
  function validate(){

		if($this->validator){
			$errors = $this->validator->validate();

			if ($errors){

				if (Director::is_ajax()) { // && $this->validator->getJavascriptValidationHandler() == 'prototype') {
				  
				  //Set error messages to form fields for display after form is rendered
				  $fields = $this->Fields();

				  foreach ($errors as $errorData) {
				    $field = $fields->dataFieldByName($errorData['fieldName']);
            if ($field) {
              $field->setError($errorData['message'], $errorData['messageType']);
              $fields->replaceField($errorData['fieldName'], $field);
            }
				  }
				} 
				else {
				
					$data = $this->getData();

					$formError = array();
					if ($formMessageType = $this->MessageType()) {
					  $formError['message'] = $this->Message();
					  $formError['messageType'] = $formMessageType;
					}

					// Load errors into session and post back
					Session::set("FormInfo.{$this->FormName()}", array(
						'errors' => $errors,
						'data' => $data,
					  'formError' => $formError
					));

				}
				return false;
			}
		}
		return true;
	}
}

