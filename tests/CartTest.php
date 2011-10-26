<?php
/**
 * 
 * @author frankmullenger
 * 
 * TODO tests
 * add product to cart
 * change quantity of product in cart
 * update product and add it to cart again checking version number
 * add negative quantity to cart
 * add 0 quantity to cart
 * try saving duplicate variations
 * try saving variation without full set of options
 * change product price after it is in the cart
 * cannot add non-published product to the cart
 * customer members can add stuff to carts
 * website visitors can add stuff to carts
 * add product to cart and change price
 * add product variation
 * change quantity of variation
 * add different variations for same product
 * add product and variation to cart and check version
 * add variation to cart with price change
 * 
 * 
 * Checkout testing
 * unpublish product after it is in the cart cannot checkout
 * delete product after it is in the cart cannot checkout
 * add variation to cart then delete variation cannot checkout
 * submit checkout without necessary details
 * submit checkout without specifying payment gateway
 * submit checkout without products in cart
 * 
 * unpublish product, does not appear on website
 * 
 * check cart total
 * check cart subtotal
 * 
 * add shipping options to checkout
 * submit checkout with shipping option that does not match shipping country
 * add variation to cart and change price
 * when last item deleted from the cart, remove order modifiers also
 * variations with some attributes with empty options
 * 
 * delete product, staging versions all up to date and still exist
 * 
 * 
 * TEST:
 * Order
 * Order addresses
 * Order modifiers
 * Shipping
 * Product Categories
 * Account page
 * Product 
 * Checkout
 * Payment
 * 
 */
class CartTest extends FunctionalTest {
  
	static $fixture_file = 'stripeycart/tests/CartTest.yml';
	static $disable_themes = false;
	static $use_draft_site = true;
	
  function setUp() {
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
	}
	
	/**
	 * Log current member out by clearing session
	 */
	function logOut() {
	  $this->session()->clear('loggedInAs');
	}

	/**
	 * Create product and check basic attributes
	 */
  function testProduct() {
    
		$productA = $this->objFromFixture('Product', 'productA');
		$this->assertEquals($productA->dbObject('Amount')->getAmount(), 500.00, 'The price of Product A should be 500.');
		$this->assertEquals($productA->dbObject('Amount')->getCurrency(), 'NZD', 'The currency of Product A should be NZD.');
	}
	
	/**
	 * Add an item to the cart for a basic product and check correct product added
	 */
  function testAddProductToCart() {

    //Add published product to cart
    $productA = $this->objFromFixture('Product', 'productA');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->Count());
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(1, $firstItem->Quantity);
	  
