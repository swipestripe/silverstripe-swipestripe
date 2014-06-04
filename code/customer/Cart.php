<?php
/**
 * Extends {@link Page_Controller} adding some functions to retrieve the current cart, 
 * and link to the cart.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Cart extends Extension {

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
	function CartLink($type = 'Cart') {
		switch ($type) {
			case 'Account':
				if ($page = DataObject::get_one('AccountPage')) return $page->Link();
				else break;
			case 'Checkout':
				if ($page = DataObject::get_one('CheckoutPage')) return $page->Link();
				else break;
			case 'Login':
				return Director::absoluteBaseURL() . 'Security/login';
				break;
			case 'Logout':
				return Director::absoluteBaseURL() . 'Security/logout?BackURL=%2F';
				break;
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
	public static function get_current_order($persist = false) {

		$orderID = Session::get('Cart.OrderID');
		$order = null;
		
		if ($orderID) {
			$order = DataObject::get_by_id('Order', $orderID);
		}
		
		if (!$orderID || !$order || !$order->exists()) {
			$order = Order::create();

			if ($persist) {
				$order->write();
				Session::set('Cart', array(
					'OrderID' => $order->ID
				));
				Session::save();
			}
		}
		return $order;
	}

	/**
	 * Updates timestamp LastActive on the order, called on every page request. 
	 */
	function onBeforeInit() {

		$orderID = Session::get('Cart.OrderID');
		if ($orderID && $order = DataObject::get_by_id('Order', $orderID)) {
			$order->LastActive = SS_Datetime::now()->getValue();
			$order->write();
		}
	}
}