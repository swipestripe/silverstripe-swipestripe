<?php

class OrderFormValidator extends RequiredFields {
  
  protected $items;
  protected $modifiers;
  
  function addItemField($field) {
		$this->items[] = $field;
	}

	/**
	 * Check that current order is valid
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function php($data) {

	  //Order can be invalid
	  //Each item can be invalid
	  //Shipping could be invalid according to shipping country
	  
		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//TODO Validate the current order, if some items are not valid then put errors into the session for 
		//corresponding item fields in the checkout form
		//Can apply the same approach to order modifier fields
		
		$currentOrder = CartControllerExtension::get_current_order();
		$items = $currentOrder->Items();

		foreach ($this->items as $fieldName) {

		  $formField = $fields->dataFieldByName($fieldName);
		  
		  //Make sure item is in the order
		  //make sure the item is valid
		  $itemID  = str_replace('OrderItem', '', $fieldName);
		  
		  if ($itemID && is_numeric($itemID)) {
		     $item = $items->find('ID', $itemID);
		  }
		  
		  if (!$item || !$item->exists()) {
		    
		    $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This item is not in the Order.');
				if($msg = $formField->getCustomValidationMessage()) {
					$errorMessage = $msg;
				}
		    
		    $this->validationError(
					$fieldName,
					$errorMessage,
					"required"
				);
				$valid = false;
		  }
		  
		  
		  $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This item is not in the Order.');
			if($msg = $formField->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
	    
	    $this->validationError(
				$fieldName,
				$errorMessage,
				"required"
			);
			$valid = false;

		}
		
		/*
		$currentOrder = CartControllerExtension::get_current_order();
	  if (!$currentOrder || !$currentOrder->isValid()) {
		  //Get current order invalid products and add a validationError for each product
		}
		*/
		
		return $valid;
	}

}