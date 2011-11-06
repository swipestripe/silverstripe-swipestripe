<?php
class CartFormValidator extends RequiredFields {
	
	/**
	 * Helper so that form fields can access the form and current form data
	 */
	public function getForm() {
	  return $this->form;
	}
}