<?php

namespace SwipeStripe\Core\Customer;

use SilverStripe\View\Requirements;
use SwipeStripe\Core\Customer\Customer;
use SwipeStripe\Core\Form\OrderForm;

/**
 * Display the checkout page, with order form. Process the order - send the order details
 * off to the Payment class.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPageController extends \PageController
{
    protected $orderProcessed = false;

    private static $allowed_actions = [
        'index',
        'OrderForm'
    ];

    /**
     * Include some CSS and javascript for the checkout page
     *
     * TODO why didn't I use init() here?
     *
     * @return Array Contents for page rendering
     */
    public function index()
    {
        //Update stock levels
        //Order::delete_abandoned();

        Requirements::css('swipestripe/swipestripe: css/Shop.css');

        return [
            'Content' => $this->Content,
            'Form' => $this->OrderForm()
        ];
    }

    public function OrderForm()
    {
        $order = Cart::get_current_order();
        $member = Customer::currentUser() ? Customer::currentUser() : singleton(Customer::class);

        $form = OrderForm::create(
            $this,
            OrderForm::class
        )->disableSecurityToken();

        //Populate fields the first time form is loaded
        $form->populateFields();

        return $form;
    }
}
