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
 * @version 1.0
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
	 * @return FieldSet Actions fieldset with unpublish action removed
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
	 * @return FieldSet
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
 * @version 1.0
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
    $order = CartControllerExtension::get_current_order();

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
	
	/**
	 * Add fields for billing address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
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
	
	/**
	 * Add fields for shipping address and required fields to the validator.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 */
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

    $regions = Shipping::supported_regions();
    $regionField = null;
    if (!empty($regions)) {
      $regionField = new RegionField('Shipping[Region]', 'Region');
      $regionField->setCustomValidationMessage('Please enter a region.');
    }
    
    $sameAddressField = new CheckboxField('ShipToBillingAddress', 'to same address?');
    $sameAddressField->addExtraClass('shipping-same-address');
    
	  $shippingAddressFields = new CompositeField(
	    new HeaderField('Shipping Address', 3),
	    $sameAddressField,
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
	    
	    $link = $this->Link();
	    $lit = <<<EOS
<p class="alert alert-info">
	<strong class="alert-heading">NOTE:</strong>
	Please choose a password, so you can login and check your order history in the future. <br /><br />
	If you are already a member please <a href="Security/login?BackURL=$link">log in</a>.
</p>
EOS;
	    
	    $personalFields->push(
	      new CompositeField(
  	      new FieldGroup(
  	        new ConfirmedPasswordField('Password', 'Password')
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
	  $fields['Notes'][] = new TextareaField('Notes', 'Notes about this order', 5, 20, '');
	}
	
	/**
	 * Add pament fields for the current payment method. Also adds payment method as a required field.
	 * 
	 * @param Array $fields Array of fields
	 * @param OrderFormValidator $validator Checkout form validator
	 * @param Order $order The current order
	 */
	private function addPaymentFields(&$fields, &$validator, $order) {
	  $paymentFields = new CompositeField();
	  
		foreach (Payment::combined_form_fields($order->Total->getAmount()) as $field) {

		  //Bit of a nasty hack to customize validation error message
		  if ($field->Name() == 'PaymentMethod') {
		    $field->setCustomValidationMessage('Please select a payment method.');
		  }

		  $paymentFields->push($field);
		}
		
		$paymentFields->setID('PaymentFields');
	  $fields['Payment'][] = $paymentFields;
	  
	  //TODO need to check required payment fields
	  //$requiredPaymentFields = Payment::combined_form_requirements();
	  
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
	  $paymentClass = (!empty($data['PaymentMethod'])) ? $data['PaymentMethod'] : null;
		$payment = class_exists($paymentClass) ? new $paymentClass() : null;

		if(!($payment && $payment instanceof Payment)) {
		  Debug::friendlyError(
		    403,
		    'Sorry, that is not a valid payment method.',
		    'Please go back and try again.'
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
  				'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.',
  				'bad'
  			);
  			Director::redirectBack();
  			return false;
		  }
		}
		
		//Save the order
		$order = CartControllerExtension::get_current_order();
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

		//Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		$payment->PaidByID = $member->ID;
		$payment->PaidForID = $order->ID;
		$payment->PaidForClass = $order->class;
		$payment->OrderID = $order->ID;
		$payment->Amount->setAmount($order->Total->getAmount());
		$payment->Amount->setCurrency($order->Total->getCurrency());
		$payment->write();
		
		//Process payment, get the result back
		$result = $payment->processPayment($data, $form);

    //If instant payment success
		if ($result->isSuccess()) {
      $order->sendReceipt();
      $order->sendNotification();
		}
		
	  //If payment is being processed
	  //e.g long payment process redirected to another website (PayPal, DPS)
		if ($result->isProcessing()) {
		  
		  //Defer sending receipt until payment process has completed
		  //@see AccountPage_Controller::order()
		  
			return $result->getValue();
		}
		
		//If payment failed
		if (!$result->isSuccess() && !$result->isProcessing()) {
      $order->sendReceipt();
      $order->sendNotification();
		}

		//Fallback
		Director::redirect($order->Link());
		return true;
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
      $order = CartControllerExtension::get_current_order();
      
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
  
      $actions = new FieldSet(
        new FormAction('ProcessOrder', 'Proceed to pay')
      );
      $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
      $form->disableSecurityToken();
      $form->validate();
  
  	  return $form->renderWith('CheckoutFormOrder');
	  }
	}

}