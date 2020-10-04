<?php

namespace SwipeStripe\Core\tests;





use Exception;
use SwipeStripe\Core\code\Customer\CheckoutPage;
use SwipeStripe\Core\code\Customer\AccountPage;
use SwipeStripe\Core\code\Customer\CartPage;
use SilverStripe\Core\Config\Config;
use SwipeStripe\Core\code\Product\Product;
use SwipeStripe\Core\code\Customer\Customer;
use SilverStripe\Control\Director;
use SwipeStripe\Core\code\Customer\Cart;
use SilverStripe\ORM\DataObject;
use SwipeStripe\Core\code\Product\Variation;


/**
 * Testing {@link Order} modifiers at checkout.
 * 
 * Summary of tests:
 * -----------------
 * checkout published product
 * unpublish product after it is in the cart cannot checkout
 * delete product after it is in the cart cannot checkout
 * add variation then disable the variation, cannot checkout
 * add variation to cart then delete variation cannot checkout
 * submit checkout without products in cart
 * checkout with product that has attributes, without a variation set
 * submit checkout without necessary details
 * submit checkout without specifying payment gateway
 * 
 * TODO
 * ----
 * process payment
 * send receipt
 * checkout addresses correct
 * when attribute deleted cannot checkout variation
 * when attribute deleted previous orders keep data
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 */
class SWS_CheckoutTest extends SWS_Test {
	
	function setUp() {
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
		//$this->assertTrue(class_exists('ChequePayment'), 'Cheque Payment is installed.');
		
		//Need to publish a few pages because not using the draft site
		$checkoutPage = $this->objFromFixture(CheckoutPage::class, 'checkout');  
		$accountPage = $this->objFromFixture(AccountPage::class, 'account');
		$cartPage = $this->objFromFixture(CartPage::class, 'cart');
		
		$this->loginAs('admin');
		$checkoutPage->doPublish();
		$accountPage->doPublish();
		$cartPage->doPublish();
		$this->logOut();

		Config::inst()->remove('PaymentProcessor', 'supported_methods');
		Config::inst()->update('PaymentProcessor', 'supported_methods', array('test' => array('Cheque')));
		Config::inst()->remove('PaymentGateway', 'environment');
		Config::inst()->update('PaymentGateway', 'environment', 'test');
	}

