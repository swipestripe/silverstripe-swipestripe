<?php

namespace SwipeStripe\Core\tests;

use SwipeStripe\Core\Customer\CheckoutPage;
use SwipeStripe\Core\Customer\AccountPage;
use SwipeStripe\Core\Customer\CartPage;
use SilverStripe\Core\Config\Config;
use SwipeStripe\Core\Customer\Customer;
use SwipeStripe\Core\Product\Product;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SwipeStripe\Core\Order\Order;
use SwipeStripe\Core\Admin\ShopConfig;

/**
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage tests
 */
class SWS_OrderTest extends SWS_Test
{
    public function setUp()
    {
        parent::setUp();

        //Check that payment module is installed
        $this->assertTrue(class_exists('Payment'), 'Payment module is installed.');
        $this->assertTrue(class_exists('ChequeGateway'), 'Cheque Payment is installed.');

        //Need to publish a few pages because not using the draft site
        $this->loginAs('admin');
        $this->objFromFixture(CheckoutPage::class, 'checkout')->doPublish();
        $this->objFromFixture(AccountPage::class, 'account')->doPublish();
        $this->objFromFixture(CartPage::class, 'cart')->doPublish();
        $this->logOut();

        Config::inst()->remove('PaymentProcessor', 'supported_methods');
        Config::inst()->update('PaymentProcessor', 'supported_methods', ['test' => ['Cheque']]);
        Config::inst()->remove('PaymentGateway', 'environment');
        Config::inst()->update('PaymentGateway', 'environment', 'test');
    }

    public function testOrderStatusAfterCheckout()
    {
        $buyer = $this->objFromFixture(Customer::class, 'buyer');
        $productA = $this->objFromFixture(Product::class, 'productA');
        $checkoutPage = $this->objFromFixture(CheckoutPage::class, 'checkout');

        $this->loginAs('admin');
        $productA->doPublish();
        $this->logOut();

        $this->loginAs($buyer);

        $this->get(Director::makeRelative($productA->Link()));
        $this->submitForm('ProductForm_ProductForm', null, [
            'Quantity' => 1
        ]);

        $this->get(Director::makeRelative($checkoutPage->Link()));

        $this->submitForm('OrderForm_OrderForm', null, [
            'Notes' => 'New order for test buyer.'
        ]);

        DataObject::flush_and_destroy_cache();

        $order = $buyer->Orders()->First();

        $this->assertEquals(Order::STATUS_PROCESSING, $order->Status);
        $this->assertEquals('Paid', $order->PaymentStatus);
    }

    public function testOrderPaymentStatusUpdated()
    {
        $order = $this->objFromFixture(Order::class, 'orderOne');
        $payment = $order->Payments()->First();

        $this->assertEquals('Paid', $order->PaymentStatus);

        $this->loginAs('admin');
        $payment->Status = 'Pending';
        $payment->write();
        $this->logOut();

        $order = $this->objFromFixture(Order::class, 'orderOne');
        $this->assertEquals('Unpaid', $order->PaymentStatus);

        $this->loginAs('admin');
        $payment->Status = 'Success';
        $payment->write();
        $this->logOut();

        DataObject::flush_and_destroy_cache();

        $order = $this->objFromFixture(Order::class, 'orderOne');
        $this->assertEquals('Paid', $order->PaymentStatus);
    }

    public function testOrderEmailsSentAfterCheckout()
    {
        $buyer = $this->objFromFixture(Customer::class, 'buyer');
        $productA = $this->objFromFixture(Product::class, 'productA');
        $checkoutPage = $this->objFromFixture(CheckoutPage::class, 'checkout');
        $shopConfig = $this->objFromFixture(ShopConfig::class, 'config');

        $this->loginAs('admin');
        $productA->doPublish();
        $this->logOut();

        $this->loginAs($buyer);

        $this->get(Director::makeRelative($productA->Link()));
        $this->submitForm('ProductForm_ProductForm', null, [
            'Quantity' => 1
        ]);

        $this->get(Director::makeRelative($checkoutPage->Link()));

        $this->submitForm('OrderForm_OrderForm', null, [
            'Notes' => 'New order for test buyer.'
        ]);

        $this->assertEmailSent($buyer->Email, $shopConfig->ReceiptFrom, '/Receipt for order.*/');
        $this->assertEmailSent($shopConfig->NotificationTo, $buyer->Email, '/Notification for order.*/');
    }
}
