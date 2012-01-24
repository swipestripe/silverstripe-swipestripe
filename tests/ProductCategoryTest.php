<?php
/**
 * Testing {@link Product} attributes and options on product pages.
 * 
 * Summary of tests:
 * -----------------
 * 
 * TODO
 * ----
 * check that getting a product category gets the products within it
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 * @version 1.0
 */
class ProductCategoryTest extends FunctionalTest {
  
	static $fixture_file = 'swipestripe/tests/Shop.yml';
	static $disable_themes = true;
	static $use_draft_site = false;
	
  function setUp() {
		parent::setUp();
		
		$category = $this->objFromFixture('ProductCategory', 'general');
		$this->assertTrue(is_numeric($category->ID));
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

	function testProductCategoryProducts() {
	  $category = $this->objFromFixture('ProductCategory', 'general');
	  $productA = $this->objFromFixture('Product', 'productA');
	  $productB = $this->objFromFixture('Product', 'productB');
	  
	  $this->loginAs('admin');
	  $category->doPublish();
	  $productA->doPublish();
	  $productB->doPublish();
	  $this->logOut();

    $this->assertEquals(2, $category->Products()->count());
    
    $doSet = DataObject::get( 
       'Product', 
       "\"ProductCategory_Products\".\"ProductCategoryID\" = '".$category->ID."' OR \"ParentID\" = '".$category->ID."'", 
       "Created DESC", 
       "LEFT JOIN \"ProductCategory_Products\" ON \"ProductCategory_Products\".\"ProductID\" = \"Product\".\"ID\"",
       "0, 3"
    );
    $this->assertEquals(2, $doSet->count());
	}
	
}