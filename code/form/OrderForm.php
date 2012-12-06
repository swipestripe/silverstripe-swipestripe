<?php
/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details 
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 */
class OrderForm extends Form {

	protected $order;
	protected $customer;
  
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
  function __construct($controller, $name) {

  	Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe/javascript/OrderForm.js');

  	$this->order = Cart::get_current_order();
    $this->customer = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
    $this->controller = $controller;

    $fields = $this->createFields();
    $actions = $this->createActions();
    $validator = $this->createValidator();

		parent::__construct($controller, $name, $fields, $actions, $validator);

		$this->setTemplate('OrderForm');
		$this->addExtraClass('order-form');
  }

  public function createFields() {

  	$order = $this->order;
    $member = $this->customer;

    //Personal details fields
    if(!$member->ID || $member->Password == '') {

	    $link = $this->controller->Link();
	    
	    $note = _t('CheckoutPage.NOTE','NOTE:');
	    $passwd = _t('CheckoutPage.PLEASE_CHOOSE_PASSWORD','Please choose a password, so you can login and check your order history in the future.');
	    $mber = sprintf(
	      _t('CheckoutPage.ALREADY_MEMBER', 'If you are already a member please %s log in. %s'), 
	      "<a href=\"Security/login?BackURL=$link\">", 
	      '</a>'
	    );

	    $personalFields = CompositeField::create(
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
	  if ($items) foreach ($items as $item) {
	  	$itemFields->push(new OrderForm_ItemField($item));
	  }

	  //Order modifications fields
	  $subTotalModsFields = CompositeField::create()->setName('SubTotalModificationsFields');
	  $subTotalMods = $order->SubTotalModifications();

		foreach ($subTotalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$subTotalModsFields->push($field);
			}
		}

		$totalModsFields = CompositeField::create()->setName('TotalModificationsFields');
		$totalMods = $order->TotalModifications();

		foreach ($totalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$totalModsFields->push($field);
			}
		}

		//Payment fields
    $supported_methods = PaymentProcessor::get_supported_methods();

    $source = array();
    foreach ($supported_methods as $methodName) {
      $methodConfig = PaymentFactory::get_factory_config($methodName);
      $source[$methodName] = $methodConfig['title'];
    }

    $paymentFields = CompositeField::create(
    	new HeaderField(_t('CheckoutPage.PAYMENT',"Payment"), 3),
	    DropDownField::create(
	      'PaymentMethod',
	      'Select Payment Method',
	      $source
	    )->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD',"Please select a payment method."))
    )->setName('PaymentFields');


    $fields = FieldList::create(

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
		  )->setID('ShippingAddress')->setName('ShippingAddress'),

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
		  )->setID('BillingAddress')->setName('BillingAddress'),

			$itemFields,

			$subTotalModsFields,

			$totalModsFields,

			$notesFields = CompositeField::create(
		    TextareaField::create('Notes', _t('CheckoutPage.NOTES_ABOUT_ORDER',"Notes about this order"))
	    )->setName('NotesFields'),

