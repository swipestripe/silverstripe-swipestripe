<?php
/**
 * Validate the {@link OrderForm}, check that the current {@link Order} is valid.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class OrderFormValidator extends RequiredFields {

	/**
	 * Check that current order is valid
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	function php($data) {
	  
	  //TODO move the form error messages to CheckoutForm::validate()

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check the order is valid
		$currentOrder = CartControllerExtension::get_current_order();
		if (!$currentOrder) {
		  $this->form->sessionMessage(
  		  _t('Form.ORDER_IS_NOT_VALID', 'Your cart seems to be empty, please add an item from the shop'),
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
		}
		else {
		  $validation = $currentOrder->validateForCart();
		  
		  if (!$validation->valid()) {
		    
		    $this->form->sessionMessage(
    		  _t('Form.ORDER_IS_NOT_VALID', 'There seems to be a problem with your order. ' . $validation->message()),
    		  'bad'
    		);
    		
    		//Have to set an error for Form::validate()
    		$this->errors[] = true;
    		$valid = false;
		  }
		}
		
		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form
	 */
	public function getForm() {
	  return $this->form;
	}
}