	  //Check that the correct product has been added
	  $firstProduct = $firstItem->Object();
	  $this->assertInstanceOf('Product', $firstProduct);
	  $this->assertEquals($productA->Title, $firstProduct->Title);
	  $this->assertEquals($productA->dbObject('Amount')->getAmount(), $firstProduct->dbObject('Amount')->getAmount());
	}
	
	/**
	 * Adding non published product to a cart should fail
	 */
	function testAddNonPublishedProductToCart() {
	  
    $productA = $this->objFromFixture('Product', 'productA');
    
    $this->assertEquals(false, $productA->isPublished());
    
    $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
    
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(0, $items->Count());
	}
	
	/**
	 * Add product to the cart twice and check quantity
	 */
	function testAddProductQuantityToCart() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 2
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(3, $items->First()->Quantity);
	}
	
	/**
	 * Add negative quantity to cart, should have no effect on cart
	 */
	function testAddProductNegativeQuantityToCart() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => -1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	}
	
	/**
	 * Adding product with zero quantity should have no effect on cart
	 */
	function testAddProductZeroQuantityToCart() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 0
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	}
	
	/**
	 * Published products should get different versions, new versions are new items in the cart
	 */
	function testAddProductVersionToCart() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $firstVersion = $productA->Version;
	  $this->assertTrue($firstVersion > 0);

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(1, $items->Count());
	  
	  $firstProduct = $order->Items()->First()->Object();
	  $this->assertEquals($firstVersion, $firstProduct->Version);
	  
	  //Publish again and check version in the cart
	  $this->logInAs('admin');
	  //$productA->forceChange();
	  $productA->Title = 'Product A Changed';
	  $productA->doPublish();
	  $this->logOut();
	  
	  $secondVersion = $productA->Version;
	  $this->assertTrue($secondVersion > $firstVersion);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $this->assertEquals(2, $items->Count());
	  
	  $this->assertEquals($firstVersion, $order->Items()->First()->Object()->Version);
	  $this->assertEquals($secondVersion, $order->Items()->Last()->Object()->Version);
	}
	
	/**
	 * Add a product to the cart as a visitor to the website
	 */
	function testAddProductToCartLoggedOut() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $loggedInAs = $this->session()->get('loggedInAs');
	  $this->assertTrue(!$loggedInAs);

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $this->assertEquals(1, $order->Items()->Count());
	}
	
	/**
	 * Add a product logged in as a customer
	 */
	function testAddProductToCartLoggedInCustomer() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->logInAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  $loggedInAs = $this->session()->get('loggedInAs');
	  $this->assertEquals($buyer->ID, $loggedInAs);
	  
	  $member = Member::currentUser();
	  $this->assertEquals(true, $member->inGroup('customers'));

	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $this->assertEquals(1, $order->Items()->Count());
	}
	
	/**
	 * Change product price after it is in the cart, check that price has not changed in cart
	 * TODO remove? this is kinda stupid
	 */
	function testAddProductToCartChangePrice() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->logInAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();
	  $firstProduct = clone $productA;
	  
	  $this->assertEquals(1, $order->Items()->Count());
	  $this->assertEquals($productA->Amount->getAmount(), $firstItem->Amount->getAmount());
	  $this->assertEquals($productA->Amount->getCurrency(), $firstItem->Amount->getCurrency());
	  
	  $newAmount = new Money();
	  $newAmount->setAmount(72.34);
	  $newAmount->setCurrency('NZD');
	  
	  $this->logInAs('admin');
	  $productA->Amount->setValue($newAmount); 
	  $productA->doPublish();
	  $this->logOut();
	  
	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();

	  $firstItem = $items->First();
	  $secondItem = $items->Last();

	  $this->assertEquals(2, $order->Items()->Count());
	  
	  $this->assertEquals($firstProduct->Amount->getAmount(), $firstItem->Amount->getAmount());
	  $this->assertEquals($firstProduct->Amount->getCurrency(), $firstItem->Amount->getCurrency());
	  
	  $this->assertEquals($newAmount->getAmount(), $secondItem->Amount->getAmount());
	  $this->assertEquals($newAmount->getCurrency(), $secondItem->Amount->getCurrency());
	}
	
	/**
	 * Add a product variaiton to the cart
	 */
	function testAddProductVariationToCart() {
    $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');
	  $this->assertEquals('Enabled', $teeshirtAVariation->Status);
	  
	  $this->assertEquals(9,  $teeshirtAVariation->getAttributeOption(1)->ID);
	  $this->assertEquals(12, $teeshirtAVariation->getAttributeOption(2)->ID);
	  $this->assertEquals(14, $teeshirtAVariation->getAttributeOption(3)->ID);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();
	  $itemOptions = $firstItem->ItemOptions();
	  $variation = $itemOptions->First()->Object();
	  
	  $this->assertEquals(1, $itemOptions->Count());
	  $this->assertEquals($teeshirtAVariation->ID, $variation->ID);
	  $this->assertEquals($teeshirtAVariation->Version, $variation->Version);
	  $this->assertEquals($teeshirtAVariation->Status, $variation->Status);
	  $this->assertEquals($teeshirtAVariation->ProductID, $variation->ProductID);
	  $this->assertEquals('Variation', $variation->ClassName);
	}
	
	/**
	 * Add disabled product variation to cart should not work
	 */
	function testAddDisabledProductVariationToCart() {
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $teeshirtAVariation->Status = 'Disabled';
	  $teeshirtAVariation->write();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 

	  $this->assertEquals('Disabled', $teeshirtAVariation->Status);
	  
	  $this->assertEquals(9,  $teeshirtAVariation->getAttributeOption(1)->ID);
	  $this->assertEquals(12, $teeshirtAVariation->getAttributeOption(2)->ID);
	  $this->assertEquals(14, $teeshirtAVariation->getAttributeOption(3)->ID);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();

	  $this->assertEquals(0, $items->Count());
	}
	
	/**
	 * Add invalid product variation to cart should not work
	 */
	function testAddInvalidProductVariationToCart() {
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 

	  $this->assertEquals(9,  $teeshirtAVariation->getAttributeOption(1)->ID);
	  $this->assertEquals(12, $teeshirtAVariation->getAttributeOption(2)->ID);
	  $this->assertEquals(14, $teeshirtAVariation->getAttributeOption(3)->ID);

	  //Note to self: Cannot set values for POST that are not valid on the form
	  
	  //Submit with incorrect variation values, for Medium, Red, Cotton
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 10,  //Medium
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();

	  $this->assertEquals(0, $items->Count());
	}
	
	/**
	 * Add product variations and check quantities
	 */
  function testAddProductVariationQuantity() {
	  
    $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();

	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $firstItem->Quantity);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 2,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();

	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(3, $firstItem->Quantity);
	}
	
	/**
	 * Add different product variations for the same product
	 */
	function testAddProductVariations() {
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();

	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $firstItem->Quantity);
	  
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 15, //Polyester
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();

	  $this->assertEquals(2, $items->Count());
	  $this->assertEquals(1, $items->First()->Quantity);
	  $this->assertEquals(1, $items->Last()->Quantity);
	}
	
	/**
	 * Add product variations and check version correct
	 */
	function testAddVariationWithVersion() {
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton'); 

    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $teeshirtAVariation->Amount->setAmount(1.00);
	  $teeshirtAVariation->write();
	  $this->logOut();

	  $firstVersion = $teeshirtAVariation->Version;

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $firstItem = $items->First();
	  $itemOptions = $firstItem->ItemOptions();
	  $firstItemOption = $itemOptions->First();
	  $variation = $firstItemOption->Object();

	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $firstItem->Quantity);
	  $this->assertEquals($firstVersion, $firstItemOption->ObjectVersion);
	  
	  
	  $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $teeshirtAVariation->Amount->setAmount(0.00);
	  $teeshirtAVariation->write();
	  $this->logOut();
	  
	  $secondVersion = $teeshirtAVariation->Version;
	  $this->assertTrue($secondVersion > $firstVersion);
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 14, //Cotton
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  $lastItemOption = $items->Last()->ItemOptions()->Last();

	  $this->assertEquals(2, $items->Count());
	  $this->assertEquals(1, $items->Last()->Quantity);
	  $this->assertEquals($secondVersion, $lastItemOption->ObjectVersion);
	  
	}
	
	/**
	 * Add product variation with different price and check order total
	 */
	function testAddVariationWithPriceChanged() {
	  
	  $teeshirtA = $this->objFromFixture('Product', 'teeshirtA');
	  $teeshirtAVariation = $this->objFromFixture('Variation', 'teeshirtSmallRedPolyester'); 
	  
    $this->logInAs('admin');
	  $teeshirtA->doPublish();
	  $this->logOut();

	  $expectedAmount = $teeshirtA->Amount->getAmount() + $teeshirtAVariation->Amount->getAmount();

	  $this->get(Director::makeRelative($teeshirtA->Link())); 
	  
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1,
	    'Options[1]' => 9,  //Small
	    'Options[2]' => 12, //Red
	    'Options[3]' => 15, //Polyester
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();

	  $this->assertEquals($expectedAmount, $order->Total->getAmount());
	}
	
	/**
	 * Get product attribute and test options associated with it
	 */
	function testProductAttributeOptions() {
	  
	  $attributeSize = $this->objFromFixture('Attribute', 'attrSize');
	  $options = $attributeSize->Options();
	  
	  //Remove all the attribute options that have ProductID > 0, these are not default options
	  foreach ($options as $option) {
	    if ($option->ProductID != 0) {
	      $options->remove($option);
	    }
	  }
	  
	  $this->assertInstanceOf('ComponentSet', $options);
	  $this->assertEquals(3, $options->Count());
	  
	  $optionSmall = $options->find('Title', 'Small');
	  $this->assertInstanceOf('Option', $optionSmall);
	}
	
	/**
	 * Get variation options and test that they are correct
	 */
	function testProductVariationOptions() {
	  
	  $smallRedCotton = $this->objFromFixture('Variation', 'teeshirtSmallRedCotton');
	  
	  $this->assertEquals('Enabled', $smallRedCotton->Status, 'Variation should be status Enabled by default.');
	  
	  //Ensure correct options
	  $options = $smallRedCotton->Options();
	  $this->assertInstanceOf('ComponentSet', $options);
	  $this->assertEquals(3, $options->Count());
	  $this->assertEquals(array(
	    14 => 'Cotton',
	    12 => 'Red',
	    9  => 'Small'
	  ), $options->map('ID', 'Title'));
	}
	
	/**
	 * Test saving variation without all options set
	 */
	function testSaveInvalidProductVariation() {

	  //This variation only has 1 option instead of 2
	  $brokenProductVariation = $this->objFromFixture('Variation', 'brokenMedium');
	  $options = $brokenProductVariation->Options();
	  $this->assertInstanceOf('ComponentSet', $options);
	  $this->assertEquals(1, $options->Count());
	  
	  $e = null;
	  try {
	    $brokenProductVariation->write();
	  }
	  catch (ValidationException $e) {
	    $message = $e->getMessage();
	  }
	  $this->assertInstanceOf('ValidationException', $e);
	}
	
	/**
	 * Test saving duplicate product variations
	 */
	function testSaveDuplicateProductVariation() {

	  $brokenSmallRed = $this->objFromFixture('Variation', 'brokenSmallRed');
	  $brokenSmallRedDuplicate = $this->objFromFixture('Variation', 'brokenSmallRedDuplicate');
	  
	  $firstOptions = $brokenSmallRed->Options()->map();
	  $secondOptions = $brokenSmallRedDuplicate->Options()->map();

	  $this->assertEquals($firstOptions, $secondOptions);
	  
	  //Hacky way to add attribute options to the record for Variation::isDuplicate()
	  foreach ($brokenSmallRedDuplicate->Options() as $option) {
	    $brokenSmallRedDuplicate->setField('Options['.$option->AttributeID.']', $option->ID);
	  }

	  $e = null;
	  try {
	    $brokenSmallRedDuplicate->write();
	  }
	  catch (ValidationException $e) {
	    $message = $e->getMessage();
	  }
	  $this->assertInstanceOf('ValidationException', $e);
	}

	
	/**
	 * Test saving a product with a new attribute, existing variations without this attribute should be disabled
	 */
	function testSaveProductWithExtraAttribute() {
	  //TODO create a product with one too many attributes in YAML fixture
	}
	
	
	
	/**
	 * Adding items to the cart and setting quantity
	 *
	function testCartItemsQuantity() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('DummyProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $removeLink = $productA->RemoveFromCartLink();
	  
	  
	  //1 item with quantity 2 in cart
	  $this->get(Director::makeRelative($addLink)); 
	  $this->get(Director::makeRelative($addLink)); 

	  $order = CartController::get_current_order();
	  $this->assertEquals(1000, $order->Total->getAmount());
	  
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(2, $firstItem->Quantity);
	  
	  
	  //1 item with quantity 1 in cart
	  $this->get(Director::makeRelative($removeLink));
	  
	  $order = CartController::get_current_order();
	  $this->assertEquals(500, $order->Total->getAmount());
	  
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(1, $firstItem->Quantity);
	  
	  
	  //0 items in the cart
	  $this->get(Director::makeRelative($removeLink));
	  
	  $order = CartController::get_current_order();
	  $this->assertEquals(null, $order->Total->getAmount());
	  
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(0, $items->TotalItems());
	}

	/**
	 * Removing an item from the cart and checking that cart is empty
	 *
	function testRemoveItemFromCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('DummyProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstProduct = $items->First()->Object();
	  $this->assertInstanceOf('DummyProductPage', $firstProduct);
	  $this->assertEquals($productA, $firstProduct);
	  
	  //Remove product A from cart
	  $removeLink = $productA->RemoveFromCartLink();
	  $this->get(Director::makeRelative($removeLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(0, $items->TotalItems());
	}
	
	/**
	 * Clear the shopping cart
	 *
	function testClearCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add products A and B to cart
	  $productA = $this->objFromFixture('DummyProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $productB = $this->objFromFixture('DummyProductPage', 'productB');
	  $addLink = $productB->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(2, $items->TotalItems());
	  
	  $clearLink = $productA->ClearCartLink();
	  $this->get(Director::makeRelative($clearLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(0, $items->TotalItems());
	}
	
	/**
	 * Process the order form with dummy data
	 * Relies on ChequePayment 
	 *
	function testProcessPayment() {

	  $this->loginAs('buyer');
	  
	  //Add some products to the shopping cart
	  $productA = $this->objFromFixture('DummyProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $customer = $this->objFromFixture('Member', 'buyer');
	  $accountPage = $this->objFromFixture('AccountPage', 'account');
	  $checkoutPage = $this->objFromFixture('CheckoutPage', 'checkout');

	  $this->get(Director::makeRelative($checkoutPage->Link()));
	  
	  //Check that ChequePayment exists
		$this->assertTrue(class_exists('ChequePayment'), 'Payment module is installed with cheque payment.');
		
		//Maybe use $customer->toMap(), need to consider other fields like ID which will be posted

	  $orderPage = $this->submitForm('Form_OrderForm', null, array(
	    'FirstName' => $customer->FirstName,
      'Surname' => $customer->Surname,
      'HomePhone' => $customer->HomePhone,
      'Email' => $customer->Email,
      'Address' => $customer->Address,
      'AddressLine2' => $customer->AddressLine2,
      'City' => $customer->City,
      'PostalCode' => $customer->PostalCode,
      'Country' => $customer->Country,
      'PaymentMethod' => 'ChequePayment',
      'Cheque' => '0',
      'Amount' => '1000'
	  ));
	  
	  //Get the last order and its payment and set Payment->Status = Success
	  //to test onAfterPayment()
	  $order = DataObject::get_one('Order');
	  $customerID = $this->idFromFixture('Member', 'buyer');
	  $this->assertEquals($order->MemberID, $customerID);
	  $this->assertEquals($order->Status, 'Cart', 'Order status is Cart');

	  $payments = $order->Payments();
	  $this->assertInstanceOf('DataObjectSet', $payments);
	  $this->assertEquals(1, $payments->TotalItems());

    $payment = $payments->First();
    $this->assertEquals('ChequePayment', $payment->ClassName);
    $this->assertEquals('1000', $payment->Amount->getAmount(), 'Payment is for $1000');
    $this->assertEquals($customerID, $payment->PaidByID);
    
    $payment->Status = 'Success';
    $payment->write();
    
    //Check that receipt was sent
    $this->assertEmailSent($customer->Email, $order->getReceiptFrom(), $order->getReceiptSubject());
	  $this->assertEquals(1, $order->ReceiptSent);

	  //Check that order status is updated
	  $order = DataObject::get_one('Order');
	  $this->assertEquals($order->Status, 'Paid', 'Order status is Paid');
	}
	
	/**
	 * TODO Test downloading virtual products: download limit, cleanup task, file creation, download window
	 * TODO Remove test dependency on products/productA.txt file, create a test file if necessary
	 * TODO see RemoveOrphanedPagesTaskTest for examples of testing tasks
	 *
	function testVirtualProductDownload() {

	  $this->loginAs('buyer');

	  //Add virtual product A to cart a few times
	  $virtualProductA = $this->objFromFixture('DummyVirtualProductPage', 'virtualProductA');
	  $addLink = $virtualProductA->AddToCartLink();
	  $removeLink = $virtualProductA->RemoveFromCartLink();
	  
	  $this->get(Director::makeRelative($addLink)); 
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $order = CartController::get_current_order();
	  $this->assertEquals(139.98, $order->Total->getAmount());
	  
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(2, $firstItem->Quantity);
	  
	  $virtualProduct = $firstItem->Object();
	  $this->assertEquals('/products/productA.txt', $virtualProduct->FileLocation);
	  
	  //Process the order
	  $customer = $this->objFromFixture('Member', 'buyer');
	  $accountPage = $this->objFromFixture('AccountPage', 'account');
	  $checkoutPage = $this->objFromFixture('CheckoutPage', 'checkout');

	  $this->get(Director::makeRelative($checkoutPage->Link()));
	  
	  //Check that ChequePayment exists
		$this->assertTrue(class_exists('ChequePayment'), 'Payment module is installed with cheque payment.');
		
		//Maybe use $customer->toMap(), need to consider other fields like ID which will be posted

	  $orderPage = $this->submitForm('Form_OrderForm', null, array(
	    'FirstName' => $customer->FirstName,
      'Surname' => $customer->Surname,
      'HomePhone' => $customer->HomePhone,
      'Email' => $customer->Email,
      'Address' => $customer->Address,
      'AddressLine2' => $customer->AddressLine2,
      'City' => $customer->City,
      'PostalCode' => $customer->PostalCode,
      'Country' => $customer->Country,
      'PaymentMethod' => 'ChequePayment',
      'Cheque' => '0',
      'Amount' => '139.98' //This value does not actually get used
	  ));
	  
	  //Get the last order and its payment and set Payment->Status = Success
	  //to test onAfterPayment()
	  $order = DataObject::get_one('Order');
	  $customerID = $this->idFromFixture('Member', 'buyer');
	  $this->assertEquals($order->MemberID, $customerID);
	  
	  $payments = $order->Payments();
	  $this->assertInstanceOf('DataObjectSet', $payments);
	  $this->assertEquals(1, $payments->TotalItems());

    $payment = $payments->First();
    $this->assertEquals('ChequePayment', $payment->ClassName);
    $this->assertEquals(139.98, $payment->Amount->getAmount(), 'Payment is for $139.98');
    $this->assertEquals($customerID, $payment->PaidByID);
    
    $payment->Status = 'Success';
    $payment->write();
    
    //Get the order page and check that downloads exist
	  $this->get(Director::makeRelative($accountPage->Link() . '/order/' . $order->ID));
	  
	  $this->assertExactHTMLMatchBySelector('#DownloadsTable td.productTitle', array(
			'<td class="productTitle">Virtual Product A</td>'
		));
	  
	  //Get the download 
	  $downloadLink = $firstItem->DownloadLink();
	  $this->assertEquals($accountPage->Link() . 'downloadproduct/?ItemID='.$firstItem->ID, $downloadLink);
	  
	  $this->get(Director::makeRelative($downloadLink));
	  
	  //TODO finish this off, test that a download was created etc.
	  
	}
	*/
}