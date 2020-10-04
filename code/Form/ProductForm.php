<?php

namespace SwipeStripe\Core\Form;

use FormResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\Requirements;
use SwipeStripe\Core\Product\Price;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Session;
use SilverStripe\Forms\Form;
use SwipeStripe\Core\Customer\Cart;
use SwipeStripe\Core\Customer\CartPage;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SwipeStripe\Core\Product\Variation;
use SilverStripe\ORM\ArrayList;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\NumericField;

/**
 * Form for adding items to the cart from a {@link Product} page.
 */
class ProductForm extends Form
{
    protected $product;
    protected $quantity;
    protected $redirectURL;

    private static $allowed_actions = [
        'add'
    ];

    public function __construct($controller, $name, $quantity = null, $redirectURL = null)
    {
        parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript('swipestripe/javascript/ProductForm.js');

        $this->product = $controller->data();
        $this->quantity = $quantity;
        $this->redirectURL = $redirectURL;

        $this->fields = $this->createFields();
        $this->actions = $this->createActions();
        $this->validator = $this->createValidator();

        $this->setupFormErrors();

        $this->addExtraClass('product-form');

        //Add a map of all variations and prices to the page for updating the price
        $map = [];
        $variations = $this->product->Variations();
        $productPrice = $this->product->Price();

        if ($variations && $variations->exists()) {
            foreach ($variations as $variation) {
                if ($variation->isEnabled()) {
                    $variationPrice = $variation->Price();

                    $amount = Price::create();
                    $amount->setAmount($productPrice->getAmount() + $variationPrice->getAmount());
                    $amount->setCurrency($productPrice->getCurrency());
                    $amount->setSymbol($productPrice->getSymbol());

                    $map[] = [
                        'price' => $amount->Nice(),
                        'options' => $variation->Options()->column('ID'),
                        'free' => _t('Product.FREE', 'Free'),
                    ];
                }
            }
        }

        $this->setAttribute('data-map', json_encode($map));
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
        $product = $this->product;

        $fields = FieldList::create(
            HiddenField::create('ProductClass', 'ProductClass', $product->ClassName),
            HiddenField::create('ProductID', 'ProductID', $product->ID),
            HiddenField::create('Redirect', 'Redirect', $this->redirectURL)
        );

        $attributes = $this->product->Attributes();
        $prev = null;

        if ($attributes && $attributes->exists()) {
            foreach ($attributes as $attribute) {
                $field = $attribute->getOptionField($prev);
                $fields->push($field);

                $prev = $attribute;
            }
        }

        $fields->push(ProductForm_QuantityField::create(
            'Quantity',
            _t('ProductForm.QUANTITY', 'Quantity'),
            is_numeric($this->quantity) ? $this->quantity : 1
        ));

        $this->extend('updateFields', $fields);
        $fields->setForm($this);
        return $fields;
    }

    public function createActions()
    {
        $actions = new FieldList(
            new FormAction('add', _t('ProductForm.ADD_TO_CART', 'Add To Cart'))
        );

        $this->extend('updateActions', $actions);
        $actions->setForm($this);
        return $actions;
    }

    public function createValidator()
    {
        $validator = new ProductForm_Validator(
            'ProductClass',
            'ProductID',
            'Quantity'
        );

        $this->extend('updateValidator', $validator);
        $validator->setForm($this);
        return $validator;
    }

    /**
     * Overloaded so that form error messages are displayed.
     *
     * @see OrderFormValidator::php()
     * @see Form::validate()
     */
    public function validate()
    {
        if ($this->validator) {
            $errors = $this->validator->validate();

            if ($errors) {
                if (Director::is_ajax()) { // && $this->validator->getJavascriptValidationHandler() == 'prototype') {
                    FormResponse::status_message(_t('Form.VALIDATIONFAILED', 'Validation failed'), 'bad');
                    foreach ($errors as $error) {
                        FormResponse::add(sprintf(
                            "validationError('%s', '%s', '%s');\n",
                            Convert::raw2js($error['fieldName']),
                            Convert::raw2js($error['message']),
                            Convert::raw2js($error['messageType'])
                        ));
                    }
                } else {
                    $data = $this->getData();

                    $formError = [];
                    if ($formMessageType = $this->MessageType()) {
                        $formError['message'] = $this->Message();
                        $formError['messageType'] = $formMessageType;
                    }

                    // Load errors into session and post back
                    Session::set("FormInfo.{$this->FormName()}", [
                        'errors' => $errors,
                        'data' => $data,
                        'formError' => $formError
                    ]);
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Add an item to the current cart ({@link Order}) for a given {@link Product}.
     *
     * @param Array $data
     * @param Form $form
     */
    public function add(array $data, Form $form)
    {
        Cart::get_current_order(true)
            ->addItem(
                $this->getProduct(),
                $this->getVariation(),
                $this->getQuantity(),
                $this->getOptions()
            );

        //Show feedback if redirecting back to the Product page
        if (!$this->getRequest()->requestVar('Redirect')) {
            $cartPage = DataObject::get_one(CartPage::class);
            $message = _t('ProductForm.PRODUCT_ADDED', 'The product was added to your cart.');
            if ($cartPage->exists()) {
                $message = _t(
                    'ProductForm.PRODUCT_ADDED_LINK',
                    'The product was added to {openanchor}your cart{closeanchor}.',
                    [
                        'openanchor' => "<a href=\"{$cartPage->Link()}\">",
                        'closeanchor' => '</a>'
                    ]
                );
            }
            $form->sessionMessage(
                DBField::create_field('HTMLText', $message),
                'good',
                false
            );
        }
        $this->goToNextPage();
    }

    /**
     * Find a product based on current request - maybe shoul dbe deprecated?
     *
     * @see SS_HTTPRequest
     * @return DataObject
     */
    private function getProduct()
    {
        $request = $this->getRequest();
        return DataObject::get_by_id($request->requestVar('ProductClass'), $request->requestVar('ProductID'));
    }

    private function getVariation()
    {
        $productVariation = new Variation();
        $request = $this->getRequest();
        $options = $request->requestVar('Options');
        $product = $this->product;
        $variations = $product->Variations();

        if ($variations && $variations->exists()) {
            foreach ($variations as $variation) {
                $variationOptions = $variation->Options()->map('AttributeID', 'ID')->toArray();
                if ($options == $variationOptions && $variation->isEnabled()) {
                    $productVariation = $variation;
                }
            }
        }

        return $productVariation;
    }

    /**
     * Find the quantity based on current request
     *
     * @return Int
     */
    private function getQuantity()
    {
        $quantity = $this->getRequest()->requestVar('Quantity');
        return (isset($quantity) && is_numeric($quantity)) ? $quantity : 1;
    }

    private function getOptions()
    {
        $options = new ArrayList();
        $this->extend('updateOptions', $options);
        return $options;
    }

    /**
     * Send user to next page based on current request vars,
     * if no redirect is specified redirect back.
     *
     * TODO make this work with AJAX
     */
    private function goToNextPage()
    {
        $redirectURL = $this->getRequest()->requestVar('Redirect');

        //Check if on site URL, if so redirect there, else redirect back
        if ($redirectURL && Director::is_site_url($redirectURL)) {
            $this->controller->redirect(Director::absoluteURL(Director::baseURL() . $redirectURL));
        } else {
            $this->controller->redirectBack();
        }
    }
}
