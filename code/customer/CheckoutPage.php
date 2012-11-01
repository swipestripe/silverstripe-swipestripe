<?php
/**
 * A checkout page for displaying the checkout form to a visitor.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage extends Page {
  
  /**
   * Adding ChequeMessage field, a requirement for ChequePayment::ChequeContent().
   * 
   * @var Array Database field descriptions
   */
  static $db = array(
    'ChequeMessage' => 'HTMLText'
  );
  
	/**
	 * Automatically create a CheckoutPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('CheckoutPage')) {
			$page = new CheckoutPage();
			$page->Title = 'Checkout';
			$page->Content = '';
			$page->URLSegment = 'checkout';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Checkout page \'Checkout\' created', 'created');
		}
	}
	
	/**
	 * Prevent CMS users from creating another checkout page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
	function canCreate($member = null) {
	  return false;
	}
	
	/**
	 * Prevent CMS users from deleting the checkout page.
	 * 
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
	  return false;
	}

	public function delete() {
    if ($this->canDelete(Member::currentUser())) {
      parent::delete();
    }
  }
	
	/**
	 * Prevent CMS users from unpublishing the checkout page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see CheckoutPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
	function canDeleteFromLive($member = null) {
	  return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see CheckoutPage::canDeleteFromLive()
	 * @return FieldList Actions fieldset with unpublish action removed
	 */
	function getCMSActions() {
	  $actions = parent::getCMSActions();
	  $actions->removeByName('action_unpublish');
	  return $actions;
	}
	
	/**
	 * Remove page type dropdown to prevent users from changing page type.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
  function getCMSFields() {
    $fields = parent::getCMSFields();
    $fields->removeByName('ClassName');
    return $fields;
	}
}

/**
 * Display the checkout page, with order form. Process the order - send the order details
 * off to the Payment class.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage_Controller extends Page_Controller {

	static $allowed_actions = array (
    'index',
    'OrderForm',
    'ProcessOrder',
    'updateOrderFormCart'
  );
  
  /**
   * Include some CSS and javascript for the checkout page
   * 
   * TODO why didn't I use init() here?
   * 
   * @return Array Contents for page rendering
   */
  function index() {
    
    //Update stock levels
    Order::delete_abandoned();

    Requirements::css('swipestripe/css/Shop.css');
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe/javascript/CheckoutPage.js');
		
    return array( 
       'Content' => $this->Content, 
       'Form' => $this->Form 
    );
  }
	
	/**
	 * Create an order form for customers to fill out their details and pass the order
	 * on to the payment class.
	 * 
	 * @return CheckoutForm The checkout/order form 
	 */
	function OrderForm() {
		
    $fields = array();
    $validator = new OrderFormValidator();
    
    $order = Cart::get_current_order();
    $member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

    $this->addBillingAddressFields($fields, $validator);
    $this->addShippingAddressFields($fields, $validator);
    $this->addPersonalDetailsFields($fields, $validator, $member);
    $this->addItemFields($fields, $validator, $order);
    $this->addModifierFields($fields, $validator, $order);
    $this->addNotesField($fields, $validator);
    $this->addPaymentFields($fields, $validator, $order);

    $actions = new FieldList(
      new FormAction('ProcessOrder', _t('CheckoutPage.PROCEED_TO_PAY',"Proceed to pay"))
    );

    $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
    $form->disableSecurityToken();


    //Populate values in the form the first time
    if (!Session::get("FormInfo.{$form->FormName()}.errors")) {

    	$shippingAddress = $member->ShippingAddress();
    	$shippingAddressData = ($shippingAddress && $shippingAddress->exists()) 
    		? $shippingAddress->getCheckoutFormData()
    		: array();

    	$billingAddress = $member->BillingAddress();
    	$billingAddressData = ($billingAddress && $billingAddress->exists()) 
    		? $billingAddress->getCheckoutFormData()
    		: array();

    	//If billing address is a subset of shipping address, consider them equal
    	$intersect = array_intersect(array_values($shippingAddressData), array_values($billingAddressData));
    	if (array_values($intersect) == array_values($billingAddressData)) $billingAddressData['BillToShippingAddress'] = true;

    	$data = array_merge(
	    	$member->toMap(), 
	    	$shippingAddressData,
	    	$billingAddressData
	    );
	    $form->loadDataFrom($data);
    }


    //Hook for editing the checkout page order form
		$this->extend('updateOrderForm', $form);
    return $form;
	}
	
	/**
	 * Add fields for shipping address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
	public function addShippingAddressFields(&$fields, &$validator) {

	  $shippingAddressFields = CompositeField::create(
	    HeaderField::create(_t('CheckoutPage.SHIPPING_ADDRESS',"Shipping Address"), 3),
			TextField::create('Shipping[FirstName]', _t('CheckoutPage.FIRSTNAME',"First Name"))
				->addExtraClass('shipping-firstname')
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_FIRSTNAME',"Please enter a first name.")),
			TextField::create('Shipping[Surname]', _t('CheckoutPage.SURNAME',"Surname"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_SURNAME',"Please enter a surname.")),
			TextField::create('Shipping[Company]', _t('CheckoutPage.COMPANY',"Company")),
			TextField::create('Shipping[Address]', _t('CheckoutPage.ADDRESS',"Address"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_ADDRESS',"Please enter an address."))
				->addExtraClass('address-break'),
			TextField::create('Shipping[AddressLine2]', '&nbsp;'),
			TextField::create('Shipping[City]', _t('CheckoutPage.CITY',"City"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_CITY',"Please enter a city.")),
			TextField::create('Shipping[PostalCode]', _t('CheckoutPage.POSTAL_CODE',"Postal Code")),
			TextField::create('Shipping[State]', _t('CheckoutPage.STATE',"State"))
				->addExtraClass('address-break'),
			DropdownField::create('Shipping[CountryCode]', 
					_t('CheckoutPage.COUNTRY',"Country"), 
					Country_Shipping::get()->map('Code', 'Title')->toArray()
				)->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_COUNTRY',"Please enter a country."))
	  )->setID('ShippingAddress');

	  if (Region::shipping_map()) $shippingAddressFields->push(
  		RegionField::create('Shipping[RegionCode]', _t('CheckoutPage.REGION',"Region"))
  			->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_REGION',"Please select a region."))
  	);

	  $validator->appendRequiredFields(RequiredFields::create(
	  	'Shipping[FirstName]',
	  	'Shipping[Surname]',
	  	'Shipping[Address]',
	  	'Shipping[City]',
	  	'Shipping[CountryCode]'
	  ));

	  $this->extend('updateShippingAddressFields', $shippingAddressFields, $validator);
	  $fields['ShippingAddress'][] = $shippingAddressFields;
	}

	/**
	 * Add fields for billing address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
	public function addBillingAddressFields(&$fields, &$validator) {

	  $billingAddressFields = CompositeField::create(
	    HeaderField::create(_t('CheckoutPage.BILLINGADDRESS',"Billing Address"), 3),
	    $checkbox = CheckboxField::create('BillToShippingAddress', _t('CheckoutPage.SAME_ADDRESS',"same as shipping address?"))
	    	->addExtraClass('shipping-same-address'),
			TextField::create('Billing[FirstName]', _t('CheckoutPage.FIRSTNAME',"First Name"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURFIRSTNAME',"Please enter your first name."))
				->addExtraClass('address-break'),
			TextField::create('Billing[Surname]', _t('CheckoutPage.SURNAME',"Surname"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURSURNAME',"Please enter your surname.")),
			TextField::create('Billing[Company]', _t('CheckoutPage.COMPANY',"Company")),
			TextField::create('Billing[Address]', _t('CheckoutPage.ADDRESS',"Address"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURADDRESS',"Please enter your address."))
				->addExtraClass('address-break'),
			TextField::create('Billing[AddressLine2]', '&nbsp;'),
			TextField::create('Billing[City]', _t('CheckoutPage.CITY',"City"))
				->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCITY',"Please enter your city")),
			TextField::create('Billing[PostalCode]', _t('CheckoutPage.POSTALCODE',"Postal Code")),
			TextField::create('Billing[State]', _t('CheckoutPage.STATE',"State"))
				->addExtraClass('address-break'),
			DropdownField::create('Billing[CountryCode]', 
					_t('CheckoutPage.COUNTRY',"Country"), 
					Country_Billing::get()->map('Code', 'Title')->toArray()
				)->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCOUNTRY',"Please enter your country."))
	  )->setID('BillingAddress');

	  $validator->appendRequiredFields(RequiredFields::create(
	  	'Billing[FirstName]',
	  	'Billing[Surname]',
	  	'Billing[Address]',
	  	'Billing[City]',
	  	'Billing[CountryCode]'
	  ));

	  $this->extend('updateBillingAddressFields', $billingAddressFields, $validator);
	  $fields['BillingAddress'][] = $billingAddressFields;
	}
	
	/**
	 * Add fields for personal details and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Member $member Current logged in member, or Member class singleton if no one logged in
	 */
	public function addPersonalDetailsFields(&$fields, &$validator, $member) {

	  if(!$member->ID || $member->Password == '') {

	    $link = $this->Link();
	    
	    $note = _t('CheckoutPage.NOTE','NOTE:');
	    $passwd = _t('CheckoutPage.PLEASE_CHOOSE_PASSWORD','Please choose a password, so you can login and check your order history in the future.');
	    $member = sprintf(
	      _t('CheckoutPage.ALREADY_MEMBER', 'If you are already a member please %s log in. %s'), 
	      "<a href=\"Security/login?BackURL=$link\">", 
	      '</a>'
	    );

	    $personalFields = new CompositeField(
		    new HeaderField(_t('CheckoutPage.ACCOUNT',"Account"), 3),
		    new CompositeField(
	  			EmailField::create('Email', _t('CheckoutPage.EMAIL', 'Email'))
	  				->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_EMAIL_ADDRESS', "Please enter your email address.")),
	  			TextField::create('HomePhone', _t('CheckoutPage.PHONE',"Phone"))
		    ),
		    new CompositeField(
  	      new FieldGroup(
  	        new ConfirmedPasswordField('Password', _t('CheckoutPage.PASSWORD', "Password"))
  	      )
	    	),
	    	new CompositeField(
    			new LiteralField(
    				'AccountInfo', 
    				"
				    <p class=\"alert alert-info\">
				    	<strong class=\"alert-heading\">$note</strong>
							$passwd <br /><br />
							$member
						</p>
				    "
    			)
	    	)
	    );

			$validator->addRequiredField('Password');
			$validator->addRequiredField('Email');

			$personalFields->setID('PersonalDetails');

	    $this->extend('updatePersonalFields', $personalFields, $validator);
		  $fields['PersonalDetails'][] = $personalFields;
		}
	}
	
	/**
	 * Add item fields for each item in the current order.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	public function addItemFields(&$fields, &$validator, $order) {
	  $items = $order->Items();

	  if ($items) foreach ($items as $item) {
	    $fields['Items'][] = new OrderItemField($item);
	  }
	}
	
	/**
	 * Add modifier fields for this order, such as Shipping fields.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	public function addModifierFields(&$fields, &$validator, $order) {

		$subTotalMods = $order->SubTotalModifications();
		$totalMods = $order->TotalModifications();

		foreach ($subTotalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$fields['SubTotalModifiers'][] = $field;
			}
		}

		foreach ($totalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$fields['Modifiers'][] = $field;
			}
		}
	}
	
	/**
	 * Add notes field for the order.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
	public function addNotesField(&$fields, &$validator) {

		$notesFields = new CompositeField(
	    TextareaField::create('Notes', _t('CheckoutPage.NOTES_ABOUT_ORDER',"Notes about this order"))
    );

		$this->extend('updateNotesFields', $notesFields, $validator);
	  $fields['Notes'][] = $notesFields;
	}
	
	/**
	 * Add pament fields for the current payment method. Also adds payment method as a required field.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	public function addPaymentFields(&$fields, &$validator, $order) {

    // Create a dropdown select field for choosing gateway
    $supported_methods = PaymentProcessor::get_supported_methods();

    $source = array();
    foreach ($supported_methods as $methodName) {
      $methodConfig = PaymentFactory::get_factory_config($methodName);
      $source[$methodName] = $methodConfig['title'];
    }

    $paymentFields = new CompositeField(
    	new HeaderField(_t('CheckoutPage.PAYMENT',"Payment"), 3),
	    DropDownField::create(
	      'PaymentMethod',
	      'Select Payment Method',
	      $source
	    )->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD',"Please select a payment method."))
    );
    $validator->addRequiredField('PaymentMethod');

    $this->extend('updatePaymentFields', $paymentFields, $validator);
    $fields['Payment'][] = $paymentFields;
	}
	
	/**
	 * Process the order by sending form information to Payment class.
	 * 
	 * TODO send emails from this function after payment is processed
	 * 
	 * @see Payment::processPayment()
	 * @param Array $data Submitted form data via POST
	 * @param Form $form Form data was submitted from
	 */
	function ProcessOrder($data, $form) {

	  //Check payment type
		try {
			$paymentMethod = $data['PaymentMethod'];
      $paymentProcessor = PaymentFactory::factory($paymentMethod);
    }
    catch (Exception $e) {
      Debug::friendlyError(
		    403,
		    _t('CheckoutPage.NOT_VALID_METHOD',"Sorry, that is not a valid payment method."),
		    _t('CheckoutPage.TRY_AGAIN',"Please go back and try again.")
		  );
			return;
    }

		//Save or create a new customer/member
		//Need to save billing address info to Member for Payment class to work

		$memberData = array(
		  'FirstName' => $data['Billing']['FirstName'],
		  'Surname' => $data['Billing']['Surname'],
			'Address' => $data['Billing']['Address'],
		  'AddressLine2' => $data['Billing']['AddressLine2'],
			'City' => $data['Billing']['City'],
		  'State' => $data['Billing']['State'],
			'Country' => $data['Billing']['CountryCode'],
		  'PostalCode' => $data['Billing']['PostalCode']
		);

		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
		if (!$member->exists()) {

			$existingCustomer = Customer::get()->where("\"Email\" = '".$data['Email']."'");
			if ($existingCustomer && $existingCustomer->exists()) {
				$form->sessionMessage(
  				_t('CheckoutPage.MEMBER_ALREADY_EXISTS', 'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.'),
  				'bad'
  			);
  			$this->redirectBack();
  			return false;
			}

			$member = new Customer();
			
			$form->saveInto($member);
			$member->update($data['Billing']);
			$member->Email = $data['Email'];
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
		$order->OrderedOn = SS_Datetime::now()->getValue();
		$order->write();

		//TODO Save status updates for the order instead of saving a Note on the Order directly

		//Save the order items (not sure why can't do this with writeComponents() perhaps because Items() are cached?!)
	  foreach ($items as $item) {
      $item->OrderID = $order->ID;
		  $item->write();
    }
    
    //Add addresses to order
    $order->updateAddresses($data)->write();

    //Add modifiers to order
    $order->updateModifications($data)->write();

		Session::clear('Cart.OrderID');

    try {

      $paymentData = array(
				'Amount' => $order->Total()->getAmount(),
				'Currency' => $order->Total()->getCurrency()
			);
			$paymentProcessor->payment->OrderID = $order->ID;
			$paymentProcessor->payment->PaidByID = $member->ID;

			$paymentProcessor->setRedirectURL($order->Link());
	    $paymentProcessor->capture($paymentData);
    }
    catch (Exception $e) {

      //This is where we catch gateway validation or gateway unreachable errors
      $result = $paymentProcessor->gateway->getValidationResult();
      $payment = $paymentProcessor->payment;

      //TODO: Need to get errors and save for display on order page
      SS_Log::log(new Exception(print_r($result->message(), true)), SS_Log::NOTICE);
      SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);

      $this->redirect($order->Link());
    }
	}
	
	/**
	 * Update the order form cart, called via AJAX with current order form data.
	 * Renders the cart and sends that back for displaying on the order form page.
	 * 
	 * @param SS_HTTPRequest $data Form data sent via AJAX POST.
	 * @return String Rendered cart for the order form, template include 'CheckoutFormOrder'.
	 */
	function updateOrderFormCart(SS_HTTPRequest $request) {

	  if ($request->isPOST()) {

  	  $fields = array();
      $validator = new OrderFormValidator();
      $member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
      $order = Cart::get_current_order();

      //Update the Order 
      $order->update($request->postVars());
      $order->updateAddresses($request->postVars());
      $order->write();

      $order->updateModifications($request->postVars())
      	->write();

      //Create the part of the form that displays the Order
      $this->addItemFields($fields, $validator, $order);
      $this->addModifierFields($fields, $validator, $order); 

      $actions = new FieldList(
        new FormAction('ProcessOrder', _t('CheckoutPage.PROCEED_TO_PAY',"Proceed to pay"))
      );
      $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
      $form->disableSecurityToken();
      $form->validate();

  	  return $form->renderWith('CheckoutFormOrder');
	  }
	}

}