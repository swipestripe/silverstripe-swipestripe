<?php
/**
 * 
 * @author frankmullenger
 * 
 * Summary of tests:
 * -----------------
 * checkout published product
 * unpublish product after it is in the cart cannot checkout
 * 
 * TODO
 * ----
 * variation versions in cart with changed price
 * 
 * Checkout testing
 * checkout with product that has attributes, without a variation set
 * delete product after it is in the cart cannot checkout
 * add variation to cart then delete variation cannot checkout
 * add variation then disable the variation, cannot checkout
 * submit checkout without necessary details
 * submit checkout without specifying payment gateway
 * submit checkout without products in cart
 * when last item deleted from the cart, remove order modifiers also
 * add shipping options to checkout
 * submit checkout with shipping option that does not match shipping country
 * process payment
 * send receipt
 * checkout addresses correct
 * 
 * Product Category
 * unpublish product, does not appear on website
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
class CheckoutTest extends FunctionalTest {
  
	static $fixture_file = 'stripeycart/tests/CartTest.yml';
	static $disable_themes = false;
	static $use_draft_site = true;
	
  function setUp() {
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
		$this->assertTrue(class_exists('ChequePayment'), 'Cheque Payment module is installed.');
		
		//Force payment method to be basic cheque payment
		Payment::set_supported_methods(array(
      'ChequePayment' => 'Cheque Or Pay On Site'
    ));
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
  function testCheckoutWithPublishedProduct() {

		$productA = $this->objFromFixture('Product', 'productA');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $this->get(Director::makeRelative($checkoutPage->Link()));

	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Notes' => 'New order for test buyer.'
	  ));
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(2, $orders->Count());
	}
	
	/**
	 * Try to checkout an unpublished product
	 */
	function testCheckoutWithUnpublishedProduct() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());

	  //Add product to cart, buyer has one Order existing from fixture
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  $this->assertEquals(1, $buyer->Orders()->Count());
	  
	  $this->loginAs('buyer');

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  $this->logOut();
	  
	  //Unpublish the product thats in the cart
	  $this->loginAs('admin');
	  $productA->doUnpublish();
	  $this->logOut();
	  
	  $this->assertEquals(false, $productA->isPublished());
	  $this->assertEquals(false, $order->isValid());
	  
	  //Log in as buyer again and try to checkout
	  $this->loginAs('buyer');
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $this->get(Director::makeRelative($checkoutPage->Link()));

	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Notes' => 'This order should fail.'
	  ));
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());
	}
	
/**
	 * Try to checkout a deleted product
	 */
	function testCheckoutWithDeletedProduct() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());

	  //Add product to cart, buyer has one Order existing from fixture
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  $this->assertEquals(1, $buyer->Orders()->Count());
	  
	  $this->loginAs('buyer');

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  $this->logOut();
	  
	  //Delete the product thats in the cart
	  $this->loginAs('admin');
	  $productA->delete();
	  $this->logOut();
	  
	  $this->assertEquals(false, $productA->isInDB());
	  $this->assertEquals(false, $order->isValid());
	  
	  //Log in as buyer again and try to checkout
	  $this->loginAs('buyer');
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $this->get(Director::makeRelative($checkoutPage->Link()));

	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Notes' => 'This order should fail.'
	  ));
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());
	}
	
}