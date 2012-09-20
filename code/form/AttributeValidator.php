<?php
/**
 * Validator for editing {@link Attribute}s in the {@link ShopAdmin}. 
 * 
 * @see Attribute:getCMSValidator()
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class AttributeValidator extends RequiredFields {

	/**
	 * Check that an {@link Attribute} Title is unique.
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	function php($data) {

		$valid = parent::php($data);
		
		$newTitle = (isset($data['Title'])) ? $data['Title'] : null;
		if ($newTitle) {
		  
		  //$existingTitles = DataObject::get('Attribute');
		  $existingTitles = Attribute::get();
		  $existingTitles = $existingTitles->map('ID', 'Title')->toArray();
		  
		  if (isset($data['ID'])) unset($existingTitles[$data['ID']]);
		  
		  if (in_array($newTitle, $existingTitles)) {
		    $valid = false;
		    $this->validationError("Title", "Title already exists, please choose a different one.", 'bad'); 
		  }
		}

		
		/*
		//If invalid tidy up empty Attributes in the DB
		if (!$valid) {
  		$emptyAttributes = DataObject::get(
  			'Attribute', 
  			'"Attribute"."Title" IS NULL AND "Attribute"."Label" IS NULL'
  		);
  		if ($emptyAttributes && $emptyAttributes->exists()) foreach ($emptyAttributes as $attr) {
  		  $attr->delete();
  		}
		}
		*/

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
