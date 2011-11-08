<?php

class CartControllerExtension extends Extension {
  
  public static $allowed_actions = array (
  );
  
  /**
   * Clear the cart by clearing the session
   * TODO is this in the right place?
   * 
   * @deprecated
   */
  function clear() {
    
    return;
    
    Session::clear('Cart.OrderID');
    $this->goToNextPage();
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
	
	/**
	 * Updates timestamp LastActive on the order, should be called on every request
	 */
  public function onBeforeInit() {

    $orderID = Session::get('Cart.OrderID');
    if ($orderID && $order = DataObject::get_by_id('Order', $orderID)) {
      $order->LastActive = SS_Datetime::now()->getValue();
      $order->write();
    }
  }
  
  /**
   * Get links to cart pages
   * 
   * @param String $type
   */
  public function CartLink($type = 'Cart') {
    switch ($type) {
      case 'Account':
        if ($page = DataObject::get_one('AccountPage')) return $page->Link();
        else break;
      case 'Checkout':
        if ($page = DataObject::get_one('CheckoutPage')) return $page->Link();
        else break;
      case 'Logout':
        if ($page = DataObject::get_one('AccountPage')) return $page->Link() . 'logout';
        else break;
      case 'Cart':
      default:
        if ($page = DataObject::get_one('CartPage')) return $page->Link();
        else break;
    }
  }
}