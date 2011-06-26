<?php

class Order extends DataObject {

	public static $db = array(
		'Status' => "Enum('Unpaid,Paid,Cart','Cart')",
	  'Total' => 'Money',
		'ReceiptSent' => 'Boolean',
	  'PaidEmailSent' => 'Boolean'
	);
	
	public static $defaults = array(
	  'ReceiptSent' => false,
	  'PaidEmailSent' => false
	);

	public static $has_one = array(
		'Member' => 'Member'
	);

	public static $has_many = array(
	  'Items' => 'Item',
		'Payments' => 'Payment'
	);
	
	public static $table_overview_fields = array(
		'ID' => 'Order No',
		'Created' => 'Created',
		'Member.FirstName' => 'First Name',
		'Member.Surname' => 'Surname',
		'Total' => 'Total',
		'Status' => 'Status'
	);
	
	public static $summary_fields = array(
	  'ID' => 'Order No',
		'Created' => 'Created',
		'Member.Name' => 'Customer',
		'SummaryTotal' => 'Total',
		'Status' => 'Status'
	);
	
	public static $searchable_fields = array(
	  'ID' => array(
			'field' => 'TextField',
			'filter' => 'PartialMatchFilter',
			'title' => 'Order Number'
		),
		'Member.Surname' => array(
			'title' => 'Customer Surname',
			'filter' => 'PartialMatchFilter'
		),
		'Member.Email' => array(
			'title' => 'Customer Email',
			'filter' => 'PartialMatchFilter'
		),
		'TotalPaid' => array(
			'filter' => 'OrderAdminFilters_MustHaveAtLeastOnePayment',
		),
	);
	
	public static $casting = array(
		'TotalPaid' => 'Money'
	);
	
	/**
	 * Filter for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 */
  function scaffoldSearchFields(){
		$fieldSet = parent::scaffoldSearchFields();
		$fieldSet->push(new DropdownField("TotalPaid", "Has Payment", array(1 => "yes", 0 => "no")));
		return $fieldSet;
	}
	
	/**
	 * Prevent orders from being created in the CMS
	 * 
	 * @see DataObject::canCreate()
	 */
  public function canCreate($member = null) {
    return false;
//		return Permission::check('ADMIN', 'any', $member);
	}
	
	/**
	 * Prevent orders from being deleted in the CMS
	 * @see DataObject::canDelete()
	 */
  public function canDelete($member = null) {
    return false;
//		return Permission::check('ADMIN', 'any', $member);
	}
	
	/**
	 * Set CMS fields for viewing this Order in the CMS
	 * Cannot change status of an order in the CMS
	 * 
	 * @see DataObject::getCMSFields()
	 */
	public function getCMSFields() {
	  $fields = parent::getCMSFields();
	  
	  $fields->insertBefore(new LiteralField('Title',"<h2>Order #$this->ID - ".$this->dbObject('Created')->Format('g:i a, j M y')." - ".$this->Member()->getName()."</h2>"),'Root');
	  
	  $toBeRemoved = array();
	  $toBeRemoved[] = 'MemberID';
	  $toBeRemoved[] = 'Total';
	  $toBeRemoved[] = 'Items';
	  foreach($toBeRemoved as $field) {
			$fields->removeByName($field);
		}
		
		$htmlSummary = $this->renderWith("Order");
		$fields->addFieldToTab('Root.Main', new LiteralField('MainDetails', $htmlSummary));
		
		$fields->makeFieldReadonly('Status');

	  return $fields;
	}
	
	/**
	 * Helper to get a nicely formatted total of the order
	 * 
	 * @return String
	 */
	function SummaryTotal() {
	  return $this->dbObject('Total')->Nice();
	}
	
	/**
	 * Generate the URL for viewing this order on the frontend
	 * 
	 * @return String URL for viewing this order
	 */
	function Link() {
	  //get the account page and go to it
	  $account = DataObject::get_one('AccountPage');
		return $account->Link()."order/$this->ID";
	}

	/**
	 * Helper to get payments made for this order
	 * 
	 * @return DataObjectSet Set of Payment objects
	 */
	function Payments() {
	  $payments = DataObject::get('Payment', "PaidForID = $this->ID AND PaidForClass = '$this->class'");
	  return $payments;
	}
	
	/**
	 * Calculate the total outstanding for this order that remains to be paid,
	 * all payments except 'Failure' payments are considered
	 * 
	 * @return Money With value and currency of total outstanding
	 */
	function TotalOutstanding() {
	  $total = $this->Total->getAmount();

	  foreach ($this->Payments() as $payment) {
	    if ($payment->Status != 'Failure') {
	      $total -= $payment->Amount->getAmount();
	    }
	  }
	  
	  $outstanding = new Money();
	  $outstanding->setAmount($total);
	  $outstanding->setCurrency($this->Total->getCurrency());
	  
	  return $outstanding;
	}
	
	/**
	 * Calculate the total paid for this order, only 'Success' payments
	 * are considered.
	 * 
	 * @return Money With value and currency of total paid
	 */
	function TotalPaid() {
	   $paid = 0;
	   
	  foreach ($this->Payments() as $payment) {
	    if ($payment->Status == 'Success') {
	      $paid += $payment->Amount->getAmount();
	    }
	  }
	  
	  $totalPaid = new Money();
	  $totalPaid->setAmount($paid);
	  $totalPaid->setCurrency($this->Total->getCurrency());
	  
	  return $totalPaid;
	}
	
