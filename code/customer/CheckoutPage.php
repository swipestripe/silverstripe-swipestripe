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
    
    $member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
    $order = Cart::get_current_order();

    $billingAddress = $member->BillingAddress();
    $shippingAddress = $member->ShippingAddress();

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
    
    //Need to disable the js validation because not using custom validation messages
    //$validator->setJavascriptValidationHandler('none');

    if ($member->ID) $form->loadDataFrom($member);
    if ($billingAddress) $form->loadDataFrom($billingAddress->getCheckoutFormData('Billing')); 
    if ($shippingAddress) $form->loadDataFrom($shippingAddress->getCheckoutFormData('Shipping')); 

    //Hook for editing the checkout page order form
		$this->extend('updateOrderForm', $form);

    return $form;
	}
	
	/**
	 * Add fields for billing address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
	private function addBillingAddressFields(&$fields, &$validator) {
	  
	  $firstNameField = new TextField('Billing[FirstName]', _t('CheckoutPage.FIRSTNAME',"First Name"));
	  $firstNameField->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURFIRSTNAME',"Please enter your first name."));
	  
	  $surnameField = new TextField('Billing[Surname]', _t('CheckoutPage.SURNAME',"Surname"));
	  $surnameField->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURSURNAME',"Please enter your surname."));
	  
	  $addressField = new TextField('Billing[Address]', _t('CheckoutPage.ADDRESS1',"Address 1"));
	  $addressField->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURADDRESS',"Please enter your address."));
	  
	  $cityField = new TextField('Billing[City]', _t('CheckoutPage.CITY',"City"));
	  $cityField->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCITY',"Please enter your city"));
	  
	  $countryField = new DropdownField('Billing[Country]', _t('CheckoutPage.COUNTRY',"Country"), Country::billing_countries());
	  $countryField->setCustomValidationMessage(_t('CheckoutPage.PLEASEENTERYOURCOUNTRY',"Please enter your country."));
	  
	  $billingAddressFields = new CompositeField(
	    new HeaderField(_t('CheckoutPage.BILLINGADDRESS',"Billing Address"), 3),
			$firstNameField,
			$surnameField,
			new TextField('Billing[Company]', _t('CheckoutPage.COMPANY',"Company")),
			$addressField,
			new TextField('Billing[AddressLine2]', _t('CheckoutPage.ADDRESS2',"Address 2")),
			$cityField,
			new TextField('Billing[PostalCode]', _t('CheckoutPage.POSTALCODE',"Postal Code")),
			new TextField('Billing[State]', _t('CheckoutPage.STATE',"State")),
			$countryField
	  );

	  $billingAddressFields->setID('BillingAddress');
	  $fields['BillingAddress'][] = $billingAddressFields;
	  
	  $validator->addRequiredField('Billing[FirstName]');
	  $validator->addRequiredField('Billing[Surname]');
	  $validator->addRequiredField('Billing[Address]');
	  $validator->addRequiredField('Billing[City]');
	  $validator->addRequiredField('Billing[Country]');
	}
	
	/**
	 * Add fields for shipping address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
	private function addShippingAddressFields(&$fields, &$validator) {
	  
	  $firstNameField = new TextField('Shipping[FirstName]', _t('CheckoutPage.FIRSTNAME',"First Name"));
	  $firstNameField->addExtraClass('shipping-firstname');
	  $firstNameField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_FIRSTNAME',"Please enter a first name."));
	  
	  $surnameField = new TextField('Shipping[Surname]', _t('CheckoutPage.SURNAME',"Surname"));
	  $surnameField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_SURNAME',"Please enter a surname."));
	  
	  $addressField = new TextField('Shipping[Address]', _t('CheckoutPage.ADDRESS1',"Address 1"));
	  $addressField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_ADDRESS',"Please enter an address."));
	  
	  $cityField = new TextField('Shipping[City]', _t('CheckoutPage.CITY',"City"));
	  $cityField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_CITY',"Please enter a city."));
	  
	  $countryField = new DropdownField('Shipping[Country]', _t('CheckoutPage.COUNTRY',"Country"), Country::shipping_countries());
	  $countryField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_COUNTRY',"Please enter a country."));

    $regions = Region::shipping_regions();

    $regionField = null;
    if (!empty($regions)) {
      $regionField = new RegionField('Shipping[Region]', _t('CheckoutPage.REGION',"Region"));
      $regionField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_REGION',"Please enter a country."));
    }

	  $sameAddressField = new CheckboxField('ShipToBillingAddress', _t('CheckoutPage.SAME_ADDRESS',"to same address?"));
    $sameAddressField->addExtraClass('shipping-same-address');
    
	  $shippingAddressFields = new CompositeField(
	    new HeaderField(_t('CheckoutPage.SHIPPING_ADDRESS',"Shipping Address"), 3),
	    $sameAddressField,
			$firstNameField,
			$surnameField,
			new TextField('Shipping[Company]', _t('CheckoutPage.COMPANY',"Company")),
			$addressField,
			new TextField('Shipping[AddressLine2]', _t('CheckoutPage.ADDRESS2',"Address 2")),
			$cityField,
			new TextField('Shipping[PostalCode]', _t('CheckoutPage.POSTAL_CODE',"Postal Code")),
			new TextField('Shipping[State]', _t('CheckoutPage.STATE',"State")),
			$countryField
	  );
	  
	  if ($regionField) $shippingAddressFields->push($regionField);
	  
	  $shippingAddressFields->setID('ShippingAddress');
	  $fields['ShippingAddress'][] = $shippingAddressFields;
	  
	  $validator->addRequiredField('Shipping[FirstName]');
	  $validator->addRequiredField('Shipping[Surname]');
	  $validator->addRequiredField('Shipping[Address]');
	  $validator->addRequiredField('Shipping[City]');
	  $validator->addRequiredField('Shipping[Country]');
	}
	
	/**
	 * Add fields for personal details and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Member $member Current logged in member, or Member class singleton if no one logged in
	 */
	private function addPersonalDetailsFields(&$fields, &$validator, $member) {
	  
	  //TODO: Change back to EmailField and debug FunctionalTest->submitForm()
	  $emailField = new EmailField('Email', _t('CheckoutPage.EMAIL', 'Email'));
	  $emailField->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_EMAIL_ADDRESS', "Please enter your email address."));
	  $validator->addRequiredField('Email');
	  
	  $personalFields = new CompositeField(
	    new HeaderField(_t('CheckoutPage.PERSONAL_DETAILS',"Personal Details"), 3),
	    new CompositeField(
  			$emailField,
  			new TextField('HomePhone', _t('CheckoutPage.PHONE',"Phone"))
	    )
    );
    
	  if(!$member->ID || $member->Password == '') {
	    
	    $link = $this->Link();
	    
	    $note = _t('CheckoutPage.NOTE','NOTE:');
	    $passwd = _t('CheckoutPage.PLEASE_CHOOSE_PASSWORD','Please choose a password, so you can login and check your order history in the future.');
	    $member = sprintf(
	      _t('CheckoutPage.ALREADY_MEMBER', 'If you are already a member please %s log in. %s'), 
	      "<a href=\"Security/login?BackURL=$link\">", 
	      '</a>'
	    );

	    $lit = "
	    <p class=\"alert alert-info\">
	    	<strong class=\"alert-heading\">$note</strong>
				$passwd <br /><br />
				$member
			</p>
	    ";

	    $personalFields->push(
	      new CompositeField(
  	      new FieldGroup(
  	        new ConfirmedPasswordField('Password', _t('CheckoutPage.PASSWORD',"Password"))
  	      ),
    			new LiteralField(
    				'AccountInfo', 
    				$lit
    			)
	    ));
			$validator->addRequiredField('Password');
		}

    $personalFields->setID('PersonalDetails');
	  $fields['PersonalDetails'][] = $personalFields;
	}
	
	/**
	 * Add item fields for each item in the current order.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	private function addItemFields(&$fields, &$validator, $order) {
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
	private function addModifierFields(&$fields, &$validator, $order) {

		foreach (Modifier::combined_form_fields($order) as $field) {
		  
		  if ($field->modifiesSubTotal()) {
		    $fields['SubTotalModifiers'][] = $field;
		  }
		  else {
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
	private function addNotesField(&$fields, &$validator) {
	  $fields['Notes'][] = new TextareaField('Notes', _t('CheckoutPage.NOTES_ABOUT_ORDER',"Notes about this order"));
	}
	
	/**
	 * Add pament fields for the current payment method. Also adds payment method as a required field.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	private function addPaymentFields(&$fields, &$validator, $order) {

    // Create a dropdown select field for choosing gateway
    $supported_methods = PaymentProcessor::get_supported_methods();

    $source = array();
    foreach ($supported_methods as $methodName) {
      $methodConfig = PaymentFactory::get_factory_config($methodName);
      $source[$methodName] = $methodConfig['title'];
    }

    $gatewayField = new DropDownField(
      'PaymentMethod',
      'Select Payment Method',
      $source
    );
    $gatewayField->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD',"Please select a payment method."));
    $fields['Payment'][] = $gatewayField;

    $validator->addRequiredField('PaymentMethod');
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
			'Country' => $data['Billing']['Country'],
		  'PostalCode' => $data['Billing']['PostalCode']
		);

	  if (!$member = DataObject::get_one('Member', "\"Email\" = '".$data['Email']."'")) {
			$member = new Customer();
			
			$form->saveInto($member);
			$member->FirstName = $data['Billing']['FirstName'];
			$member->Surname = $data['Billing']['Surname'];
			$member->Address = $data['Billing']['Address'];
			$member->AddressLine2 = $data['Billing']['AddressLine2'];
			$member->City = $data['Billing']['City'];
			$member->State = $data['Billing']['State'];
			$member->Country = $data['Billing']['Country'];
			$member->PostalCode = $data['Billing']['PostalCode'];
			$member->Email = $data['Email'];

			$member->write();
			$member->addToGroupByCode('customers');
			$member->logIn();
		}
		else {
		  
		  if (Customer::currentUser() && Customer::currentUser()->Email == $data['Email']) {
		    $member->update($data);
			  $member->write();
		  }
		  else {
		    $form->sessionMessage(
  				_t('CheckoutPage.MEMBER_ALREADY_EXISTS', 'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.'),
  				'bad'
  			);
  			$this->redirectBack();
  			return false;
		  }
		}
		
		//Save the order
		$order = Cart::get_current_order();
		$items = $order->Items();

		$form->saveInto($order);
		$order->MemberID = $member->ID;
		$order->Status = Order::STATUS_PENDING;
		$order->OrderedOn = SS_Datetime::now()->getValue();
		$order->write();

		//Save the order items (not sure why can't do this with writeComponents() perhaps because Items() are cached?!)
	  foreach ($items as $item) {
      $item->OrderID = $order->ID;
		  $item->write();
    }
    
    //Add addresses to order
    $order->addAddressesAtCheckout($data);

    //Add modifiers to order
    $order->addModifiersAtCheckout($data);

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
	function updateOrderFormCart(SS_HTTPRequest $data) {

	  if ($data->isPOST()) {

  	  $fields = array();
      $validator = new OrderFormValidator();
      $member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
      $order = Cart::get_current_order();
      
      //Update the Order 
      $order->addAddressesAtCheckout($data->postVars());
      $order->addModifiersAtCheckout($data->postVars());
      //TODO update personal details, notes and payment type?
  
      //Create the part of the form that displays the Order
      $this->addItemFields($fields, $validator, $order);
      $this->addModifierFields($fields, $validator, $order); //This is going to go through and add modifiers based on current Form DATA
      
      //TODO This should be constructed for non-dropdown fields as well
      //Update modifier form fields so that the dropdown values are correct
      $newModifierData = array();
      $subTotalModifiers = (isset($fields['SubTotalModifiers'])) ? $fields['SubTotalModifiers'] : array();
      $totalModifiers = (isset($fields['Modifiers'])) ? $fields['Modifiers'] : array(); 
      $modifierFields = array_merge($subTotalModifiers, $totalModifiers);

      foreach ($modifierFields as $field) {
  
        if (method_exists($field, 'updateValue')) {
          $field->updateValue($order);
        }
  
        $modifierClassName = get_class($field->getModifier());
        $newModifierData['Modifiers'][$modifierClassName] = $field->Value();
      }
  
      //Add modifiers to the order again so that the new values are used
      $order->addModifiersAtCheckout($newModifierData);
  
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