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

		if(!DataObject::get_one('CheckoutPage')) {
			$page = new CheckoutPage();
			$page->Title = 'Checkout';
			$page->Content = '<p>This is the checkout page, it is used for customers to complete their order.</p>';
			$page->URLSegment = 'checkout';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Checkout page \'Checkout\' created', 'created');
		}
	}
}

class CheckoutPage_Controller extends Page_Controller {
  
  /**
   * Include some CSS for the checkout page
   */
  function index() {
    
    Requirements::css('stripeycart/css/OrderReport.css');
    Requirements::css('stripeycart/css/Checkout.css');

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
    $validator = new RequiredFields();
    $member = Member::currentUser() ? Member::currentUser() : singleton('Member');
    $order = Product_Controller::get_current_order();
    $billingAddress = $member->BillingAddress();
    $shippingAddress = $member->ShippingAddress();
    
    $this->addBillingAddressFields($fields, $validator);
    $this->addShippingAddressFields($fields, $validator);
    $this->addPersonalDetailsFields($fields, $validator, $member);
    $this->addModifierFields($fields, $validator, $order);
    $this->addPaymentFields($fields, $validator, $order);

    $actions = new FieldSet(
      new FormAction('ProcessOrder', 'Proceed to pay')
    );

    $form = new CheckoutForm($this, 'OrderForm', $fields, $actions, $validator, $order);
    $form->disableSecurityToken();
    
    if ($member->ID) $form->loadDataFrom($member);
    if ($billingAddress) $form->loadDataFrom($billingAddress->getCheckoutFormData('Billing')); 
    if ($shippingAddress) $form->loadDataFrom($shippingAddress->getCheckoutFormData('Shipping')); 
    
    return $form;
	}
	
	private function addBillingAddressFields(&$fields, &$validator) {
	  
	  $billingAddressFields = new CompositeField(
	    new HeaderField('Billing Address', 3),
			new TextField('Billing[FirstName]', 'First Name'),
			new TextField('Billing[Surname]', 'Surname'),
			new TextField('Billing[Company]', 'Company'),
			new TextField('Billing[Address]', 'Address 1'),
			new TextField('Billing[AddressLine2]', 'Address 2'),
			new TextField('Billing[City]', 'City'),
			new TextField('Billing[PostalCode]', 'Postal Code'),
			new TextField('Billing[State]', 'State')
	  );

    $countryField = new DropdownField('Billing[Country]', 'Country', Geoip::getCountryDropDown());
    if (!Member::currentUserID() && Geoip::$default_country_code) $countryField->setValue(Geoip::$default_country_code);
    $billingAddressFields->push($countryField);
	  
	  $billingAddressFields->setID('billing-address');
	  $fields['BillingAddress'][] = $billingAddressFields;
	}
	
	private function addShippingAddressFields(&$fields, &$validator) {
	  
	  $shippingAddressFields = new CompositeField(
	    new HeaderField('Shipping Address', 3),
	    new CheckboxField('ShipToBillingAddress', 'to same address?'),
			new TextField('Shipping[FirstName]', 'First Name'),
			new TextField('Shipping[Surname]', 'Surname'),
			new TextField('Shipping[Company]', 'Company'),
			new TextField('Shipping[Address]', 'Address 1'),
			new TextField('Shipping[AddressLine2]', 'Address 2'),
			new TextField('Shipping[City]', 'City'),
			new TextField('Shipping[PostalCode]', 'Postal Code'),
			new TextField('Shipping[State]', 'State')
	  );

    $countryField = new DropdownField('Shipping[Country]', 'Country', Shipping::supported_countries());
    if (!Member::currentUserID() && Geoip::$default_country_code) $countryField->setValue(Geoip::$default_country_code); //Should probably do a default country in Shipping
    $shippingAddressFields->push($countryField);
	  
	  $shippingAddressFields->setID('shipping-address');
	  $fields['ShippingAddress'][] = $shippingAddressFields;
	}
	
