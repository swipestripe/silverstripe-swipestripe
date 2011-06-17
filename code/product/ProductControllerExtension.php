<?php

class ProductControllerExtension extends Extension {
  
  public static $allowed_actions = array (
  	'add',
    'remove',
    'clear'
  );
  
  /**
   * Clear the cart by clearing the session
   */
  function clear() {
    Session::clear('Cart.OrderID');
    $this->goToNextPage();
  }

  /**
   * Add an item to the cart
   */
  function add() {
    self::get_current_order()->addItem($this->getProduct(), $this->getQuantity());
    $this->goToNextPage();
  }
  
  /**
   * Remove an item from the cart
   */
  function remove() {
    self::get_current_order()->removeItem($this->getProduct(), $this->getQuantity());
    $this->goToNextPage();
  }
  
  /**
   * Find a product based on current request
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProduct() {
    $request = $this->owner->getRequest();
    return DataObject::get_by_id($request->requestVar('ProductClass'), $request->requestVar('ProductID'));
  }
  
  /**
   * Find the quantity based on current request
   * 
   * @return Int
   */
  private function getQuantity() {
    return $this->owner->getRequest()->requestVar('Quantity');
  }
  
  /**
   * Send user to next page based on current request vars,
   * if no redirect is specified redirect back.
   * 
   * TODO make this work with AJAX
   */
  private function goToNextPage() {
    $redirectURL = $this->owner->getRequest()->requestVar('Redirect');

    //Check if on site URL, if so redirect there, else redirect back
    if ($redirectURL && Director::is_site_url($redirectURL)) Director::redirect(Director::absoluteURL(Director::baseURL() . $redirectURL));
    else Director::redirectBack();
  }

  /**
   * Retrieve the current cart
   * 
   * @return Order 
   */
  function Cart() {
    $order = self::get_current_order();
    $order->Items();
    $order->Total;

		//HTTP::set_cache_age(0);
		return $order;
	}
	
	/**
   * Get the current order from the session, if order does not exist
   * John Connor it (create a new order)
   * 
   * @return Order
   */
  static function get_current_order() {

    $orderID = Session::get('Cart.OrderID');
    
    if ($orderID) {
      $order = DataObject::get_by_id('Order', $orderID);
    }
    else {
      $order = new Order();
      $order->write();
      Session::set('Cart', array(
        'OrderID' => $order->ID
      ));
      Session::save();
    }
    
    return $order;
  }
  
}
