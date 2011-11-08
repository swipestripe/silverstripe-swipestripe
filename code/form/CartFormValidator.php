<?php
/**
 * Validator for the {@link CartForm}, not really necessary
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage form
 * @version 1.0
 */
class CartFormValidator extends RequiredFields {
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form The current form
	 */
	public function getForm() {
	  return $this->form;
	}
}