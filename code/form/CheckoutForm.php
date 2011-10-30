<?php
class CheckoutForm extends Form {
  
  public $currentOrder;
  protected $groupedFields = array();
  private $extraFieldsSet;
  
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
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 *
	function setupFormErrors() {
    
		$errorInfo = Session::get("FormInfo.{$this->FormName()}");

		if (isset($errorInfo['errors']) && is_array($errorInfo['errors'])) {
			foreach ($errorInfo['errors'] as $error) {
			  
				$field = $this->fields->dataFieldByName($error['fieldName']);

				if (!$field) {
					$errorInfo['message'] = $error['message'];
					$errorInfo['type'] = $error['messageType'];
				} 
				else {
					$field->setError($error['message'], $error['messageType']);
				}
			}

			// load data in from previous submission upon error
			if(isset($errorInfo['data'])) $this->loadDataFrom($errorInfo['data']);
		}

		if (isset($errorInfo['message']) && isset($errorInfo['type'])) {
			$this->setMessage($errorInfo['message'], $errorInfo['type']);
		}
	}
	
	/**
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 *
	function setupFormErrors() {
	  
	  parent::setupFormErrors();
	  
		$errorInfo = Session::get("FormInfo.{$this->FormName()}");
		
		SS_Log::log(new Exception(print_r($errorInfo, true)), SS_Log::NOTICE);
		
		if(isset($errorInfo['errors']) && is_array($errorInfo['errors'])) {
		  
		  $order = CartControllerExtension::get_current_order();
		  
			foreach($errorInfo['errors'] as $error) {
			  
			  if (isset($error['itemID'])) {
			    $item = DataObject::get_one('Item', 'Item.ID = ' . Convert::raw2sql($error['itemID']) . ' AND Item.OrderID = ' . $order->ID);
			    
			    if ($item) {
			      $item->setError($error['message'], $error['messageType']);
			    }
			  }
			}
		}

	}
	*/

}