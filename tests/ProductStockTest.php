<?php
/**
 * Testing {@link Product} stock, updating stock levels when products
 * added, removed and carts are deleted by scheduled tasks.
 * 
 * Summary of tests:
 * -----------------
 * add product to cart, stock is reduced in product without creating a new version of product
 * remove product from cart, stock is replenished for product
 * add product variation to cart, stock is reduced for product variation
 * remove product variation from cart, stock is replenished for product variation
 * 
 * TODO
 * ----
 * scheduled task deletes order and associated objects, replenishes stock
 * stock level cannot go below -1
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage tests
 * @version 1.0
 */
class ProductStockTest extends FunctionalTest {
  
	static $fixture_file = 'shop/tests/Shop.yml';
	static $disable_themes = false;
	static $use_draft_site = false;
	
  function setUp() {
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
		
		//Need to publish a few pages because not using the draft site
		$checkoutPage = $this->objFromFixture('CheckoutPage', 'checkout');  
		$accountPage = $this->objFromFixture('AccountPage', 'account');
		$cartPage = $this->objFromFixture('CartPage', 'cart');
		
		$this->loginAs('admin');
	  $checkoutPage->doPublish();
	  $accountPage->doPublish();
	  $cartPage->doPublish();
	  $this->logOut();
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
   * Check that stock level correct, considering an item is added to an order in the fixture.
   */
  function testStockLevels() {
    
    $productA = $this->objFromFixture('Product', 'productA');
    $this->assertEquals(4, $productA->StockLevel()->Level);
    
    $teeshirtExtraLargePurpleCotton = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
    $this->assertEquals('Enabled', $teeshirtExtraLargePurpleCotton->Status);
    $this->assertEquals(5, $teeshirtExtraLargePurpleCotton->StockLevel()->Level);
  }

	/**
	 * Add a product to the cart and reduce stock level of product without affecting versions of product
	 */
	function testAddProductToCartReduceStock() {

	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(4, $productA->StockLevel()->Level); //Stock starts one down because of orderOneItemOne
	  
	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productA->ID);
	  $versionsAfterPublished = array();
	  foreach ($versions as $versionRow) $versionsAfterPublished[] = $versionRow;
	  
	  $variations = $productA->Variations();
	  $this->assertEquals(false, $variations->exists());
	  
	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(3, $productA->StockLevel()->Level);
	  
	  
	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 2
	  ));
	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(1, $productA->StockLevel()->Level);
	  
	  //Make sure a new version of the product was NOT created 
	  $versions = DB::query('SELECT * FROM "Product_versions" WHERE "RecordID" = ' . $productA->ID);
	  $versionsAfterStockChanges = array();
	  foreach ($versions as $versionRow) $versionsAfterStockChanges[] = $versionRow;
	  
	  $this->assertTrue($versionsAfterPublished == $versionsAfterStockChanges);
	}
	
	/**
	 * remove a product from the cart and replenish stock levels
	 */
	function testRemoveProductFromCartReplaceStock() {
	  
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(4, $productA->StockLevel()->Level); //Stock starts one down because of orderOneItemOne
	  
	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
    
	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 3
	  ));
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(1, $productA->StockLevel()->Level);
	  
	  
	  //Remove the Item from the Order
	  $cartPage = $this->objFromFixture('CartPage', 'cart');
	  $this->get(Director::makeRelative($cartPage->Link()));

	  $order = CartControllerExtension::get_current_order();
	  $item = $order->Items()->First();
	  
	  $this->submitForm('CartForm_CartForm', null, array(
	    "Quantity[{$item->ID}]" => 0
	  ));
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $this->assertEquals(4, $productA->StockLevel()->Level);
	}

	/**
	 * Add a product variation to the cart, reduce stock level for variation without creating
	 * a new version for the variation
	 */
	function testAddProductVariationToCartReduceStock() {

	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();
	  
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $this->assertEquals('Enabled', $teeshirtAVariation->Status);
	  $this->assertEquals(5, $teeshirtAVariation->StockLevel()->Level);

	  $versions = DB::query('SELECT * FROM "Variation_versions" WHERE "RecordID" = ' . $teeshirtAVariation->ID);
	  $versionsAfterPublished = array();
	  foreach ($versions as $versionRow) $versionsAfterPublished[] = $versionRow;
	  
	  //Add variation to the cart
	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  $data = array('Quantity' => 1);
	  foreach ($teeshirtAVariation->Options() as $option) {
	    $data["Options[{$option->AttributeID}]"] = $option->ID;
	  }
	  $this->submitForm('AddToCartForm_AddToCartForm', null, $data);
	  
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $this->assertEquals(4, $teeshirtAVariation->StockLevel()->Level);
	  
	  //Make sure a new version of the product was NOT created 
	  $versions = DB::query('SELECT * FROM "Variation_versions" WHERE "RecordID" = ' . $teeshirtAVariation->ID);
	  $versionsAfterStockChanges = array();
	  foreach ($versions as $versionRow) $versionsAfterStockChanges[] = $versionRow;
	  
	  $this->assertTrue($versionsAfterPublished == $versionsAfterStockChanges);
	}
	
	/**
	 * Remove variation from the cart and replenish stock levels
	 */
	function testRemoveProductVariationFromCartReplaceStock() {

	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();
	  
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $this->assertEquals(5, $teeshirtAVariation->StockLevel()->Level);
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  
	  //Add variation to the cart
	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  $data = array('Quantity' => 1);
	  foreach ($teeshirtAVariation->Options() as $option) {
	    $data["Options[{$option->AttributeID}]"] = $option->ID;
	  }
	  $this->submitForm('AddToCartForm_AddToCartForm', null, $data);
	  
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $this->assertEquals(4, $teeshirtAVariation->StockLevel()->Level);
	  
	  
	  //Remove the Item from the Order
	  $cartPage = $this->objFromFixture('CartPage', 'cart');
	  $this->get(Director::makeRelative($cartPage->Link()));

	  $order = CartControllerExtension::get_current_order();
	  $item = $order->Items()->First();
	  
	  $this->submitForm('CartForm_CartForm', null, array(
	    "Quantity[{$item->ID}]" => 0
	  ));

	  //Flush the cache
	  DataObject::flush_and_destroy_cache();
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $this->assertEquals(5, $teeshirtAVariation->StockLevel()->Level);
	}
	
}