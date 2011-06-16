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
    
    Requirements::css('simplecart/css/OrderReport.css');

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
			new TextField('PostalCode', 'Postal Code'),
			new DropdownField('Country', 'Country', Geoip::getCountryDropDown(), 'NZ')
    );
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
				'<p>Please choose a password, so you can login and check your order history in the future</p><br/>'
			));
			$rightFields->push(new FieldGroup(new ConfirmedPasswordField('Password', 'Password')));
			
			$validator->addRequiredField('Password');
		}
    
    $order = CartController::get_current_order();
    $paymentFields = Payment::combined_form_fields($order->Total->getAmount());
		foreach ($paymentFields as $field) {
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
	  if(!$member = DataObject::get_one('Member', "\"Email\" = '".$data['Email']."'")){
			$member = new Member();
			$form->saveInto($member);
			$member->write();
			$member->logIn();
		}else{
		  
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
		$order = CartController::get_current_order();
		$items = $order->Items();

		//Save the order
		$form->saveInto($order);
		$order->MemberID = $member->ID;
		$order->write();

		//Save the order items (not sure why can't do this with writeComponents() perhaps because Items() are cached?!)
	  foreach ($items as $item) {
      $item->OrderID = $order->ID;
		  $item->write();
    }

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
		}

		Director::redirect($order->Link());
		return true;
		
		
		
		/*
		 * Look at below for ideas
		 */
		
		
		//check for cart items
		if(!ShoppingCart::has_items()) {
			$form->sessionMessage(_t('OrderForm.NoItemsInCart','Please add some items to your cart'), 'bad');
			Director::redirectBack();
			return false;
		}

		//check that price hasn't changed
		$oldtotal = ShoppingCart::current_order()->Total();

		// Create new Order from shopping cart, discard cart contents in session
		$order = ShoppingCart::current_order();
		if($order->Total() != $oldtotal) {
			$form->sessionMessage(_t('OrderForm.PriceUpdated','The order price has been updated'), 'warning');
			Director::redirectBack();
			return false;
		}

		// Create new OR update logged in {@link Member} record
		$member = EcommerceRole::ecommerce_create_or_merge($data);
		if(!$member) {
			
			$form->sessionMessage(
				_t(
					'OrderForm.MEMBEREXISTS', 'Sorry, a member already exists with that email address.
					If this is your email address, please log in first before placing your order.'
				),
				'bad'
			);
			
			Director::redirectBack();
			return false;
		}

		$member->write();
		$member->logIn();

		if($member)	$payment->PaidByID = $member->ID;

		// Write new record {@link Order} to database
		$form->saveInto($order);
		$order->save(); //sets status to 'Unpaid'
		$order->MemberID = $member->ID;
		$order->write();

		$this->clearSessionData(); //clears the stored session form data that might have been needed if validation failed

		// Save payment data from form and process payment
		$form->saveInto($payment);
		$payment->OrderID = $order->ID;
		$payment->PaidForID = $order->ID;
		$payment->PaidForClass = $order->class;
		
		$payment->Amount->Amount = $order->Total();
		$payment->write();
		
		//prepare $data - ie put into the $data array any fields that may need to be there for payment

		// Process payment, get the result back
		$result = $payment->processPayment($data, $form);

		// isProcessing(): Long payment process redirected to another website (PayPal, Worldpay)
		if($result->isProcessing()) {
			return $result->getValue();
		}

		if($result->isSuccess()) {
			$order->sendReceipt();
		}

		Director::redirect($order->Link());
		return true;

	}

}