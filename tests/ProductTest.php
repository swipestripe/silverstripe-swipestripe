<?php
/**
 * 
 * @author frankmullenger
 * 
 * Summary of tests:
 * -----------------
 * delete product, is unpublished, versions still exist
 * 
 * 
 * TODO
 * ----
 * 
 * new version of product created when amount changed
 * variations disabled when new attribute added
 * add new variation
 * add product to parent page
 * add product to multiple categories?
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
	
	/**
	 * Try to delete a product, make sure it is unpublished but that versions remain the same
	 */
	function testDeleteProduct() {
	  
	  $this->loginAs('admin');
	  $productA = $this->objFromFixture('Product', 'productA');
	  $productID = $productA->ID; 
	  
	  //Publish
	  $productA->doPublish();
	  $this->assertTrue($productA->isPublished());

	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
	  $versionsAfterPublished = array();
	  foreach ($versions as $versionRow) $versionsAfterPublished[] = $versionRow;

	  
    //Delete
	  $productA->delete();
	  $this->assertTrue(!$productA->isPublished());

	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
	  $versionsAfterDelete = array();
	  foreach ($versions as $versionRow) $versionsAfterDelete[] = $versionRow;
	  
	  $this->assertTrue($versionsAfterPublished == $versionsAfterDelete);
	  
	  
	  //$versions = DB::query('SELECT * FROM "SiteTree_Live" WHERE "ID" = ' . $productID);
	}

}