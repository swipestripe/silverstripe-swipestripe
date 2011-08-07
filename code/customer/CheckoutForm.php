<?php
class CheckoutForm extends Form {
  
  public $currentOrder;
  
  function __construct($controller, $name, FieldSet $fields, FieldSet $actions, $validator = null, Order $currentOrder = null) {
    
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setTemplate('CheckoutForm');
		$this->currentOrder = $currentOrder;
  }
  
  function Cart() {
    return $this->currentOrder;
  }
  
  function PopQuantityField() {
    $fields = $this->Fields();
    
    $quantityField = $fields->pop();

    SS_Log::log(new Exception(print_r($quantityField->Name(), true)), SS_Log::NOTICE);
    SS_Log::log(new Exception(print_r('to here?', true)), SS_Log::NOTICE);
    
    return $quantityField;
  }

}