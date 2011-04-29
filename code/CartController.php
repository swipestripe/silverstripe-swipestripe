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
    $currentOrder->Total->setAmount($currentOrder->Total->getAmount() + $product->Amount->getAmount()); 
    $currentOrder->Total->setCurrency($product->Amount->getCurrency()); 
    $currentOrder->write();
    
    $item = new Item();
    $item->ObjectID = $product->ID;
    $item->ObjectClass = $product->class;
    $item->Amount->setAmount($product->Amount->getAmount());
    $item->Amount->setCurrency($product->Amount->getCurrency());
    $item->OrderID = $currentOrder->ID;
		$item->write();
    
    Director::redirectBack();
  }
  
  /**
   * Remove an item from the cart
   */
  function remove() {
    
    $product = $this->getProduct();

    $currentOrder = self::get_current_order();
    $currentOrder->Total->setAmount($currentOrder->Total->getAmount() - $product->Amount->getAmount()); 
    $currentOrder->write();
    
    $item = DataObject::get_one('Item', "ObjectID = $product->ID AND ObjectClass = '$product->class' AND OrderID = $currentOrder->ID");
		$item->delete();
    
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
   * Get the current order from the session
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
