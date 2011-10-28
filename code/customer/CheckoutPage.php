<?php
class CheckoutPage extends Page
{
  static $db = array(
    'ChequeMessage' => 'HTMLText' //Dependency for ChequePayment::ChequeContent()
  );

  public function getCMSFields() {
    $fields = parent::getCMSFields();
    return $fields;
  }
  
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
	
	function canCreate($member = null) {
	  return false;
	}
	
	function canDelete($member = null) {
	  return false;
	}
	
	function canDeleteFromLive($member = null) {
	  return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 */
	function getCMSActions() {
	  $actions = parent::getCMSActions();
	  $actions->removeByName('action_unpublish');
	  return $actions;
	}
}

class CheckoutPage_Controller extends Page_Controller {
  
  /**
   * Include some CSS for the checkout page
   * TODO why didn't I use init() here?
   */
  function index() {
    
    Requirements::css('stripeycart/css/StripeyCart.css');
    
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('stripeycart/javascript/CheckoutPage.js');

    return array( 
       'Content' => $this->Content, 
       'Form' => $this->Form 
    );
  }
	
	/**
	 * Create an order form
	 * 
	 * @return Form 
	 */
	function OrderForm() {

    $fields = array();
    $validator = new OrderFormValidator();
    $member = Member::currentUser() ? Member::currentUser() : singleton('Member');
    $order = Product_Controller::get_current_order();
    $billingAddress = $member->BillingAddress();
    $shippingAddress = $member->ShippingAddress();
    
    $this->addBillingAddressFields($fields, $validator);
    $this->addShippingAddressFields($fields, $validator);
    $this->addPersonalDetailsFields($fields, $validator, $member);
    $this->addItemFields($fields, $validator, $order);
    $this->addModifierFields($fields, $validator, $order);
    $this->addNotesField($fields, $validator);
    $this->addPaymentFields($fields, $validator, $order);

    $actions = new FieldSet(
      new FormAction('ProcessOrder', 'Proceed to pay')
    );

    $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
    $form->disableSecurityToken();
    
    //Need to disable the js validation because not using custom validation messages
    $validator->setJavascriptValidationHandler('none');
    
    if ($member->ID) $form->loadDataFrom($member);
    if ($billingAddress) $form->loadDataFrom($billingAddress->getCheckoutFormData('Billing')); 
    if ($shippingAddress) $form->loadDataFrom($shippingAddress->getCheckoutFormData('Shipping')); 

    return $form;
	}
	
