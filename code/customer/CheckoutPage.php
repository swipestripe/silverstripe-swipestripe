<?php
class CheckoutPage extends Page
{
  static $has_many = array (
  );
  static $db = array(
    'ChequeMessage' => 'HTMLText' //Dependency for ChequePayment::ChequeContent()
  );
  static $has_one = array(
  );
  static $defaults = array(
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
	  
	  $leftFields = new CompositeField();
		$leftFields->setID('LeftCheckout');

		$rightFields = new CompositeField();
		$rightFields->setID('RightCheckout');
		
		$validator = new RequiredFields();
  
		$member = Member::currentUser() ? Member::currentUser() : singleton('Member');
		
		//Left fields
    $memberFields = new CompositeField(
			new HeaderField('Personal Information', 3),
			new TextField('FirstName', 'First Name', $member->FirstName),
			new TextField('Surname', 'Surname'),
			new TextField('HomePhone', 'Phone'),
			new EmailField('Email', 'Email'),
			new TextField('Address', 'Street Address'),
			new TextField('AddressLine2', 'Suburb'),
			new TextField('City', 'City'),
			new TextField('PostalCode', 'Postal Code')
    );
    
    //Set country field to country from browser if no member is logged in
    $countryField = new DropdownField('Country', 'Country', Geoip::getCountryDropDown());
    if (!$member) $countryField->setValue(Geoip::visitor_country());
    $memberFields->push($countryField);

    $validator->addRequiredField('FirstName');
    $validator->addRequiredField('Email');
    $validator->addRequiredField('HomePhone');
    
    $leftFields->push($memberFields);
    

    //Right fields
	  if(!$member->ID || $member->Password == '') {
			$rightFields->push(new HeaderField(_t('OrderForm.MembershipDetails','Membership Details'), 3));
			$rightFields->push(new LiteralField(
				'MemberInfo', 
				'<p class="message good">If you are already a member please <a href="Security/login?BackURL=' . $this->Link() . '">log in</a>.</p>'
			));
			$rightFields->push(new LiteralField(
				'AccountInfo', 
				'<p>Please choose a password, so you can login and check your order history in the future</p>'
			));
			$rightFields->push(new FieldGroup(new ConfirmedPasswordField('Password', 'Password')));
			
			$validator->addRequiredField('Password');
		}
    
    $order = Product_Controller::get_current_order();
    
    //Payment fields
    $paymentFields = Payment::combined_form_fields($order->Total->getAmount());
		foreach ($paymentFields as $field) {
		  $rightFields->push($field);
		}
		
		//Shipping fields
		$shippingFields = Shipping::combined_form_fields($order);
		foreach ($shippingFields as $field) {
		  $rightFields->push($field);
		}
		
		$fields = new FieldSet($leftFields, $rightFields);

    $actions = new FieldSet(
      new FormAction('ProcessOrder', 'Proceed to pay')
    );

    $form = new Form($this, 'OrderForm', $fields, $actions, $validator);
    $form->loadDataFrom($member);
    return $form;
	}
	
	/**
	 * Process the order by sending form information to Payment class
	 * 
	 * @see Payment::processPayment()
	 * @param Array $data
	 * @param Form $form
	 */
	function ProcessOrder($data, $form) {

	  //Get payment type
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
	
	/**
	 * Form including quantities for items for displaying on the checkout
	 * 
	 * TODO validator for positive quantity
	 * 
	 * @see CheckoutForm
	 * @see CheckoutQuantityField
	 */
	function CheckoutForm() {

	  $fields = new FieldSet();
	  $validator = new RequiredFields();
	  $currentOrder = $this->Cart();
	  $items = $currentOrder->Items();
	  
	  if ($items) foreach ($items as $item) {
	    
	    $quantityField = new CheckoutQuantityField('Quantity['.$item->ID.']', '', $item->Quantity);
	    $quantityField->setItem($item);
	    
	    $fields->push($quantityField);
	    
	    $itemOptions = $item->ItemOptions();
	    if ($itemOptions && $itemOptions->exists()) foreach($itemOptions as $itemOption) {
	      //TODO if item option is not a Variation then add it as another row to the checkout
	      //Like gift wrapping as an option perhaps
	    } 
	    
	    $validator->addRequiredField('Quantity['.$item->ID.']');
	  } 
	  
    $actions = new FieldSet(
      new FormAction('updateCart', 'Update Cart')
    );
    
    return new CheckoutForm($this, 'updateCart', $fields, $actions, $validator, $currentOrder);
	}
	
	/**
	 * Update the current cart quantities
	 * 
	 * @param SS_HTTPRequest $data
	 */
	function updateCart(SS_HTTPRequest $data) {

	  $currentOrder = $this->Cart();
	  $quantities = $data->postVar('Quantity');

	  if ($quantities) foreach ($quantities as $itemID => $quantity) {
	    
  	  //If quantity not correct throw error
  	  if (!is_numeric($quantity) || $quantity < 0) {
  	    user_error("Cannot change quantity, quantity must be a non negative number.", E_USER_WARNING);
  	  }

	    if ($item = $currentOrder->Items()->find('ID', $itemID)) {
	      
  	    if ($quantity == 0) {
    	    $item->delete();
    	  }
    	  else {
    	    $item->Quantity = $quantity;
	        $item->write();
    	  }
	    }
	  }
	  
	  $currentOrder->updateTotal();
	  Director::redirectBack();
	}

}