	private function addPersonalDetailsFields(&$fields, &$validator, $member) {
	  $personalFields = new CompositeField(
			new HeaderField('Personal Details', 3),
			new TextField('FirstName', 'First Name'),
			new EmailField('Email', 'Email'),
			new TextField('HomePhone', 'Phone')
    );
    
	  if(!$member->ID || $member->Password == '') {
			$personalFields->push(new LiteralField(
				'MemberInfo', 
				'<p class="message good">If you are already a member please <a href="Security/login?BackURL=' . $this->Link() . '">log in</a>.</p>'
			));
			$personalFields->push(new LiteralField(
				'AccountInfo', 
				'<p>Please choose a password, so you can login and check your order history in the future</p>'
			));
			$personalFields->push(new FieldGroup(new ConfirmedPasswordField('Password', 'Password')));
			
			$validator->addRequiredField('Password');
		}
		
		$validator->addRequiredField('FirstName');
    $validator->addRequiredField('Email');
    $validator->addRequiredField('HomePhone');
    
    $personalFields->setID('personal-details');
	  $fields['PersonalDetails'][] = $personalFields;
	}
	
	private function addCartFields(&$fields, &$validator, $order) {
	  
	}
	
	private function addModifierFields(&$fields, &$validator, $order) {

		foreach (Shipping::combined_form_fields($order) as $field) {
		  $fields['Modifiers'][] = $field;
		}
	}
	
	private function addPaymentFields(&$fields, &$validator, $order) {
	  $paymentFields = new CompositeField();
	  
		foreach (Payment::combined_form_fields($order->Total->getAmount()) as $field) {
		  $paymentFields->push($field);
		}
		
		$paymentFields->setID('payment');
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
		}

		//Save or create a new member
	  if (!$member = DataObject::get_one('Member', "\"Email\" = '".$data['Email']."'")) {
			$member = new Member();
			$form->saveInto($member);
			$member->addToGroupByCode('customers');
			$member->write();
			$member->logIn();
		}
		else if (Member::currentUser()) {
		  
		  if (Member::currentUser()->Email == $data['Email']) {
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
		
		//Get the order and items in the order
		$order = Product_Controller::get_current_order();
		$items = $order->Items();

		//Save the order
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
    //$order->addAddressesAtCheckout($data);
    
    $billingAddress = new Address();
	  $billingAddress->OrderID = $order->ID;
	  $billingAddress->MemberID = $member->ID;
	  $billingAddress->FirstName = $data['Billing']['FirstName'];
	  $billingAddress->Surname = $data['Billing']['Surname'];
	  $billingAddress->Company = $data['Billing']['Company'];
	  $billingAddress->Address = $data['Billing']['Address'];
	  $billingAddress->AddressLine2 = $data['Billing']['AddressLine2'];
	  $billingAddress->City = $data['Billing']['City'];
	  $billingAddress->PostalCode = $data['Billing']['PostalCode'];
	  $billingAddress->State = $data['Billing']['State'];
	  $billingAddress->Country = $data['Billing']['Country'];
	  $billingAddress->Type = 'Billing';
	  $billingAddress->write();
	  
	  $shippingAddress = new Address();
	  $shippingAddress->OrderID = $order->ID;
	  $shippingAddress->MemberID = $member->ID;
	  $shippingAddress->FirstName = $data['Shipping']['FirstName'];
	  $shippingAddress->Surname = $data['Shipping']['Surname'];
	  $shippingAddress->Company = $data['Shipping']['Company'];
	  $shippingAddress->Address = $data['Shipping']['Address'];
	  $shippingAddress->AddressLine2 = $data['Shipping']['AddressLine2'];
	  $shippingAddress->City = $data['Shipping']['City'];
	  $shippingAddress->PostalCode = $data['Shipping']['PostalCode'];
	  $shippingAddress->State = $data['Shipping']['State'];
	  $shippingAddress->Country = $data['Shipping']['Country'];
	  $shippingAddress->Type = 'Shipping';
	  $shippingAddress->write();
    
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

}
