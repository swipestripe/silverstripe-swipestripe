<?php

class ProductControllerExtension extends Extension {
  
  public static $allowed_actions = array (
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
  
}