	/**
	 * Processed if payment is successful,
	 * send a receipt to the customer
	 * 
	 * @see PaymentDecorator::onAfterWrite()
	 */
	function onAfterPayment() {
	  
	  $this->updateStatus();

	  //Send a receipt to customer
		if(!$this->ReceiptSent){
			$receipt = new ReceiptEmail($this->Member(), $this);
  		if ($receipt->send()) {
  		  
  		  //Prevent another paid confirmation email being sent
  		  if ($this->getPaid()) {
  		    $this->PaidEmailSent = true;
  		  }
  		  
  		  //Send a notification to website owner
    		$orderEmail = new OrderEmail($this->Member(), $this);
    		$orderEmail->send();
  		  
  	    $this->ReceiptSent = true;
  	    $this->write();
  	  }
		}
	}
	
	/**
	 * Update the order status after payment,
	 * send email to customer if order is paid
	 * 
	 * @see Order::onAfterPayment()
	 */
	private function updateStatus() {

	  if ($this->getPaid()) {
	    $this->Status = 'Paid';
	    $this->write();
	    
	    if (!$this->PaidEmailSent) {
  	    $paidEmail = new PaidEmail($this->Member(), $this);
    		if ($paidEmail->send()) {
    	    $this->PaidEmailSent = true;
    	    $this->write();
    	  }
	    }
	  }
	  elseif ($this->Status == 'Cart') {
	    $this->Status = 'Unpaid';
	    $this->write();
	  }
	}
	
	/**
	 * If the order has been totally paid
	 * 
	 * @return Boolean
	 */
	public function getPaid() {
	  return ($this->TotalPaid()->getAmount() == $this->Total->getAmount());
	}
	
	/**
	 * Add an item to the order representing the product, 
	 * if an item for this product exists increase the quantity
	 * 
	 * @param DataObject $product The product to be represented by this order item
	 */
	function addItem(DataObject $product, $quantity = 1) {

	  //If quantity not correct throw error
	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
	    user_error("Cannot add item to cart, quantity must be a positive number.", E_USER_WARNING);
	  }

    //Increment the quantity if this item exists already
    $item = $this->Items()->find('ObjectID', $product->ID);
    
    if ($item && $item->exists()) {
      $item->Quantity = $item->Quantity + $quantity;
      $item->write();
    }
    else {
      $item = new Item();
      $item->ObjectID = $product->ID;
      $item->ObjectClass = $product->class;
      $item->Amount->setAmount($product->Amount->getAmount());
      $item->Amount->setCurrency($product->Amount->getCurrency());
      $item->Quantity = $quantity;
      $item->OrderID = $this->ID;
      $item->write();
    }
    
    $this->updateTotal();
	}
	
	/**
	 * Decrease quantity of an item or remove it if quantity = 1
	 * 
	 * @param DataObject $product The product to remove
	 */
	function removeItem(DataObject $product, $quantity = 1) {
	  
	  //If quantity not correct throw error
	  if (!$quantity || !is_numeric($quantity) || $quantity <= 0) {
	    user_error("Cannot remove item from cart, quantity mustbe a positive number.", E_USER_WARNING);
	  }

	  //Update order items
    $item = $this->Items()->find('ObjectID', $product->ID);

    if ($item && $item->exists()) {
      if ($item->Quantity <= $quantity) {
        $item->delete();
      }
      else {
        $item->Quantity = $item->Quantity - $quantity;
        $item->write();
      }
    }
    
    $this->updateTotal();
	}
	
	/**
	 * Go through items and update cart total
	 * 
	 * Had to use DataObject::get() to retrieve Items because
	 * $this->Items() was not returning any items after first call
	 * to $this->addItem().
	 */
	private function updateTotal() {
	  
	  $total = 0;
	  $items = DataObject::get('Item', 'OrderID = '.$this->ID);
	  
	  if ($items) foreach ($items as $item) {
	    $total += ($item->Amount->getAmount() * $item->Quantity);
	  }
	  
	  $this->Total->setAmount($total); 
	  $this->Total->setCurrency(Payment::site_currency());
    $this->write();
	}
	
	/**
	 * Retrieving the downloadable virtual products for this order
	 * 
	 * @return DataObjectSet Items for this order that can be downloaded
	 */
	function Downloads() {
	  
	  $virtualItems = new DataObjectSet();
	  $items = $this->Items();
	  
	  foreach ($items as $item) {
	    
	    if (isset($item->Object()->FileLocation) && $item->Object()->FileLocation) {
	      $virtualItems->push($item);
	    }
	  }
	  return $virtualItems;
	}
	
	/**
	 * Retreive products for this order from the order items.
	 * 
	 * @return DataObjectSet Set of Products, likely to be children of Page class
	 */
	function Products() {
	  $items = $this->Items();
	  $products = new DataObjectSet();
	  foreach ($items as $item) {
	    $products->push($item->Object());
	  }
	  return $products;
	}
	
	/**
	 * Helper to summarize payment status for an order.
	 * 
	 * @return String List of payments and their status
	 */
	function PaymentStatus() {
	  $payments = $this->Payments();
	  $status = null;

	  if ($payments instanceof DataObjectSet) {
  	  if ($payments->Count() == 1) {
  	    $status = 'Payment ' . $payments->First()->Status;
  	  }
  	  else {
  	    $statii = array();
    	  foreach ($payments as $payment) {
    	    $statii[] = "Payment #$payment->ID $payment->Status";
    	  }
    	  $status = implode(', ', $statii);
  	  }
	  }
	  return $status;
	}
	
	/**
	 * Testing to add auto increment to table
	 */
	public function augmentDatabase() {
//	  $tableName = $this->class;
//	  DB::query("ALTER TABLE $tableName AUTO_INCREMENT = 12547");
//	  
//	  SS_Log::log(new Exception(print_r("ALTER TABLE $tableName AUTO_INCREMENT = 12547", true)), SS_Log::NOTICE);
	}

}
