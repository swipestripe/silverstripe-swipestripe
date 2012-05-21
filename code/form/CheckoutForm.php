<?php
/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details 
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
 */
class CheckoutForm extends Form {
  
  /**
   * The current {@link Order} 
   * 
   * @var Order
   */
  public $currentOrder;
  
  /**
   * Fields for this form are grouped in sets, they are stored in an array so that the template
   * can pull out a set of fields for a different part of the form.
   * 
   * @var Array 
   */
  protected $groupedFields = array();
  
  /**
   * Set of extra fields set for this form, such as csrf token etc.
   * 
   * @var FieldSet
   */
  private $extraFieldsSet;
  
  /**
   * Construct the form, get the grouped fields and set the fields for this form appropriately,
   * the fields are passed in an associative array so that the fields can be grouped into sets 
   * making it easier for the template to grab certain fields for different parts of the form.
   * 
   * @param Controller $controller
   * @param String $name
   * @param Array $groupedFields Associative array of fields grouped into sets
   * @param FieldSet $actions
   * @param Validator $validator
   * @param Order $currentOrder
   */
  function __construct($controller, $name, $groupedFields, FieldSet $actions, $validator = null, Order $currentOrder = null) {
    
    //Send fields in as associative array, then loop through and add to $fields array for parent constructuor
    //Overload the Fields() method to get fields for specific areas of the form
    
    $this->groupedFields = $groupedFields;
    
    $fields = new FieldSet();
    if (is_array($groupedFields)) foreach ($groupedFields as $setName => $setFields) {
      foreach ($setFields as $field) $fields->push($field);
    }
    else if ($groupedFields instanceof FieldSet) $fields = $groupedFields;
    
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setTemplate('CheckoutForm');
		$this->currentOrder = $currentOrder;
		$this->extraFieldsSet = new FieldSet();
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
	 * Return the forms fields for the template, but filter the fields for 
	 * a particular 'set' of fields.
	 * 
	 * @return FieldSet The form fields
	 */
	function Fields($set = null) {

	  if ($set) {
	    $fields = new FieldSet();
		
  		//TODO fix this, have to disable security token for now @see CheckoutPage::OrderForm()
  	  foreach ($this->getExtraFields() as $field) {
  			if (!$this->extraFieldsSet->fieldByName($field->Name())) {
  			  $this->extraFieldsSet->push($field);
  			  $fields->push($field);
  			}
  		}
  
  		if ($set && isset($this->groupedFields[$set])) {
  
  		  if (is_array($this->groupedFields[$set])) foreach ($this->groupedFields[$set] as $field) {
  		    $fields->push($field);
  		  }
  		  else $fields->push($this->groupedFields[$set]);
  		}
  		return $fields;
	  }
	  else return parent::Fields(); //For the validator to get fields
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
			  
			  //SS_Log::log(new Exception(print_r($errors, true)), SS_Log::NOTICE);

				if (Director::is_ajax() && $this->validator->getJavascriptValidationHandler() == 'prototype') {
				  
				  //Set error messages to form fields for display after form is rendered
				  $fields = $this->Fields();

				  foreach ($errors as $errorData) {
				    $field = $fields->dataFieldByName($errorData['fieldName']);
				    $field->setError($errorData['message'], $errorData['messageType']);
				    $fields->replaceField($errorData['fieldName'], $field);
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

