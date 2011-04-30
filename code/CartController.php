<?php
class CartController extends Controller {

  static $URLSegment = 'cart';

  static $session_name = 'Cart';
  
  static $use_draft_site = true; 

  static $allowed_actions = array (
  	'add',
    'remove',
    'clear'
  );
  
  /**
   * Clear the cart by clearing the session
   */
  function clear() {
    Session::clear_all();
    Session::clear('Cart.OrderID');
    Director::redirectBack();
  }

  /**
   * Add an item to the cart
   */
  function add() {

    $product = $this->getProduct();

    $currentOrder = self::get_current_order();
    $currentOrder->addItem($product);

    Director::redirectBack();
  }
  
  /**
   * Remove an item from the cart
   */
  function remove() {
    
    $product = $this->getProduct();

    $currentOrder = self::get_current_order();
    $currentOrder->removeItem($product);

    Director::redirectBack();
  }
  
  /**
   * Find a product based on current request
   * 
   * @return DataObject 
   */
  private function getProduct() {
    //Get the request (SS_HTTPRequest)
    $request = $this->getRequest();
    
    //Create a product to add to the current order
    $productClassName = $request->requestVar('ProductClass');
    $productID = $request->requestVar('ProductID');
    return DataObject::get_by_id($productClassName, $productID);
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
