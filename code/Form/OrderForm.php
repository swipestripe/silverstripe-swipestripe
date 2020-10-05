<?php

namespace SwipeStripe\Core\Form;

use PaymentProcessor;
use PaymentFactory;
use DropDownField;
use Exception;
use SS_Log;
use SilverStripe\Forms\FieldList;
use SilverStripe\View\Requirements;
use SwipeStripe\Core\Customer\Cart;
use SwipeStripe\Core\Customer\Customer;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Session;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SwipeStripe\Core\Order\Order;
use SilverStripe\ORM\FieldType\DBDatetime;
use SwipeStripe\Core\Form\OrderFormValidator;
use SwipeStripe\Core\Order\OrderUpdate;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SwipeStripe\Core\Form\OrderFormItemField;
use SwipeStripe\Core\Order\Item;

/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 */
class OrderForm extends Form
{
    protected $order;
    protected $customer;

    private static $allowed_actions = [
        'process',
        'update'
    ];

    /**
     * Construct the form, get the grouped fields and set the fields for this form appropriately,
     * the fields are passed in an associative array so that the fields can be grouped into sets
     * making it easier for the template to grab certain fields for different parts of the form.
     *
     * @param Controller $controller
     * @param String $name
     * @param Array $groupedFields Associative array of fields grouped into sets
     * @param FieldList $actions
     * @param Validator $validator
     * @param Order $currentOrder
     */
    public function __construct($controller, $name)
    {
        parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript('swipestripe/javascript/OrderForm.js');

        $this->order = Cart::get_current_order();
        $this->customer = Customer::currentUser() ? Customer::currentUser() : singleton(Customer::class);

        $this->fields = $this->createFields();
        $this->actions = $this->createActions();
        $this->validator = $this->createValidator();

        $this->setupFormErrors();

        $this->setTemplate(OrderForm::class);
        $this->addExtraClass('order-form');
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
        $order = $this->order;
        $member = $this->customer;

        //Personal details fields
        if (!$member->ID || $member->Password == '') {
            $link = $this->controller->Link();

            $note = _t('CheckoutPage.NOTE', 'NOTE:');
            $passwd = _t('CheckoutPage.PLEASE_CHOOSE_PASSWORD', 'Please choose a password, so you can login and check your order history in the future.');
            $mber = sprintf(
                _t('CheckoutPage.ALREADY_MEMBER', 'If you are already a member please %s log in. %s'),
                "<a href=\"Security/login?BackURL=$link\">",
                '</a>'
            );

            $personalFields = CompositeField::create(
                new HeaderField(_t('CheckoutPage.ACCOUNT', 'Account'), 3),
                new CompositeField(
                    EmailField::create(Email::class, _t('CheckoutPage.EMAIL', Email::class))
                        ->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_EMAIL_ADDRESS', 'Please enter your email address.'))
                ),
                new CompositeField(
                    TextField::create('Phone', _t('CheckoutPage.PHONE', 'Phone'))
                ),
                new CompositeField(
                    new FieldGroup(
                        new ConfirmedPasswordField('Password', _t('CheckoutPage.PASSWORD', 'Password'))
                    )
                ),
                new CompositeField(
                    new LiteralField(
                        'AccountInfo',
                        "
						<p class=\"alert alert-info\">
							<strong class=\"alert-heading\">$note</strong>
							$passwd <br /><br />
							$mber
						</p>
						"
                    )
                )
            )->setID('PersonalDetails')->setName('PersonaDetails');
        }

        //Order item fields
        $items = $order->Items();
        $itemFields = CompositeField::create()->setName('ItemsFields');
        if ($items) {
            foreach ($items as $item) {
                $itemFields->push(new OrderFormItemField($item));
            }
        }

        //Order modifications fields
        $subTotalModsFields = CompositeField::create()->setName('SubTotalModificationsFields');
        $subTotalMods = $order->SubTotalModifications();

        if ($subTotalMods && $subTotalMods->exists()) {
            foreach ($subTotalMods as $modification) {
                $modFields = $modification->getFormFields();
                foreach ($modFields as $field) {
                    $subTotalModsFields->push($field);
                }
            }
        }

        $totalModsFields = CompositeField::create()->setName('TotalModificationsFields');
        $totalMods = $order->TotalModifications();

        if ($totalMods && $totalMods->exists()) {
            foreach ($totalMods as $modification) {
                $modFields = $modification->getFormFields();
                foreach ($modFields as $field) {
                    $totalModsFields->push($field);
                }
            }
        }

        //Payment fields
        $supported_methods = PaymentProcessor::get_supported_methods();

        $source = [];
        foreach ($supported_methods as $methodName) {
            $methodConfig = PaymentFactory::get_factory_config($methodName);
            $source[$methodName] = $methodConfig['title'];
        }

        $paymentFields = CompositeField::create(
            new HeaderField(_t('CheckoutPage.PAYMENT', 'Payment'), 3),
            DropDownField::create(
                'PaymentMethod',
                _t('CheckoutPage.SELECTPAYMENT', 'Select Payment Method'),
                $source
            )->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD', 'Please select a payment method.'))
        )->setName('PaymentFields');

        $fields = FieldList::create(
            $itemFields,
            $subTotalModsFields,
            $totalModsFields,
            $notesFields = CompositeField::create(
                TextareaField::create('Notes', _t('CheckoutPage.NOTES_ABOUT_ORDER', 'Notes about this order'))
            )->setName('NotesFields'),
            $paymentFields
        );

        if (isset($personalFields)) {
            $fields->push($personalFields);
        }

        $this->extend('updateFields', $fields);
        $fields->setForm($this);
        return $fields;
    }

