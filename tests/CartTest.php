<?php
/**
 * 
 * @author frankmullenger
 * 
 * TODO tests
 * add product to cart
 * change quantity of product in cart
 * update product and add it to cart again checking version number
 * 
 * add product variation
 * change quantity of variation
 * add different variations for same product
 * add variation to cart then delete variation
 * add product to cart then delete product
 * add product and variation to cart and check version
 * add variation to cart with price change
 * check variation options on product page
 * add negative quantity to cart
 * submit checkout without necessary details
 * submit checkout without specifying payment gateway
 * submit checkout without products in cart
 * add shipping options to checkout
 * submit checkout with shipping option that does not match shipping country
 * add product to cart and change price
 * add variation to cart and change price
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
	 * Create product and check basic attributes
	 */
  function testProductAttributes() {
    
		$product = $this->objFromFixture('Product', 'productA');
		$this->assertEquals($product->dbObject('Amount')->getAmount(), 500.00, 'The price of Product A should be 500.');
		$this->assertEquals($product->dbObject('Amount')->getCurrency(), 'NZD', 'The currency of Product A should be NZD.');
	}
	
	/**
	 * Add an item to the cart for a basic product
	 * Add the same product with different quantity
	 * Publish same product and add again to check that new item added to cart with different version
	 * Adding products with negative quantity should not work
	 */
  function testAddItemToCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('Product', 'productA');
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
	  
	  //Check that the correct product has been added
	  $firstProduct = $firstItem->Object();
	  $this->assertInstanceOf('Product', $firstProduct);
	  $this->assertEquals($productA->Title, $firstProduct->Title);
	  $this->assertEquals($productA->dbObject('Amount')->getAmount(), $firstProduct->dbObject('Amount')->getAmount());
	  $this->assertEquals($firstVersion, $firstProduct->Version);
	  

	  //Add the product again and check the quantity
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 2
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals(1, $items->TotalItems());
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(3, $firstItem->Quantity);
	  
	  $firstProduct = $firstItem->Object();
	  $this->assertInstanceOf('Product', $firstProduct);
	  $this->assertEquals($productA->Title, $firstProduct->Title);
	  $this->assertEquals($productA->dbObject('Amount')->getAmount(), $firstProduct->dbObject('Amount')->getAmount());
	  $this->assertEquals($firstVersion, $firstProduct->Version);
	  
	  
	  //Update the product and add it again, should have new item in cart 
	  //because product version has changed
	  $productA->writeToStage('Stage');
		$productA->publish('Stage', 'Live');
		$secondVersion = $productA->Version;
		
		$productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
		
		$this->assertEquals(2, $items->Count());
	  $this->assertEquals(2, $items->TotalItems());
	  
	  $firstItem = $items->First();
	  $this->assertInstanceOf('Item', $firstItem);
	  $this->assertEquals(3, $firstItem->Quantity);

	  $firstProduct = $firstItem->Object();
	  $this->assertInstanceOf('Product', $firstProduct);
	  $this->assertEquals($productA->Title, $firstProduct->Title);
	  $this->assertEquals($productA->dbObject('Amount')->getAmount(), $firstProduct->dbObject('Amount')->getAmount());
	  $this->assertEquals($firstVersion, $firstProduct->Version);
	  
	  $secondItem = $items->Last();
	  $this->assertInstanceOf('Item', $secondItem);
	  $this->assertEquals(1, $secondItem->Quantity);
	  
	  $secondProduct = $secondItem->Object();
	  $this->assertInstanceOf('Product', $secondProduct);
	  $this->assertEquals($productA->Title, $secondProduct->Title);
	  $this->assertEquals($productA->dbObject('Amount')->getAmount(), $secondProduct->dbObject('Amount')->getAmount());
	  $this->assertEquals($secondVersion, $secondProduct->Version);
	  
	  
	  //Add product with negative quantity should have no effect
	  $productALink = $productA->Link();
	  $this->get(Director::makeRelative($productALink)); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => -1
	  ));
	  
	  $order = ProductControllerExtension::get_current_order();
	  $items = $order->Items();
		
		$this->assertEquals(2, $items->Count());
	  $this->assertEquals(2, $items->TotalItems());
	  
	  $secondItem = $items->Last();
	  $this->assertInstanceOf('Item', $secondItem);
	  $this->assertEquals(1, $secondItem->Quantity);

	  
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