<?php
class CartTest extends FunctionalTest {
  
	static $fixture_file = 'simplecart/tests/CartTest.yml';
	static $disable_themes = false;
	static $use_draft_site = true;
	
  function setUp() {
		parent::setUp();
		
		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
	}

	/**
	 * Creating a product and checking price and currency
	 */
  function testProductAttributes() {
    
		$product = $this->objFromFixture('ProductPage', 'productA');
		$this->assertEquals($product->dbObject('Amount')->getAmount(), 500.00, 'The price of Product A should be 500.');
		$this->assertEquals($product->dbObject('Amount')->getCurrency(), 'NZD', 'The currency of Product A should be NZD.');
	}
	
	/**
	 * Adding an item to the cart and checking item exists
	 */
  function testAddItemToCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('ProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstProduct = $items->First()->Object();
	  $this->assertInstanceOf('ProductPage', $firstProduct);
	  $this->assertEquals($productA, $firstProduct);
	}
	
	/**
	 * Adding items to the cart and setting quantity
	 */
	function testCartItemsQuantity() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('ProductPage', 'productA');
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
	 */
	function testRemoveItemFromCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add product A to cart
	  $productA = $this->objFromFixture('ProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $order = CartController::get_current_order();
	  $items = $order->Items();
	  $this->assertInstanceOf('ComponentSet', $items);
	  $this->assertEquals(1, $items->TotalItems());
	  
	  $firstProduct = $items->First()->Object();
	  $this->assertInstanceOf('ProductPage', $firstProduct);
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
	 */
	function testClearCart() {
	  
	  $this->loginAs('buyer');
	  
	  //Add products A and B to cart
	  $productA = $this->objFromFixture('ProductPage', 'productA');
	  $addLink = $productA->AddToCartLink();
	  $this->get(Director::makeRelative($addLink)); 
	  
	  $productB = $this->objFromFixture('ProductPage', 'productB');
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
	 */
	function testProcessPayment() {

	  $this->loginAs('buyer');
	  
	  //Add some products to the shopping cart
	  $productA = $this->objFromFixture('ProductPage', 'productA');
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
	  
	  $payments = $order->Payments();
	  $this->assertInstanceOf('DataObjectSet', $payments);
	  $this->assertEquals(1, $payments->TotalItems());

    $payment = $payments->First();
    $this->assertEquals('ChequePayment', $payment->ClassName);
    $this->assertEquals('1000', $payment->Amount->getAmount(), 'Payment is for $199.98');
    $this->assertEquals($customerID, $payment->PaidByID);
    
    $payment->Status = 'Success';
    $payment->write();
    
    //Check that receipt was sent
    $this->assertEmailSent($customer->Email, $order->getReceiptFrom(), $order->getReceiptSubject());
	  $this->assertEquals(1, $order->ReceiptSent);
	}
	
	/**
	 * TODO Test downloading virtual products
	 */
	function testVirtualProductDownload() {
	  
	}

}