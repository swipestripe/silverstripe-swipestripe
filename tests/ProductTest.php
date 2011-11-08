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
 * correct options for variations returned on product page on first, second and third attribute dropdowns
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
	 * Load the project page and test the first select for correct product options
	 * 
	 * # Teeshirt Variations
	 * # Small, Red, Cotton
	 * # Small, Red, Polyester
	 * # Small, Purple, Cotton
	 * # Small, Purple, Polyester
	 * #
	 * # Medium, Purple, Cotton
	 * # Medium, Purple, Silk
	 * #
	 * # Extra Large, Red, Cotton
	 * # Extra Large, Red, Polyester
	 * # Extra Large, Purple, Cotton
	 */
  function testProductOptionsFirstSet() {

	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $attributes = $teeshirtA->Attributes();
	  $options = $teeshirtA->Options();
	  $variations = $teeshirtA->Variations();
	  
	  $this->loginAs('admin');
    $teeshirtA->doPublish();	  
	  $this->logOut();
	  
	  $this->loginAs('buyer');
	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  //Check that options fields exist for each attribute
	  $attributeOptionsMap = array();
	  $firstAttributeID = null;
	  foreach ($attributes as $attribute) {
	    
	    if (!$firstAttributeID) $firstAttributeID = $attribute->ID;
	    
	    $this->assertPartialMatchBySelector('#Options['.$attribute->ID.']', 1);
	    
	    $options = $teeshirtA->getOptionsForAttribute($attribute->ID);
	    $attributeOptionsMap[$attribute->ID] = $options->map();
	  }
    
	  
	  //Check that first option select has valid options in it
	  $tempAttributeOptionsMap = $attributeOptionsMap;
	  $firstAttributeOptions = array_shift($tempAttributeOptionsMap);
	  
	  $productPage = new DOMDocument();
	  $productPage->loadHTML($this->mainSession->lastContent());
	  //echo $productPage->saveHTML();

	  //Find the options for the first attribute select
	  $selectFinder = new DomXPath($productPage);
	  $firstAttributeSelectID = 'AddToCartForm_AddToCartForm_Options-'.$firstAttributeID;
	  $firstSelect = $selectFinder->query("//select[@id='$firstAttributeSelectID']");
	  
	  foreach ($firstSelect as $node) {

	    $tmp_doc = new DOMDocument(); 
      $tmp_doc->appendChild($tmp_doc->importNode($node, true));        
      $innerHTML = $tmp_doc->saveHTML();

      $optionFinder = new DomXPath($tmp_doc);

  	  if ($firstAttributeOptions) foreach ($firstAttributeOptions as $optionID => $optionTitle) {
  	    $options = $optionFinder->query("//option[@value='$optionID']");
  	    $this->assertEquals(1, $options->length);
  	  }
	  }
  }
  
  /**
   * Post add to cart form and retreive second set of product options
   */
  function testProductOptionsSecondSet() {
    
    $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
    $attributes = $teeshirtA->Attributes();
    $options = $teeshirtA->Options();
    $variations = $teeshirtA->Variations();
    
    $this->loginAs('admin');
    $teeshirtA->doPublish();	  
    $this->logOut();
    
    $this->loginAs('buyer');
    $this->get(Director::makeRelative($teeshirtA->Link())); 
    
    $data = $this->getFormData('AddToCartForm_AddToCartForm');
    unset($data['Options[2]']);
    unset($data['Options[3]']);
    unset($data['Options[1]']);
    
    $data['Options'][2] = 12;
    $data['NextAttributeID'] = 3;
    
    $this->post(
      Director::absoluteURL($teeshirtA->Link() . '/options/'),
      $data
    );
    
    $decoded = json_decode($this->mainSession->lastContent());
    
    $expected = array(
      '14' => 'Cotton',
      '15' => 'Polyester'
    );
    $actual = array();
    foreach ($decoded->options as $optionID => $optionName) {
      $actual[$optionID] = $optionName;
    }
    $this->assertEquals($expected, $actual);
  }
  
  /**
   * Post add to cart form and retreive third set of product options
   */
  function testProductOptionsThirdSet() {
    
    $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $attributes = $teeshirtA->Attributes();
	  $options = $teeshirtA->Options();
	  $variations = $teeshirtA->Variations();
	  
	  $this->loginAs('admin');
    $teeshirtA->doPublish();	  
	  $this->logOut();
	  
	  $this->loginAs('buyer');
	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
    $data = $this->getFormData('AddToCartForm_AddToCartForm');
	  unset($data['Options[2]']);
	  unset($data['Options[3]']);
	  unset($data['Options[1]']);
	  
	  $data['Options'][2] = 12;
	  $data['Options'][3] = 14;
	  $data['NextAttributeID'] = 1;
	  
	  $this->post(
	    Director::absoluteURL($teeshirtA->Link() . '/options/'),
	    $data
	  );
	  
	  $decoded = json_decode($this->mainSession->lastContent());
	  
	  $expected = array(
	    '9' => 'Small',
	    '11' => 'Extra Large'
	  );
	  $actual = array();
	  foreach ($decoded->options as $optionID => $optionName) {
	    $actual[$optionID] = $optionName;
	  }
	  $this->assertEquals($expected, $actual);
  }
	
}