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
    if (is_array($groupedFields)) foreach ($groupedFields as $setName => $compositeField) {
      $fields->push($compositeField);
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

		$fields = new FieldSet();
		
	  foreach($this->getExtraFields() as $field) {
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

}