	/**
	 * Create product and check basic attributes
	 */
	function testCheckoutWithPublishedProduct() {
		
		$productA = $this->objFromFixture(Product::class, 'productA');
		$checkoutPage = $this->objFromFixture(CheckoutPage::class, 'checkout'); 

		$this->loginAs('admin');
		$productA->doPublish();
		$this->logOut();
		
		$this->assertTrue($productA->isPublished());
		
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->loginAs($buyer);

		$this->get(Director::makeRelative($productA->Link())); 
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($productA->ID, $items->First()->Product()->ID);
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
		
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'New order for test buyer.'
		));

		$orders = $buyer->Orders();
		$this->assertEquals(2, $orders->Count());
	}
	
	/**
	 * Try to checkout an unpublished product
	 */
	function testCheckoutWithUnpublishedProduct() {
		
		$productA = $this->objFromFixture(Product::class, 'productA');

		$this->loginAs('admin');
		$productA->doPublish();
		$this->logOut();
		
		$this->assertTrue($productA->isPublished());

		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($buyer);

		$this->get(Director::makeRelative($productA->Link())); 
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($productA->ID, $items->First()->Product()->ID);
		$this->logOut();
		
		//Unpublish the product thats in the cart
		$this->loginAs('admin');
		$productA->doUnpublish();
		$this->logOut();
		
		$this->assertEquals(false, $productA->isPublished());
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'This order should fail.'
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to checkout a deleted product
	 */
	function testCheckoutWithDeletedProduct() {
		
		$productA = $this->objFromFixture(Product::class, 'productA');

		$this->loginAs('admin');
		$productA->doPublish();
		$this->logOut();
		
		$this->assertTrue($productA->isPublished());

		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($buyer);

		$this->get(Director::makeRelative($productA->Link())); 
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($productA->ID, $items->First()->Product()->ID);
		$this->logOut();
		
		//Delete the product thats in the cart
		$this->loginAs('admin');
		$productA->delete();
		$this->logOut();
		
		$this->assertEquals(false, $productA->isInDB());
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($buyer);
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'This order should fail.'
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to checkout a disabled variation
	 */
	function testCheckoutWithDisabledVariation() {
		
		$shortsA = $this->objFromFixture(Product::class, 'shortsA');

		$this->loginAs('admin');
		$shortsA->doPublish();
		$this->logOut();
		
		$this->assertTrue($shortsA->isPublished());

		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));

		$this->get(Director::makeRelative($shortsA->Link())); 
		
		$shortsAVariation = $this->objFromFixture(Variation::class, 'shortsSmallRedCotton');
		$this->assertEquals('Enabled', $shortsAVariation->Status);
		
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1,
			'Options[1]' => $shortsAVariation->getOptionForAttribute(1)->ID,  //Small
			'Options[2]' => $shortsAVariation->getOptionForAttribute(2)->ID, //Red
			'Options[3]' => $shortsAVariation->getOptionForAttribute(3)->ID, //Cotton
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($shortsA->ID, $items->First()->Product()->ID);
		$this->logOut();
		
		$this->logInAs('admin');
		$shortsAVariation->Status = 'Disabled';
		$shortsAVariation->write();
		$this->logOut();

		$this->assertEquals('Disabled', $shortsAVariation->Status);
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'This order should fail.'
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to checkout a deleted variation
	 */
	function testCheckoutWithDeletedVariation() {
		
		$shortsA = $this->objFromFixture(Product::class, 'shortsA');

		$this->loginAs('admin');
		$shortsA->doPublish();
		$this->logOut();
		
		$this->assertTrue($shortsA->isPublished());

		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));

		$this->get(Director::makeRelative($shortsA->Link())); 
		
		$shortsAVariation = $this->objFromFixture(Variation::class, 'shortsSmallRedCotton');
		$this->assertEquals('Enabled', $shortsAVariation->Status);
		
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1,
			'Options[1]' => $shortsAVariation->getOptionForAttribute(1)->ID,  //Small
			'Options[2]' => $shortsAVariation->getOptionForAttribute(2)->ID, //Red
			'Options[3]' => $shortsAVariation->getOptionForAttribute(3)->ID, //Cotton
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($shortsA->ID, $items->First()->Product()->ID);
		$this->logOut();
		
		$this->logInAs('admin');
		$shortsAVariation->delete();
		$this->logOut();

		$this->assertEquals(false, $shortsAVariation->isInDB());
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'This order should fail.'
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to checkout without products added to the order
	 */
	function testCheckoutWithoutProducts() {
		
		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));
		
		$order = Cart::get_current_order();
		$items = $order->Items();
		$this->assertEquals(0, $items->Count());
		
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		try {
			$this->submitForm('OrderForm_OrderForm', null, array(
				'Notes' => 'This order should fail.'
			));
		}
		catch (Exception $e) {
		}
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to checkout with a product that requires a variation, without a variation in the cart
	 */
	function testCheckoutWithoutRequiredVariation() { 
		$shortsA = $this->objFromFixture(Product::class, 'shortsA');

		$this->loginAs('admin');
		$shortsA->doPublish();
		$this->logOut();
		
		$this->assertTrue($shortsA->isPublished());
		$this->assertTrue($shortsA->requiresVariation());

		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));

		$this->get(Director::makeRelative($shortsA->Link())); 
		
		$shortsAVariation = $this->objFromFixture(Variation::class, 'shortsSmallRedCotton');
		$this->assertEquals('Enabled', $shortsAVariation->Status);
		
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1,
			'Options[1]' => $shortsAVariation->getOptionForAttribute(1)->ID,  //Small
			'Options[2]' => $shortsAVariation->getOptionForAttribute(2)->ID, //Red
			'Options[3]' => $shortsAVariation->getOptionForAttribute(3)->ID, //Cotton
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		$variation = $order->Items()->First()->Variation();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($shortsA->ID, $items->First()->Product()->ID);
		$this->logOut();
		
		$this->logInAs('admin');
		$variation->delete();
		$this->logOut();
		
		$this->assertEquals(false, $variation->isInDB());
		$this->assertEquals(false, $order->validateForCart()->valid());
		
		//Log in as buyer again and try to checkout
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Notes' => 'This order should fail.'
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try to submit the checkout form without some required fields
	 * Assumes that billing FirstName is always required
	 */
	function testCheckoutWithoutRequiredFields() {
		$shortsA = $this->objFromFixture(Product::class, 'shortsA');
		$shortsAVariation = $this->objFromFixture(Variation::class, 'shortsSmallRedCotton');
		
		$this->loginAs('admin');
		$shortsA->doPublish();
		$this->logOut();

		$this->assertTrue($shortsA->isPublished());
		
		//Add product to cart, buyer has one Order existing from fixture
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));

		$this->get(Director::makeRelative($shortsA->Link()));
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1,
			'Options[1]' => $shortsAVariation->getOptionForAttribute(1)->ID,  //Small
			'Options[2]' => $shortsAVariation->getOptionForAttribute(2)->ID, //Red
			'Options[3]' => $shortsAVariation->getOptionForAttribute(3)->ID, //Cotton
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		$variation = $order->Items()->First()->Variation();
		
		$this->assertEquals(1, $items->Count());
		$this->assertEquals($shortsA->ID, $items->First()->Product()->ID);
		$this->logOut();

		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));

		$this->submitForm('OrderForm_OrderForm', null, array(
			'Billing[FirstName]' => ''
		));
		
		$orders = $buyer->Orders();
		$this->assertEquals(1, $orders->Count());
	}
	
	/**
	 * Try checking out an order without specifying a payment gateway
	 */
	function testCheckoutWithoutPaymentGateway() {

		$productA = $this->objFromFixture(Product::class, 'productA');

		$this->loginAs('admin');
		$productA->doPublish();
		$this->logOut();
		
		$buyer = $this->objFromFixture(Customer::class, 'buyer');
		$this->assertEquals(1, $buyer->Orders()->Count());
		
		$this->loginAs($this->objFromFixture(Customer::class, 'buyer'));

		$productALink = $productA->Link();
		$this->get(Director::makeRelative($productALink)); 
		$this->submitForm('ProductForm_ProductForm', null, array(
			'Quantity' => 1
		));

		$order = Cart::get_current_order();
		$items = $order->Items();
		$this->assertEquals(1, $items->Count());
		
		$checkoutPage = DataObject::get_one(CheckoutPage::class);
		$this->get(Director::makeRelative($checkoutPage->Link()));
		
		//Submit the form without restrictions on what can be POST'd
		$data = $this->getFormData('OrderForm_OrderForm');
		$data['PaymentMethod'] = '';

		$this->post(
			Director::absoluteURL('/checkout/OrderForm'),
			$data
		);

		$this->assertEquals(1, $buyer->Orders()->Count());
	}

}