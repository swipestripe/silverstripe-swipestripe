<?php
/**
 * 
 * @author frankmullenger
 * 
 * Summary of tests:
 * -----------------
 * delete product, is unpublished, versions still exist
 * new version of product created when amount changed
 * variations disabled when new attribute added
 * correct options for variations returned on product page
 * 
 * TODO
 * ----
 * add new variation
 * add product to parent page, check URL works
 * add product to multiple categories, check that it appears on each
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
	
	/**
	 * Try to publish a product with amount changed
	 */
	function testChangeProductAmount() {
	  
	  $this->loginAs('admin');
	  $productA = $this->objFromFixture('Product', 'productA');
	  $productID = $productA->ID; 
	  
	  //Publish
	  $productA->doPublish();
	  $this->assertTrue($productA->isPublished());

	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
	  $versionsAfterPublished = array();
	  foreach ($versions as $versionRow) $versionsAfterPublished[] = $versionRow;

	  $originalAmount = $productA->Amount;
	  
	  $newAmount = new Money();
	  $newAmount->setAmount($originalAmount->getAmount() + 50);
	  $newAmount->setCurrency($originalAmount->getCurrency());
	  
	  $this->assertTrue($newAmount->Amount != $originalAmount->Amount);
	  
    //Update price and publish
	  $productA->Amount = $newAmount;
	  $productA->doPublish();

	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productID);
	  $versionsAfterPriceChange = array();
	  foreach ($versions as $versionRow) $versionsAfterPriceChange[] = $versionRow;

	  $this->assertTrue(count($versionsAfterPublished) + 1 == count($versionsAfterPriceChange));
	  $this->assertEquals($versionsAfterPriceChange[2]['AmountAmount'], $newAmount->getAmount());
	}
	
	/**
	 * Try adding a new attribute to a product, existing variations that do not have an option set for 
	 * the new attribute should be disabled
	 */
	function testVariationsDisabledAfterAttributeAdded() {
	  
	  $this->loginAs('admin');
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $variations = $teeshirtA->Variations();
	  
	  $this->assertTrue($variations->exists());
	  
	  foreach ($variations as $variation) {
	    $this->assertTrue($variation->isEnabled());
	  }
	  
	  //Add an attribute
	  $cutAttribute = $this->objFromFixture('Attribute', 'attrCut');
	  $existingAttributes = $teeshirtA->getManyManyComponents('Attributes');
	  $existingAttributes->add($cutAttribute);

	  $teeshirtA->writeComponents();

	  //Add the default options for the new attribute
	  $existingOptions = $teeshirtA->getComponents('Options');
	  $defaultOptions = DataObject::get('Option', "ProductID = 0 AND AttributeID = 4");
	  
	  foreach ($defaultOptions as $option) {
	    $existingOptions->add($option);
	  }
	  $teeshirtA->writeComponents();

	  $teeshirtA->write();

	  foreach ($teeshirtA->Variations() as $variation) {
	    $this->assertTrue(!$variation->isEnabled());
	  }
	}
	
	/**
	 * Try getting options for a product, must be valid options for product variations
	 */
  function testValidProductOptions() {

	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');

	  $this->loginAs('admin');
    $teeshirtA->doPublish();	  
	  $this->logOut();
	  
	  $this->loginAs('buyer');

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  echo $this->mainSession->lastContent();
	  
	  //Check that first option select has valid options in it
	  
	  //Post data of first option to Product->options() and check the result
	  
	  //Post data of first and second option and check the result
	  
	  //Change first option value, post data and check the result
	  
	  //Change second option value, post data and check the result
  }
}