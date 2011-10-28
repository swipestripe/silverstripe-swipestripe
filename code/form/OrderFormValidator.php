<?php

class OrderFormValidator extends RequiredFields {

	/**
	 * Check that current order is valid
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function php($data) {
		$valid = parent::php($data);
		
		//TODO Validate the current order, if some items are not valid then put errors into the session for 
		//corresponding item fields in the checkout form
		//Can apply the same approach to order modifier fields
		
	  if (!$currentOrder || !$currentOrder->isValid()) {
		  //Get current order invalid products and add a validationError for each product
		}
		
		return $valid;

		/*
		$currentOrder = CartControllerExtension::get_current_order();
		
		//Testing
		$valid = false;
		if ($currentOrder) {
		  
		  $items = $currentOrder->Items();
		  SS_Log::log(new Exception(print_r($items->map(), true)), SS_Log::NOTICE);
		  
		  if ($items) foreach ($items as $item) {
		    $this->validationError(
    			'ItemRow' . $item->ID,
    			'This item is no longer published.',
    			'error',
		      $item->ID
    		);
		  }
		}
		
		if (!$currentOrder || !$currentOrder->isValid()) {
		  //Get current order invalid products and add a validationError for each product
		}
		

		//Debug::friendlyError();
		//Debug::showError();

		return $valid;
		*/
	}
	
	/*
  function validationError($fieldName, $message, $messageType='', $itemID = null) {
    
    if ($itemID) {
      $this->errors[] = array(
  			'fieldName' => $fieldName,
  			'message' => $message,
  			'messageType' => $messageType,
        'itemID' => $itemID
  		); 
    }
    else {
      parent::validationError($fieldName, $message, $messageType);
    }
	}
	*/
	
}