<?php

namespace SwipeStripe\Core\Form;

use SS_Log;
use Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\Requirements;
use SwipeStripe\Core\code\Customer\Cart;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Form;
use SwipeStripe\Core\code\Customer\CheckoutPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Debug;
use SwipeStripe\Core\code\Form\CartForm_QuantityField;
use SwipeStripe\Core\code\Order\Item;

/**
 * Form to display the {@link Order} contents on the {@link CartPage}.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class CartForm extends Form
{
    /**
     * The current {@link Order} (cart).
     *
     * @var Order
     */
    public $order;

    /**
     * Construct the form, set the current order and the template to be used for rendering.
     *
     * @param Controller $controller
     * @param String $name
     * @param FieldList $fields
     * @param FieldList $actions
     * @param Validator $validator
     * @param Order $currentOrder
     */
    public function __construct($controller, $name)
    {
        parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript('swipestripe/javascript/CartForm.js');

        $this->order = Cart::get_current_order();

        $this->fields = $this->createFields();
        $this->actions = $this->createActions();
        $this->validator = $this->createValidator();

        $this->setupFormErrors();

        $this->addExtraClass('cart-form');
        $this->setTemplate(CartForm::class);
    }

    /**
     * Set up current form errors in session to
     * the current form if appropriate.
     */
    public function setupFormErrors()
    {
        //Only run when fields exist
        if ($this->fields->exists()) {
            parent::setupFormErrors();
        }
    }

    public function createFields()
    {
        $fields = FieldList::create();
        $items = $this->order->Items();

        if ($items) {
            foreach ($items as $item) {
                $fields->push(CartForm_QuantityField::create(
                    'Quantity[' . $item->ID . ']',
                    $item->Quantity,
                    $item
                ));
            }
        }

        $this->extend('updateFields', $fields);
        $fields->setForm($this);
        return $fields;
    }

    public function createActions()
    {
        $actions = FieldList::create(
            FormAction::create('updateCart', _t('CartForm.UPDATE_CART', 'Update Cart')),
            FormAction::create('goToCheckout', _t('CartForm.GO_TO_CHECKOUT', 'Go To Checkout'))
        );
        $this->extend('updateActions', $actions);
        $actions->setForm($this);
        return $actions;
    }

    public function createValidator()
    {
        $validator = RequiredFields::create();

        $items = $this->order->Items();
        if ($items) {
            foreach ($items as $item) {
                $validator->addRequiredField('Quantity[' . $item->ID . ']');
            }
        }

        $this->extend('updateValidator', $validator);
        $validator->setForm($this);
        return $validator;
    }

    /**
     * Update the current cart quantities then redirect back to the cart page.
     *
     * @param Array $data Data submitted from the form via POST
     * @param Form $form Form that data was submitted from
     */
    public function updateCart(array $data, Form $form)
    {
        $this->saveCart($data, $form);
        $this->controller->redirectBack();
    }

    /**
     * Update the current cart quantities and redirect to checkout.
     *
     * @param Array $data Data submitted from the form via POST
     * @param Form $form Form that data was submitted from
     */
    public function goToCheckout(array $data, Form $form)
    {
        $this->saveCart($data, $form);

        if ($checkoutPage = DataObject::get_one(CheckoutPage::class)) {
            $this->controller->redirect($checkoutPage->AbsoluteLink());
        } else {
            Debug::friendlyError(500);
        }
    }

    /**
     * Save the cart, update the order item quantities and the order total.
     *
     * @param Array $data Data submitted from the form via POST
     * @param Form $form Form that data was submitted from
     */
    private function saveCart(array $data, Form $form)
    {
        $currentOrder = Cart::get_current_order();
        $quantities = (isset($data['Quantity'])) ? $data['Quantity'] : null;

        if ($quantities) {
            foreach ($quantities as $itemID => $quantity) {
                if ($item = $currentOrder->Items()->find('ID', $itemID)) {
                    if ($quantity == 0) {
                        SS_Log::log(new Exception(print_r($item->toMap(), true)), SS_Log::NOTICE);

                        $item->delete();
                    } else {
                        $item->Quantity = $quantity;
                        $item->write();
                    }
                }
            }
        }
        $currentOrder->updateTotal();
    }

    /*
     * Retrieve the current {@link Order} which is the cart.
     *
     * @return Order The current order (cart)
     */
    public function Cart()
    {
        return $this->order;
    }
}
