<?php
class OrderFormValidator extends RequiredFields {
  
  protected $items = array();
  protected $modifiers = array();
  
  function addItemField($field) {
		$this->items[] = $field;
	}
	
	function addModifierField($field) {
	  $this->modifiers[] = $field;
	}

	/**
	 * Check that current order is valid
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function php($data) {

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		$currentOrder = CartControllerExtension::get_current_order();
		$items = $currentOrder->Items();

		//Check the validity of item fields
		foreach ($this->items as $fieldName) {

		  $formField = $fields->dataFieldByName($fieldName);
		  
		  //Make sure item is in the order
		  //make sure the item is valid
		  $itemID  = str_replace('OrderItem', '', $fieldName);
		  
		  //TODO use OrderItemField->getItemID();
		  
		  if ($itemID && is_numeric($itemID)) {
		     $item = $items->find('ID', $itemID);
		  }
		  
		  //Check that item exists
		  if (!$item || !$item->exists()) {
		    
		    $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Order.');
				if ($msg = $formField->getCustomValidationMessage()) {
					$errorMessage = $msg;
				}
		    
		    $this->validationError(
					$fieldName,
					$errorMessage,
					"error"
				);
				$valid = false;
		  }
		  
		  //Check item is valid
		  if (!$item->isValid()) {
		    
		    $errorMessage = _t('Form.ITEM_IS_NOT_VALID', 'Sorry, this product is no longer available.');
				if ($msg = $formField->getCustomValidationMessage()) {
					$errorMessage = $msg;
				}
		    
		    $this->validationError(
					$fieldName,
					$errorMessage,
					"error"
				);
				$valid = false;
		  }
		}
		
		//Check the validity of order modifiers
		foreach ($this->modifiers as $fieldName) {
		  
		  $formField = $fields->dataFieldByName($fieldName);
		  
		  //TODO use ModifierSetField->getClassName();
		  
		  //$className = str_replace('OrderItem', '', $fieldName);
		  
		  SS_Log::log(new Exception(print_r($fieldName, true)), SS_Log::NOTICE);
		  SS_Log::log(new Exception(print_r($data, true)), SS_Log::NOTICE);
		}
		
		$this->errors[] = true;
		$valid = false;
		
		//Check the order is valid
		$currentOrder = CartControllerExtension::get_current_order();
	  if (!$currentOrder || !$currentOrder->isValid()) {

	    $this->form->sessionMessage(
  		  _t('Form.ORDER_IS_NOT_VALID', 'There seems to be a problem with your order, are there products in your cart?'),
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
		}

		return $valid;
	}
}