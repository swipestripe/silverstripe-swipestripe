<?php
/**
 * Validator for editing {@link Product}s in the {@link ShopAdmin}. Currently not used.
 * 
 * @see Product::getCMSValidator()
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage form
 * @version 1.0
 */
class ProductAdminValidator extends RequiredFields {

	/**
	 * Currently not used
	 * 
	 * TODO could use this to validate variations etc. perhaps
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	function php($data) {

		$valid = parent::php($data);
		
		//$this->validationError("", "This is a test error message for the Title.", 'bad'); 
    //$valid = false; 
		
		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form The current form
	 */
	public function getForm() {
	  return $this->form;
	}
}