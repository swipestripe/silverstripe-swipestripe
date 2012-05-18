<?php
/**
 * Testing {@link Order} modifiers at checkout.
 * 
 * Summary of tests:
 * -----------------
 * checkout with flat fee shipping
 * checkout with deleted flat fee shipping option
 * checkout with wrong flat fee shipping option compared to shipping country
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 * @version 1.0
 */
class FlatFeeShippingTest extends SwipeStripeTest {
	
  function setUp() {
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
		$this->assertTrue(class_exists('ChequePayment'), 'Cheque Payment is installed.');
		$this->assertTrue(class_exists('FlatFeeShipping'), 'Flat Fee Shipping is installed.');
		
		//Force payment method to be basic cheque payment
		Payment::set_supported_methods(array(
      'ChequePayment' => 'Cheque Or Pay On Site'
    ));

    FlatFeeShipping::enable();
	}

	/**
	 * Try to checkout with valid flat fee shipping option
	 */
	function testCheckoutFlatFeeShipping() {

	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreNZ = $this->objFromFixture('FlatFeeShippingRate', 'MainCentreNewZealand');
	  $shippingAmount = $shippingMainCentreNZ->Amount->getAmount();
	  $shippingNZ = $this->objFromFixture('Country_Shipping', 'newZealand');
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $accountPage = DataObject::get_one('AccountPage');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $checkoutPage->doPublish();
	  $accountPage->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs($this->objFromFixture('Customer', 'buyer'));
	  $buyer = $this->objFromFixture('Customer', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  
	  $this->get(Director::makeRelative($checkoutPage->Link()));
	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Shipping[Country]' => 1,
	    'Modifiers[FlatFeeShipping]' => $shippingMainCentreNZ->ID
	  ));

	  $orders = $buyer->Orders();
	  $orders->sort('ID', "ASC");
	  $this->assertEquals(2, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount() + $shippingMainCentreNZ->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(1, $orders->Last()->Modifications()->Count());
	  $this->assertEquals($shippingMainCentreNZ->ID, $orders->Last()->Modifications()->First()->ID);
	}
	
	/**
	 * Try to checkout with deleted flat fee shipping option
	 */
	function testCheckoutDeletedFlatFeeShipping() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreNZ = $this->objFromFixture('FlatFeeShippingRate', 'MainCentreNewZealand');
	  $shippingID = $shippingMainCentreNZ->ID;
	  $shippingAmount = $shippingMainCentreNZ->Amount->getAmount();
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $accountPage = DataObject::get_one('AccountPage');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $shippingMainCentreNZ->delete();
	  $checkoutPage->doPublish();
	  $accountPage->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  $this->assertEquals(false, $shippingMainCentreNZ->isInDB());
	  
	  $this->loginAs($this->objFromFixture('Customer', 'buyer'));
	  $buyer = $this->objFromFixture('Customer', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $this->get(Director::makeRelative($checkoutPage->Link()));
	  
	  $data = $this->getFormData('CheckoutForm_OrderForm');
    $data['Modifiers[FlatFeeShipping]'] = $shippingID;

	  $this->post(
	    Director::absoluteURL('/checkout/OrderForm'),
	    $data
	  );
	  
	  $orders = $buyer->Orders();
	  $orders->sort('ID', "ASC");
	  $this->assertEquals(1, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(0, $orders->Last()->Modifications()->Count());
	}
	
	/**
	 * Try checkout with invalid flat fee shipping option
	 */
	function testCheckoutInvalidFlatFeeShipping() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreAustralia = $this->objFromFixture('FlatFeeShippingRate', 'MainCentreAustralia');
	  $shippingAmount = $shippingMainCentreAustralia->Amount->getAmount();
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $accountPage = DataObject::get_one('AccountPage');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $checkoutPage->doPublish();
	  $accountPage->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs($this->objFromFixture('Customer', 'buyer'));
	  $buyer = $this->objFromFixture('Customer', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('AddToCartForm_AddToCartForm', null, array(
	    'Quantity' => 1
	  ));

	  $order = CartControllerExtension::get_current_order();
	  $items = $order->Items();
	  
	  $this->assertEquals(1, $items->Count());
	  $this->assertEquals($productA->ID, $items->First()->Object()->ID);
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $this->get(Director::makeRelative($checkoutPage->Link()));

	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Shipping[Country]' => 'NZ',
	    'Modifiers[FlatFeeShipping]' => $shippingMainCentreAustralia->ID
	  ));
	  
	  $orders = $buyer->Orders();
	  $orders->sort('ID', "ASC");
	  $this->assertEquals(1, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(0, $orders->Last()->Modifications()->Count());
	}
}