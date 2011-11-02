<?php
/**
 * 
 * @author frankmullenger
 * 
 * Summary of tests:
 * -----------------
 * 
 * 
 * TODO
 * ----
 * 
 * Product Category
 * delete product, does not appear on website
 * delete product, staging versions all up to date and still exist
 * new version of product created when amount changed
 * variations disabled when new attribute added
 * add new variation
 * 
 */
class ProductTest extends FunctionalTest {
  
	static $fixture_file = 'stripeycart/tests/CartTest.yml';
	static $disable_themes = false;
	static $use_draft_site = true;
	
  function setUp() {
		parent::setUp();
	}
	
	/**
	 * Log current member out by clearing session
	 */
	function logOut() {
	  $this->session()->clear('loggedInAs');
	}
	
	/**
	 * Helper to get data from a form.
	 * 
	 * @param String $formID
	 * @return Array
	 */
	function getFormData($formID) {
	  $page = $this->mainSession->lastPage();
	  $data = array();
	  
	  if ($page) {
			$form = $page->getFormById($formID);
			if (!$form) user_error("Function getFormData() failed to find the form {$formID}", E_USER_ERROR);

  	  foreach ($form->_widgets as $widget) {
  
  	    $fieldName = $widget->getName();
  	    $fieldValue = $widget->getValue();
  	    
  	    $data[$fieldName] = $fieldValue;
  	  }
	  }
	  else user_error("Function getFormData() called when there is no form loaded.  Visit the page with the form first", E_USER_ERROR);
	  
	  return $data;
	}

}