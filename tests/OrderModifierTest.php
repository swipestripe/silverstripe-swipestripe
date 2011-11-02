<?php
/**
 * 
 * @author frankmullenger
 * 
 * Summary of tests:
 * -----------------
 * checkout with flat fee shipping
 * checkout with deleted flat fee shipping option
 * checkout with wrong flat fee shipping option compared to shipping country
 * 
 * TODO
 * ----
 * 
 * Product Category
 * delete product, does not appear on website
 * delete product, staging versions all up to date and still exist
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
class OrderModifierTest extends FunctionalTest {
  
	static $fixture_file = 'stripeycart/tests/CartTest.yml';
	static $disable_themes = false;
	static $use_draft_site = true;
	
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
    
    //Flat fee shipping enabled for NZ and AU
    Shipping::set_supported_countries(array('NZ', 'AU'));
    FlatFeeShipping::enable();
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
	 * Try to checkout with valid flat fee shipping option
	 */
	function testCheckoutFlatFeeShipping() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreNZ = $this->objFromFixture('FlatFeeShippingCountry', 'MainCentreNewZealand');
	  $shippingAmount = $shippingMainCentreNZ->Amount->getAmount();

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
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
	    'Modifiers[FlatFeeShipping]' => $shippingMainCentreNZ->ID
	  ));
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(2, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount() + $shippingMainCentreNZ->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(1, $orders->Last()->Modifiers()->Count());
	  $this->assertEquals($shippingMainCentreNZ->ID, $orders->Last()->Modifiers()->First()->ID);
	}
	
	/**
	 * Try to checkout with deleted flat fee shipping option
	 */
	function testCheckoutDeletedFlatFeeShipping() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreNZ = $this->objFromFixture('FlatFeeShippingCountry', 'MainCentreNewZealand');
	  $shippingID = $shippingMainCentreNZ->ID;
	  $shippingAmount = $shippingMainCentreNZ->Amount->getAmount();

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $shippingMainCentreNZ->delete();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  $this->assertEquals(false, $shippingMainCentreNZ->isInDB());
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
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
	  $this->assertEquals(1, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(0, $orders->Last()->Modifiers()->Count());
	}
	
	/**
	 * Try checkout with invalid flat fee shipping option
	 */
	function testCheckoutInvalidFlatFeeShipping() {
	  
	  $productA = $this->objFromFixture('Product', 'productA');
	  $shippingMainCentreAustralia = $this->objFromFixture('FlatFeeShippingCountry', 'MainCentreAustralia');
	  $shippingAmount = $shippingMainCentreAustralia->Amount->getAmount();

	  $this->loginAs('admin');
	  $productA->doPublish();
	  $this->logOut();
	  
	  $this->assertTrue($productA->isPublished());
	  
	  $this->loginAs('buyer');
	  $buyer = $this->objFromFixture('Member', 'buyer');
	  
	  $orders = $buyer->Orders();
	  $this->assertEquals(1, $orders->Count());

	  $this->get(Director::makeRelative($productA->Link())); 
	  $this->submitForm('Form_AddToCartForm', null, array(
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
	  $this->assertEquals(1, $orders->Count());
	  
	  $realTotal = $productA->Amount->getAmount();
	  $this->assertEquals($orders->Last()->Total->getAmount(), $realTotal);
	  
	  $this->assertEquals(0, $orders->Last()->Modifiers()->Count());
	}
}