	private function addBillingAddressFields(&$fields, &$validator) {
	  
	  $firstNameField = new TextField('Billing[FirstName]', 'First Name');
	  $firstNameField->setCustomValidationMessage('Please enter your first name.');
	  
	  $surnameField = new TextField('Billing[Surname]', 'Surname');
	  $surnameField->setCustomValidationMessage('Please enter your surname.');
	  
	  $addressField = new TextField('Billing[Address]', 'Address 1');
	  $addressField->setCustomValidationMessage('Please enter your address.');
	  
	  $cityField = new TextField('Billing[City]', 'City');
	  $cityField->setCustomValidationMessage('Please enter your city.');
	  
	  $countryField = new DropdownField('Billing[Country]', 'Country', Geoip::getCountryDropDown());
	  $countryField->setCustomValidationMessage('Please enter your country.');
    if (!Member::currentUserID() && Geoip::$default_country_code) $countryField->setValue(Geoip::$default_country_code);
	  
	  $billingAddressFields = new CompositeField(
	    new HeaderField('Billing Address', 3),
			$firstNameField,
			$surnameField,
			new TextField('Billing[Company]', 'Company'),
			$addressField,
			new TextField('Billing[AddressLine2]', 'Address 2'),
			$cityField,
			new TextField('Billing[PostalCode]', 'Postal Code'),
			new TextField('Billing[State]', 'State'),
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
	
	private function addShippingAddressFields(&$fields, &$validator) {
	  
	  $firstNameField = new TextField('Shipping[FirstName]', 'First Name');
	  $firstNameField->addExtraClass('shipping-firstname');
	  $firstNameField->setCustomValidationMessage('Please enter a first name.');
	  
	  $surnameField = new TextField('Shipping[Surname]', 'Surname');
	  $surnameField->setCustomValidationMessage('Please enter a surname.');
	  
	  $addressField = new TextField('Shipping[Address]', 'Address 1');
	  $addressField->setCustomValidationMessage('Please enter an address.');
	  
	  $cityField = new TextField('Shipping[City]', 'City');
	  $cityField->setCustomValidationMessage('Please enter a city.');
	  
	  $countryField = new DropdownField('Shipping[Country]', 'Country', Shipping::supported_countries());
	  $countryField->setCustomValidationMessage('Please enter a country.');
    if (!Member::currentUserID() && Geoip::$default_country_code) $countryField->setValue(Geoip::$default_country_code); //Should probably do a default country in Shipping
	  
	  $shippingAddressFields = new CompositeField(
	    new HeaderField('Shipping Address', 3),
	    new CheckboxField('ShipToBillingAddress', 'to same address?'),
			$firstNameField,
			$surnameField,
			new TextField('Shipping[Company]', 'Company'),
			$addressField,
			new TextField('Shipping[AddressLine2]', 'Address 2'),
			$cityField,
			new TextField('Shipping[PostalCode]', 'Postal Code'),
			new TextField('Shipping[State]', 'State'),
			$countryField
	  );
	  
	  $shippingAddressFields->setID('ShippingAddress');
	  $fields['ShippingAddress'][] = $shippingAddressFields;
	  
	  $validator->addRequiredField('Shipping[FirstName]');
	  $validator->addRequiredField('Shipping[Surname]');
	  $validator->addRequiredField('Shipping[Address]');
	  $validator->addRequiredField('Shipping[City]');
	  $validator->addRequiredField('Shipping[Country]');
	}
	
	private function addPersonalDetailsFields(&$fields, &$validator, $member) {
	  
	  $emailField = new EmailField('Email', 'Email');
	  $emailField->setCustomValidationMessage('Please enter your email address.');
	  $validator->addRequiredField('Email');
	  
	  $personalFields = new CompositeField(
	    new HeaderField('Personal Details', 3),
	    new CompositeField(
  			$emailField,
  			new TextField('HomePhone', 'Phone')
	    )
    );
    
	  if(!$member->ID || $member->Password == '') {
	    
	    $personalFields->push(
	      new CompositeField(
	      new FieldGroup(
	        new ConfirmedPasswordField('Password', 'Password')
	      ),
  			new LiteralField(
  				'AccountInfo', 
  				'<p class="password-message">Please choose a password, so you can login and check your order history in the future</p>'
  			),
  			new LiteralField(
  				'MemberInfo', 
  				'<p class="password-message">If you are already a member please <a href="Security/login?BackURL=' . $this->Link() . '">log in</a>.</p>'
  			)
	    ));
			$validator->addRequiredField('Password');
		}

    $personalFields->setID('PersonalDetails');
	  $fields['PersonalDetails'][] = $personalFields;
	}
	
	private function addItemFields(&$fields, &$validator, $order) {
	  $items = $order->Items();
	  
	  if ($items) foreach ($items as $item) {
	    $fields['Items'][] = new OrderItemField($item);
	    //$validator->addItemField('OrderItem' . $item->ID);
	  }
	}
	
	private function addModifierFields(&$fields, &$validator, $order) {

		foreach (Shipping::combined_form_fields($order) as $field) {
		  $fields['Modifiers'][] = $field;
		}
	}
	
	private function addNotesField(&$fields, &$validator) {
	  $fields['Notes'][] = new TextareaField('Notes', 'Notes about this order', 5, 20, '');
	}
	
	private function addPaymentFields(&$fields, &$validator, $order) {
	  $paymentFields = new CompositeField();
	  
		foreach (Payment::combined_form_fields($order->Total->getAmount()) as $field) {
		  $paymentFields->push($field);
		}
		
		$paymentFields->setID('PaymentFields');
	  $fields['Payment'][] = $paymentFields;
	}
	
	/**
	 * Process the order by sending form information to Payment class
	 * 
	 * @see Payment::processPayment()
	 * @param Array $data
	 * @param Form $form
	 */
	function ProcessOrder($data, $form) {

	  //Check payment type
	  $paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
		$payment = class_exists($paymentClass) ? new $paymentClass() : null;

		if(!($payment && $payment instanceof Payment)) {
			user_error(get_class($payment) . ' is not a valid Payment object!', E_USER_ERROR);
			//TODO return meaningful error to browser in case error not shown
			return;
		}

		//Save or create a new member
		
		//TODO use the billing address info for the member
		//Save billing address info to Member for Payment class to work

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
			$member = new Member();
			
			//$form->saveInto($member);
			//$member->update($memberData);
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
		  
		  if (Member::currentUser() && Member::currentUser()->Email == $data['Email']) {
		    $member->update($data);
			  $member->write();
		  }
		  else {
		    $form->sessionMessage(
  				'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.',
  				'bad'
  			);
  			Director::redirectBack();
  			return false;
		  }
		}
		
		//Save the order
		$order = Product_Controller::get_current_order();
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

		// Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		$payment->PaidByID = $member->ID;
		$payment->PaidForID = $order->ID;
		$payment->PaidForClass = $order->class;
		$payment->OrderID = $order->ID;
		$payment->Amount->setAmount($order->Total->getAmount());
		$payment->Amount->setCurrency($order->Total->getCurrency());
		$payment->write();
		
		// Process payment, get the result back
		$result = $payment->processPayment($data, $form);

		// isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
		if($result->isProcessing()) {
			return $result->getValue();
		}

		if($result->isSuccess()) {
		  //TODO Need to update order status here
		}

		Director::redirect($order->Link());
		return true;
	}
	
	function updateOrderFormCart(SS_HTTPRequest $data) {

	  $fields = array();
    $validator = new RequiredFields();
    $member = Member::currentUser() ? Member::currentUser() : singleton('Member');
    $order = Product_Controller::get_current_order();

    //Add addresses to order, then when getting the shipping fields use the shipping address to 
    //filter results
    $order->addAddressesAtCheckout($data->requestVars());
    
    //Add order item fields
    $this->addItemFields($fields, $validator, $order);
    
    //Add modifier fields
    $this->addModifierFields($fields, $validator, $order);
 
    //Modifier fields might have changed, so update the order with new defaults
    //by getting the new modifier field values and passing to addModifiersAtCheckout()
    //Also check to set the fields to the same values as passed by POSTed data
    $modifierData = $data->postVar('Modifiers');
    foreach ($fields['Modifiers'] as $field) {
      
      $name = str_replace(array('[', ']'), array('#', ''), $field->Name());
      $nameParts = explode('#', $name);
      $modifierType = (isset($nameParts[1])) ?$nameParts[1] :null;

      if ($modifierType && isset($modifierData[$modifierType])) {
        
        //Set the field value to what was passed in POST if possible
        $optionVals = array_keys($field->getSource());
        if (in_array($modifierData[$modifierType], $optionVals)) {
          $field->setValue($modifierData[$modifierType]);
        }
        
        $modifierData[$modifierType] = $field->Value();
      }
    }
    $order->addModifiersAtCheckout(array('Modifiers' => $modifierData));
    
    $actions = new FieldSet(
      new FormAction('ProcessOrder', 'Proceed to pay')
    );
    $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
    $form->disableSecurityToken();

	  return $form->renderWith('CheckoutFormOrder');
	}

}
