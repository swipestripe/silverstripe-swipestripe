<?php

namespace SwipeStripe\Core\Customer;

use SilverStripe\View\Requirements;
use SwipeStripe\Core\code\Order\Order;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Session;
use SwipeStripe\Core\code\Form\RepayForm;

/**
 * Display the account page with listing of previous orders, and display an individual order.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage_Controller extends PageController
{
    /**
     * Allowed actions that can be invoked.
     *
     * @var Array Set of actions
     */
    private static $allowed_actions = [
        'index',
        'order',
        'repay',
        'RepayForm'
    ];

    public function init()
    {
        parent::init();

        if (!Permission::check('VIEW_ORDER')) {
            return $this->redirect(Director::absoluteBaseURL() . 'Security/login?BackURL=' . urlencode($this->getRequest()->getVar('url')));
        }
    }

    /**
     * Check access permissions for account page and return content for displaying the
     * default page.
     *
     * @return Array Content data for displaying the page.
     */
    public function index()
    {
        Requirements::css('swipestripe/css/Shop.css');

        return [
            'Content' => $this->Content,
            'Form' => $this->Form,
            'Orders' => Order::get()
                ->where('MemberID = ' . Convert::raw2sql(Member::currentUserID()))
                ->sort('Created DESC'),
            'Customer' => Customer::currentUser()
        ];
    }

    /**
     * Return the {@link Order} details for the current Order ID that we're viewing (ID parameter in URL).
     *
     * @return Array Content for displaying the page
     */
    public function order($request)
    {
        Requirements::css('swipestripe/css/Shop.css');

        if ($orderID = $request->param('ID')) {
            $member = Customer::currentUser();
            $order = Order::get()
                ->where('"Order"."ID" = ' . Convert::raw2sql($orderID))
                ->First();

            if (!$order || !$order->exists()) {
                return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
            }

            if (!$order->canView($member)) {
                return $this->httpError(403, _t('AccountPage.CANNOT_VIEW_ORDER', 'You cannot view orders that do not belong to you.'));
            }

            return [
                'Order' => $order
            ];
        } else {
            return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
        }
    }

    public function repay($request)
    {
        Requirements::css('swipestripe/css/Shop.css');

        if ($orderID = $request->param('ID')) {
            $member = Customer::currentUser();
            $order = Order::get()
                ->where('"Order"."ID" = ' . Convert::raw2sql($orderID))
                ->First();

            if (!$order || !$order->exists()) {
                return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
            }

            if (!$order->canView($member)) {
                return $this->httpError(403, _t('AccountPage.CANNOT_VIEW_ORDER', 'You cannot view orders that do not belong to you.'));
            }

            Session::set('Repay', [
                'OrderID' => $order->ID
            ]);
            Session::save();

            return [
                'Order' => $order,
                'RepayForm' => $this->RepayForm()
            ];
        } else {
            return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
        }
    }

    public function RepayForm()
    {
        $form = RepayForm::create(
            $this,
            RepayForm::class
        )->disableSecurityToken();

        //Populate fields the first time form is loaded
        $form->populateFields();

        return $form;
    }
}
