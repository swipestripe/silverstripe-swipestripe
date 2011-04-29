<?php
class CartTest extends FunctionalTest {
  
	static $fixture_file = 'simplecart/tests/CartTest.yml';
	static $disable_themes = true;

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

}