    public function createActions()
    {
        $actions = FieldList::create(
            new FormAction('process', _t('CheckoutPage.PROCEED_TO_PAY', 'Proceed to pay'))
        );

        $this->extend('updateActions', $actions);
        $actions->setForm($this);
        return $actions;
    }

    public function createValidator()
    {
        $validator = OrderFormValidator::create(
            'PaymentMethod'
        );

        if (!$this->customer->ID || $this->customer->Password == '') {
            $validator->addRequiredField('Password');
            $validator->addRequiredField(Email::class);
        }

        $this->extend('updateValidator', $validator);
        $validator->setForm($this);
        return $validator;
    }

    public function getPersonalDetailsFields()
    {
        return $this->Fields()->fieldByName('PersonalDetails');
    }

    public function getItemsFields()
    {
        return $this->Fields()->fieldByName('ItemsFields')->FieldList();
    }

    public function getSubTotalModificationsFields()
    {
        return $this->Fields()->fieldByName('SubTotalModificationsFields')->FieldList();
    }

    public function getTotalModificationsFields()
    {
        return $this->Fields()->fieldByName('TotalModificationsFields')->FieldList();
    }

    public function getNotesFields()
    {
        return $this->Fields()->fieldByName('NotesFields');
    }

    public function getPaymentFields()
    {
        return $this->Fields()->fieldByName('PaymentFields');
    }

    /**
     * Helper function to return the current {@link Order}, used in the template for this form
     *
     * @return Order
     */
    public function Cart()
    {
        return $this->order;
    }

    /**
     * Overloaded so that form error messages are displayed.
     *
     * @see OrderFormValidator::php()
     * @see Form::validate()
     */
    public function validate()
    {
        $valid = true;
        if ($this->validator) {
            $errors = $this->validator->validate();

            if ($errors) {
                // Load errors into session and post back
                $data = $this->getData();
                Session::set("FormInfo.{$this->FormName()}.errors", $errors);
                Session::set("FormInfo.{$this->FormName()}.data", $data);
                $valid = false;
            }
        }
        return $valid;
    }

    public function process($data, $form)
    {
        $this->extend('onBeforeProcess', $data);

        //Check payment type
        try {
            $paymentMethod = Convert::raw2sql($data['PaymentMethod']);
            $paymentProcessor = PaymentFactory::factory($paymentMethod);
        } catch (Exception $e) {
            Debug::friendlyError(
                403,
                _t('CheckoutPage.NOT_VALID_METHOD', 'Sorry, that is not a valid payment method.'),
                _t('CheckoutPage.TRY_AGAIN', 'Please go back and try again.')
            );
            return;
        }

        //Save or create a new customer/member
        $member = Customer::currentUser() ? Customer::currentUser() : singleton(Customer::class);
        if (!$member->exists()) {
            $existingCustomer = Customer::get()->filter(Email::class, $data[Email::class]);
            if ($existingCustomer && $existingCustomer->exists()) {
                $form->sessionMessage(
                    _t('CheckoutPage.MEMBER_ALREADY_EXISTS', 'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.'),
                    'bad'
                );
                $this->controller->redirectBack();
                return false;
            }

            $member = Customer::create();
            $form->saveInto($member);
            $member->write();
            $member->addToGroupByCode('customers');
            $member->logIn();
        }

        //Save the order
        $order = Cart::get_current_order();
        $items = $order->Items();

        $form->saveInto($order);
        $order->MemberID = $member->ID;
        $order->Status = Order::STATUS_PENDING;
        $order->OrderedOn = DBDatetime::now()->getValue();
        $order->write();

        //Saving an update on the order
        if ($notes = $data['Notes']) {
            $update = new OrderUpdate();
            $update->Note = $notes;
            $update->Visible = true;
            $update->OrderID = $order->ID;
            $update->MemberID = $member->ID;
            $update->write();
        }

        //Add modifiers to order
        $order->updateModifications($data)->write();

        Session::clear('Cart.OrderID');

        $order->onBeforePayment();

        try {
            $shopConfig = ShopConfig::current_shop_config();
            $precision = $shopConfig->BaseCurrencyPrecision;

            $paymentData = [
                'Amount' => number_format($order->Total()->getAmount(), $precision, '.', ''),
                'Currency' => $order->Total()->getCurrency(),
                'Reference' => $order->ID
            ];
            $paymentProcessor->payment->OrderID = $order->ID;
            $paymentProcessor->payment->PaidByID = $member->ID;

            $paymentProcessor->setRedirectURL($order->Link());
            $paymentProcessor->capture($paymentData);
        } catch (Exception $e) {
            //This is where we catch gateway validation or gateway unreachable errors
            $result = $paymentProcessor->gateway->getValidationResult();
            $payment = $paymentProcessor->payment;

            //TODO: Need to get errors and save for display on order page
            SS_Log::log(new Exception(print_r($result->message(), true)), SS_Log::NOTICE);
            SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);

            $this->controller->redirect($order->Link());
        }
    }

    public function update(HTTPRequest $request)
    {
        if ($request->isPOST()) {
            $member = Customer::currentUser() ? Customer::currentUser() : singleton(Customer::class);
            $order = Cart::get_current_order();

            //Update the Order
            $order->update($request->postVars());

            $order->updateModifications($request->postVars())
                ->write();

            $form = OrderForm::create(
                $this->controller,
                OrderForm::class
            )->disableSecurityToken();

            // $form->validate();

            return $form->renderWith('OrderFormCart');
        }
    }

    public function populateFields()
    {
        //Populate values in the form the first time
        if (!Session::get("FormInfo.{$this->FormName()}.errors")) {
            $member = Customer::currentUser() ? Customer::currentUser() : singleton(Customer::class);
            $data = array_merge(
                $member->toMap()
            );

            $this->extend('updatePopulateFields', $data);
            $this->loadDataFrom($data);
        }
    }
}
