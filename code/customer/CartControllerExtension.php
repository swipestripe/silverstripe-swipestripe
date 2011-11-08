<?php
/**
 * Extends {@link Page_Controller} adding some functions to retrieve the current cart, 
 * and link to the cart.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage customer
 * @version 1.0
 */
class CartControllerExtension extends Extension {

  /**
   * Retrieve the current cart for display in the template.
   * 
   * @return Order The current order (cart)
   */
  function Cart() {
    $order = self::get_current_order();
    $order->Items();
    $order->Total;

		//HTTP::set_cache_age(0);
		return $order;
	}
	
	/**
   * Convenience method to return links to cart related page.
   * 
   * @param String $type The type of cart page a link is needed for
   * @return String The URL to the particular page
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
	
	/**
   * Get the current order from the session, if order does not exist create a new one.
   * 
   * @return Order The current order (cart)
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
	 * Updates timestamp LastActive on the order, called on every page request. 
	 * This is for a stock level solution which is not currently implemented.
	 */
  public function onBeforeInit() {

    $orderID = Session::get('Cart.OrderID');
    if ($orderID && $order = DataObject::get_by_id('Order', $orderID)) {
      $order->LastActive = SS_Datetime::now()->getValue();
      $order->write();
    }
  }
}