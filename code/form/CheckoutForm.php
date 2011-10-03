<?php
class CheckoutForm extends Form {
  
  public $currentOrder;
  protected $groupedFields = array();
  
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
  }
  
  function Cart() {
    return $this->currentOrder;
  }
  
	/**
	 * Return the form's fields - used by the templates
	 * 
	 * @return FieldSet The form fields
	 */
	function Fields($set = null) {

		foreach($this->getExtraFields() as $field) {
			if(!$this->fields->fieldByName($field->Name())) $this->fields->push($field);
		}
		return $this->fields;
	}

}