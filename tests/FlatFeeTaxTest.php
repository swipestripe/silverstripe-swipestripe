<?php
/**
 * Testing {@link Order} modifiers at checkout.
 * 
 * NOTE: be careful not to enable other modifiers in _config when running this test
 * 
 * Summary of tests:
 * -----------------
 * checkout with flat fee tax rate
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 * @version 1.0
 */
class FlatFeeTaxTest extends SwipeStripeTest {
	
  function setUp() {
    
    FlatFeeTax::enable();
    
		parent::setUp();

		//Check that payment module is installed
		$this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
		$this->assertTrue(class_exists('ChequePayment'), 'Cheque Payment is installed.');
		$this->assertTrue(class_exists('FlatFeeTax'), 'Flat Fee Shipping is installed.');
		
		//Force payment method to be basic cheque payment
		Payment::set_supported_methods(array(
      'ChequePayment' => 'Cheque Or Pay On Site'
    ));
	}

	/**
	 * Try to checkout with valid flat fee shipping option
	 */
	function testCheckoutFlatFeeShipping() {

	  $productA = $this->objFromFixture('Product', 'productA');
	  $taxRate = $this->objFromFixture('FlatFeeTaxRate', 'gstNewZealand');
	  $countryNZ = $this->objFromFixture('Country_Shipping', 'newZealand');
	  $buyer = $this->objFromFixture('Customer', 'buyer');
	  
	  $checkoutPage = DataObject::get_one('CheckoutPage');
	  $accountPage = DataObject::get_one('AccountPage');

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $checkoutPage->doPublish();
	  $accountPage->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs($buyer);

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
	  
	  //Set a modification for this Order via updateOrderFormCart
	  $data = $this->getFormDataNested('CheckoutForm_OrderForm');
	  $this->post(
	    Director::makeRelative($checkoutPage->Link() . 'updateOrderFormCart'),
	    $data
	  );

	  $this->get(Director::makeRelative($checkoutPage->Link()));

	  $this->submitForm('CheckoutForm_OrderForm', null, array(
	    'Shipping[Country]' => $countryNZ->ID,
	    'Modifiers[FlatFeeTax]' => $taxRate->ID
	  ));

	  $orders = $buyer->Orders();

	  $orders->sort('ID', "ASC");
	  $this->assertEquals(2, $orders->Count());
	  
	  $order = $orders->Last();
	  $this->assertEquals(15, $taxRate->Rate);
	  $realTotal = $order->SubTotal->getAmount() * 1.15;

	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	}

}