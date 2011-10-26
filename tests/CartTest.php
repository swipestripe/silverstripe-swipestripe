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
 * 
 * unpublish product after it is in the cart cannot checkout
 * delete product after it is in the cart cannot checkout
 * 
 * change product price after it is in the cart
 * add product variation
 * change quantity of variation
 * add different variations for same product
 * add variation to cart then delete variation
 * add product to cart then delete product
 * add product and variation to cart and check version
 * add variation to cart with price change
 * check variation options on product page
 * 
 * submit checkout without necessary details
 * submit checkout without specifying payment gateway
 * submit checkout without products in cart
 * add shipping options to checkout
 * submit checkout with shipping option that does not match shipping country
 * add product to cart and change price
 * add variation to cart and change price
 * when last item deleted from the cart, remove order modifiers also
 * variations with some attributes with empty options
 * 
 * delete product, staging versions all up to date and still exist
 * cannot add non-published product to the cart
 * only customer members can add stuff to carts
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
	 */
	function testAddProductToCartChangePrice() {
	  
	  $this->loginAs('admin');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('Product', 'productA');
	  $productA->doPublish();
	  $firstVersion = $productA->Version;

	  $addToCartForm = $productA->AddToCartForm(1);
	  $this->assertInstanceOf('Form', $addToCartForm);

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
	  $this->assertEquals(1, $items->TotalItems());
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(1, $firstItem->Quantity);
	  
	  //Unpublish the product and check the version is still in the cart
	  
	  
	  
	  //$productA->writeToStage('Stage');
		//$productA->publish('Stage', 'Live');
		//$secondVersion = $productA->Version;
		
		//SS_Log::log(new Exception(print_r($productA->latestPublished(), true)), SS_Log::NOTICE);
		
		//$published = Versioned::get_by_stage('Product', 'Live');
		//SS_Log::log(new Exception(print_r($published, true)), SS_Log::NOTICE);
		
		//$admin = $this->objFromFixture('Member', 'admin'); 
		//$adminGroup = $this->objFromFixture('Group', 'admin');
		
		//$admin->addToGroupByCode('admin');
		
		//SS_Log::log(new Exception(print_r('########@@@@@@@@@@@@@@@@##########', true)), SS_Log::NOTICE);
		//SS_Log::log(new Exception(print_r($admin->Groups(), true)), SS_Log::NOTICE);
		
		//SS_Log::log(new Exception(print_r(Permission::permissions_for_member($admin->ID), true)), SS_Log::NOTICE);
		
		//SS_Log::log(new Exception(print_r(Permission::get_groups_by_permission('ADMIN'), true)), SS_Log::NOTICE);
		
		//SS_Log::log(new Exception(print_r($adminGroup->Members(), true)), SS_Log::NOTICE);
		
		$result = DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = $productA->ID")->value();
		SS_Log::log(new Exception(print_r($result, true)), SS_Log::NOTICE);
	  
	  $productA->doUnpublish();
	  
	  $result = DB::query("SELECT \"Title\" FROM \"SiteTree_Live\" WHERE \"ID\" = $productA->ID")->value();
	  SS_Log::log(new Exception(print_r($result, true)), SS_Log::NOTICE);
	  
	  //$published = Versioned::get_by_stage('Product', 'Live');
		//SS_Log::log(new Exception(print_r($published, true)), SS_Log::NOTICE);
	  
	  //Unpublish product, cannot add to cart, cannot purchase
	  //Delete product, unpublished but versions exist, cannot add to cart, cannot purchase
	  //Delete product, versions remain but unpublished, does not display on site

	}
	
	function testAddProductVariationToCart() {

	}
	
	
	/**
	 * Get product attribute and test options associated with it
	 */
	function testProductAttributes() {
	  
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
	 * Get variation test options
	 * Remove option and try saving variation
	 * Change options to be duplicate with another variation and try save
	 * TODO Add attribute to product and save, make sure variations without that attribute are all disabled
	 */
	function testProductVariations() {
	  
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
	  
	  //Try saving variation with an option missing
	  $xlargePurple = $this->objFromFixture('Variation', 'teeshirtExtraLargePurpleCotton');
	  $options = $xlargePurple->Options();
	  $this->assertInstanceOf('ComponentSet', $options);
	  $this->assertEquals(2, $options->Count());
	  
	  $e = null;
	  try {
	    $xlargePurple->write();
	  }
	  catch (ValidationException $e) {
	    $message = $e->getMessage();
	  }
	  $this->assertInstanceOf('ValidationException', $e);
	  
	  //Try saving duplicate variations
	  $xlargeRedCotton = $this->objFromFixture('Variation', 'teeshirtExtraLargeRedCotton');
	  $xlargeRedCottonDuplicate = $this->objFromFixture('Variation', 'teeshirtExtraLargeRedCottonDuplicate');
	  
	  $firstOptions = $xlargeRedCotton->Options()->map();
	  $secondOptions = $xlargeRedCottonDuplicate->Options()->map();

	  $this->assertEquals($firstOptions, $secondOptions);
	  
	  //Hacky way to add attribute options to the record for Variation::isDuplicate()
	  foreach ($xlargeRedCottonDuplicate->Options() as $option) {
	    $xlargeRedCottonDuplicate->setField('Options['.$option->AttributeID.']', $option->ID);
	  }

	  $e = null;
	  try {
	    $xlargeRedCottonDuplicate->write();
	  }
	  catch (ValidationException $e) {
	    $message = $e->getMessage();
	  }
	  $this->assertInstanceOf('ValidationException', $e);
	  
	  //TODO Add another attribute to the product and save, all existing variations should be disabled
	  //Probably should do this in the YAML
	  
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