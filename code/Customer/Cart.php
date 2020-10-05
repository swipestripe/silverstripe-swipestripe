<?php

namespace SwipeStripe\Core\Customer;

use SwipeStripe\Core\Customer\AccountPage;
use SilverStripe\ORM\DataObject;
use SwipeStripe\Core\Customer\CheckoutPage;
use SilverStripe\Control\Director;
use SwipeStripe\Core\Customer\CartPage;
use SilverStripe\Control\Session;
use SwipeStripe\Core\Order\Order;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Core\Extension;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;

/**
 * Extends {@link Page_Controller} adding some functions to retrieve the current cart,
 * and link to the cart.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Cart extends Extension
{
    /**
     * Retrieve the current cart for display in the template.
     *
     * @return Order The current order (cart)
     */
    public function Cart()
    {
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
    public function CartLink($type = Cart::class)
    {
        switch ($type) {
            case 'Account':
                if ($page = DataObject::get_one(AccountPage::class)) {
                    return $page->Link();
                } else {
                    break;
                }
                // no break
            case 'Checkout':
                if ($page = DataObject::get_one(CheckoutPage::class)) {
                    return $page->Link();
                } else {
                    break;
                }
                // no break
            case 'Login':
                return Director::absoluteBaseURL() . 'Security/login';
                break;
            case 'Logout':
                return Director::absoluteBaseURL() . 'Security/logout?BackURL=%2F';
                break;
            case Cart::class:
            default:
                if ($page = DataObject::get_one(CartPage::class)) {
                    return $page->Link();
                } else {
                    break;
                }
        }
    }

    public static function getSession()
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        return $session = $request->getSession();
    }
    /**
     * Get the current order from the session, if order does not exist create a new one.
     *
     * @return Order The current order (cart)
     */
    public static function get_current_order($persist = false)
    {
        $session = self::getSession();

        $orderID = $session->get('Cart.OrderID');
        $order = null;

        if ($orderID) {
            $order = DataObject::get_by_id(Order::class, $orderID);
        }

        if (!$orderID || !$order || !$order->exists()) {
            $order = Order::create();

            if ($persist) {
                $order->write();
                $session->set(Cart::class, [
                    'OrderID' => $order->ID
                ]);
                $session->save();
            }
        }
        return $order;
    }

    /**
     * Updates timestamp LastActive on the order, called on every page request.
     */
    public function onBeforeInit()
    {
        $session = self::getSession();
        $orderID = $session->get('Cart.OrderID');
        if ($orderID && $order = DataObject::get_by_id(Order::class, $orderID)) {
            $order->LastActive = DBDatetime::now()->getValue();
            $order->write();
        }
    }
}