	    $paymentFields
    );

		if (isset($personalFields)) {
			$fields->push($personalFields);
		}

		return $fields;
  }

  public function createActions() {
  	$actions = FieldList::create(
  		new FormAction('process', _t('CheckoutPage.PROCEED_TO_PAY',"Proceed to pay"))
  	);
  	return $actions;
  }

  public function createValidator() {

  	$validator = new OrderForm_Validator(
			'Shipping[FirstName]',
	  	'Shipping[Surname]',
	  	'Shipping[Address]',
	  	'Shipping[City]',
	  	'Shipping[CountryCode]',
	  	'Billing[FirstName]',
	  	'Billing[Surname]',
	  	'Billing[Address]',
	  	'Billing[City]',
	  	'Billing[CountryCode]',
	  	'PaymentMethod'
		);

		if (!$this->customer->ID || $this->customer->Password == '') {
			$validator->addRequiredField('Password');
			$validator->addRequiredField('Email');
		}

		return $validator;
  }

  public function getShippingAddressFields() {
  	return $this->Fields()->fieldByName('ShippingAddress');
  }

  public function getBillingAddressFields() {
  	return $this->Fields()->fieldByName('BillingAddress');
  }

  public function getPersonalDetailsFields() {
  	return $this->Fields()->fieldByName('PersonalDetails');
  }

  public function getItemsFields() {
  	return $this->Fields()->fieldByName('ItemsFields')->FieldList();
  }

  public function getSubTotalModificationsFields() {
  	return $this->Fields()->fieldByName('SubTotalModificationsFields')->FieldList();
  }

  public function getTotalModificationsFields() {
  	return $this->Fields()->fieldByName('TotalModificationsFields')->FieldList();
  }

  public function getNotesFields() {
  	return $this->Fields()->fieldByName('NotesFields');
  }

  public function getPaymentFields() {
  	return $this->Fields()->fieldByName('PaymentFields');
  }
  
  /**
   * Helper function to return the current {@link Order}, used in the template for this form
   * 
   * @return Order
   */
  function Cart() {
    return $this->order;
  }
	
	/**
	 * Overloaded so that form error messages are displayed.
	 * 
	 * @see OrderFormValidator::php()
	 * @see Form::validate()
	 */
  function validate(){

		if($this->validator){
			$errors = $this->validator->validate();

			if ($errors){

				if (Director::is_ajax()) { // && $this->validator->getJavascriptValidationHandler() == 'prototype') {
				  
				  //Set error messages to form fields for display after form is rendered
				  $fields = $this->Fields();

				  foreach ($errors as $errorData) {
				    $field = $fields->dataFieldByName($errorData['fieldName']);
            if ($field) {
              $field->setError($errorData['message'], $errorData['messageType']);
              $fields->replaceField($errorData['fieldName'], $field);
            }
				  }
				} 
				else {
				
					$data = $this->getData();

					$formError = array();
					if ($formMessageType = $this->MessageType()) {
					  $formError['message'] = $this->Message();
					  $formError['messageType'] = $formMessageType;
					}

					// Load errors into session and post back
					Session::set("FormInfo.{$this->FormName()}", array(
						'errors' => $errors,
						'data' => $data,
					  'formError' => $formError
					));

				}
				return false;
			}
		}
		return true;
	}

  public function process($data, $form) {

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

    //TODO: Refactor customer addresses
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

		//Saving an update on the order
		if ($notes = $data['Notes']) {
			$update = new Order_Update();
			$update->Note = $notes;
			$update->Visible = true;
			$update->OrderID = $order->ID;
			$update->MemberID = $member->ID;
			$update->write();
		}

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

		$order->onBeforePayment();

    try {

      $paymentData = array(
				'Amount' => $order->Total()->getAmount(),
				'Currency' => $order->Total()->getCurrency(),
				'Reference' => $order->ID
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

  function update(SS_HTTPRequest $request) {

	  if ($request->isPOST()) {

      $member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
      $order = Cart::get_current_order();

      //Update the Order 
      $order->update($request->postVars());

      $order->updateAddresses($request->postVars())
      	->write();

      $order->updateModifications($request->postVars())
      	->write();

      $form = OrderForm::create(
      	$this->controller, 
      	'OrderForm'
      )->disableSecurityToken();
      $form->validate();

  	  return $form->renderWith('OrderFormCart');
	  }
	}
}

/**
 * Validate the {@link OrderForm}, check that the current {@link Order} is valid.
 */
class OrderForm_Validator extends RequiredFields {

	/**
	 * Check that current order is valid
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	function php($data) {
	  
	  //TODO move the form error messages to CheckoutForm::validate()

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check the order is valid
		$currentOrder = Cart::get_current_order();
		if (!$currentOrder) {
		  $this->form->sessionMessage(
  		  _t('Form.ORDER_IS_NOT_VALID', 'Your cart seems to be empty, please add an item from the shop'),
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
		}
		else {
		  $validation = $currentOrder->validateForCart();
		  
		  if (!$validation->valid()) {
		    
		    $this->form->sessionMessage(
    		  _t('Form.ORDER_IS_NOT_VALID', 'There seems to be a problem with your order. ' . $validation->message()),
    		  'bad'
    		);
    		
    		//Have to set an error for Form::validate()
    		$this->errors[] = true;
    		$valid = false;
		  }
		}
		
		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form
	 */
	public function getForm() {
	  return $this->form;
	}
}

/**
 * Represent each {@link Item} in the {@link Order} on the {@link OrderForm}.
 */
class OrderForm_ItemField extends FormField {

	/**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "OrderForm_ItemField";
	
	/**
	 * Current {@link Item} this field represents.
	 * 
	 * @var Item
	 */
	protected $item;
	
	/**
	 * Construct the form field and set the {@link Item} it represents.
	 * 
	 * @param Item $item
	 * @param Form $form
	 */
  function __construct($item, $form = null){

		$this->item = $item;
		$name = 'OrderItem' . $item->ID;
		parent::__construct($name, null, '', null, $form);
	}
	
	/**
	 * Render the form field with the correct template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder($properties = array()) {
		return $this->renderWith($this->template);
	}
	
	/**
	 * Retrieve the {@link Item} this field represents.
	 * 
	 * @return Item
	 */
	function Item() {
	  return $this->item;
	}
	
	/**
	 * Set the {@link Item} this field represents.
	 * 
	 * @param Item $item
	 */
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
	/**
	 * Validate this form field, make sure the {@link Item} exists, is in the current 
	 * {@link Order} and the item is valid for adding to the cart.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
	function validate($validator) {

	  $valid = true;
	  $item = $this->Item();
	  $currentOrder = Cart::get_current_order();
		$items = $currentOrder->Items();
		
	  //Check that item exists and is in the current order
	  if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
	    
	    $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Order.');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
	    
	    $validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
			$valid = false;
	  }
	  else if ($item) {
	    
	    $validation = $item->validateForCart();
	    
	    if (!$validation->valid()) {
	      
	      $errorMessage = $validation->message();
  			if ($msg = $this->getCustomValidationMessage()) {
  				$errorMessage = $msg;
  			}
  			
  			$validator->validationError(
  				$this->getName(),
  				$errorMessage,
  				"error"
  			);
  	    $valid = false;
	    }
	  }
	  
	  return $valid;
	}
}

