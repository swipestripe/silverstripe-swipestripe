<?php

namespace SwipeStripe\Core\Customer;

/**
 * Display the cart page, with cart form. Handle cart form actions.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CartPageController extends \PageController
{
    private static $allowed_actions = [
        'index',
        'CartForm'
    ];

    /**
     * Include some CSS for the cart page.
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
            'Form' => $this->Form
        ];
    }

    /**
     * Form including quantities for items for displaying on the cart page.
     *
     * @return CartForm A new cart form
     */
    public function CartForm()
    {
        return CartForm::create(
            $this,
            CartForm::class
        )->disableSecurityToken();
    }
}
