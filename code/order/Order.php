<?php

class Order extends DataObject {
  
  /**
   * Order has been made, waiting for payment
   * to clear/be approved
   * 
   * @var String
   */
  const STATUS_PENDING = 'Pending';
  
  /**
   * Payment approved, order being processed
   * before being dispatched
   * 
   * @var String
   */
  const STATUS_PROCESSING = 'Processing';
  
  /**
   * Order has been sent
   * 
   * @var String
   */
  const STATUS_DISPATCHED = 'Dispatched';

	public static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled,Cart','Cart')",
	  'PaymentStatus' => "Enum('Unpaid,Paid','Unpaid')",
	  'Total' => 'Money',
		'ReceiptSent' => 'Boolean',
	  'PaidEmailSent' => 'Boolean',
	  'OrderedOn' => 'SS_Datetime'
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
		'OrderedOn' => 'Date',
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
		'HasPayment' => array(
			'filter' => 'PaymentSearchFilter',
		),
		'OrderedOn' => array (
  		'filter' => 'DateRangeSearchFilter'
  	),
  	'Status' => array(
  	  'title' => 'Status',
  		'filter' => 'OptionSetSearchFilter',
  	)
	);
	
	public static $casting = array(
		'HasPayment' => 'Money'
	);
	
	/**
	 * Filter for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 */
  function scaffoldSearchFields(){
		$fieldSet = parent::scaffoldSearchFields();

		$fieldSet->push(new DropdownField("HasPayment", "Has Payment", array(1 => "yes", 0 => "no")));
		$fieldSet->push(new CheckboxSetField("Status", "Status", array(
		  'Pending' => 'Pending',
		  'Processing' => 'Processing',
		  'Dispatched' => 'Dispatched'
		)));
		return $fieldSet;
	}
	
	/**
	 * Get a new date range search context for filtering
	 * the search results in OrderAdmin
	 * 
	 * @see DataObject::getDefaultSearchContext()
	 */
  public function getDefaultSearchContext() {
  	return new DateRangeSearchContext(
  		$this->class,
  		$this->scaffoldSearchFields(),
  		$this->defaultSearchFilters()
  	);
  }
	
	/**
	 * Prevent orders from being created in the CMS
	 * 
	 * @see DataObject::canCreate()
	 */
  public function canCreate($member = null) {
    return false;
	}
	
	/**
	 * Prevent orders from being deleted in the CMS
	 * @see DataObject::canDelete()
	 */
  public function canDelete($member = null) {
    return false;
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
	  
    //Main fields
	  $toBeRemoved = array(
	    'MemberID',
	    'Total',
	    'Items',
	    'Status',
	    'ReceiptSent',
	    'PaidEmailSent',
	    'OrderedOn',
	    'PaymentStatus'
	  );
	  foreach($toBeRemoved as $field) {
			$fields->removeByName($field);
		}
		
		$htmlSummary = $this->renderWith("OrderAdmin");
		$fields->addFieldToTab('Root.Main', new LiteralField('MainDetails', $htmlSummary));
		
		$fields->removeFieldFromTab("Root", "Payments");
		
		//Action fields
		$fields->addFieldToTab("Root", new Tab('Actions'));
		
		$fields->addFieldToTab('Root.Actions', new HeaderField('OrderStatus', 'Order Status', 3));
		$statuses = $this->dbObject('Status')->enumValues();
		unset($statuses['Cart']);
		$fields->addFieldToTab('Root.Actions', new DropdownField('Status', 'Status', $statuses));
		
		$fields->addFieldToTab('Root.Actions', new HeaderField('PaymentStatus', 'Payments Status', 3));
		$fields->addFieldToTab('Root.Actions', new LiteralField('PaymentStatusP', "<p>Payment status of this order is currently <strong>$this->PaymentStatus</strong>.</p>"));
//		$fields->addFieldToTab('Root.Actions', new DropdownField('PaymentStatus', 'Payment Status', $this->dbObject('PaymentStatus')->enumValues()));
		
		foreach ($this->Payments() as $item) {
		  
		  $customerName = DataObject::get_by_id('Member', $item->PaidByID)->getName();
		  $value = $item->dbObject('Amount')->Nice();
		  $date = $item->dbObject('Created')->Format('j M y g:i a');
		  $paymentType = implode(' ', preg_split('/(?<=\\w)(?=[A-Z])/', get_class($item)));

		  $fields->addFieldToTab('Root.Actions', new DropdownField(
		  	'Payments['.$item->ID.']', 
		  	"$paymentType by $customerName <br />$value <br />$date", 
		    singleton('Payment')->dbObject('Status')->enumValues(),
		    $item->Status
		  ));
		}
		
		//TODO move this to virtual products
	  $fields->addFieldToTab('Root.Actions', new HeaderField('DownloadCount', 'Reset Download Counts', 3));
		$fields->addFieldToTab('Root.Actions', new LiteralField(
			'UpdateDownloadLimit', 
			'<p>Reset the download count for items below, can be used to allow customers to download items more times.</p>'
		));
		foreach ($this->Downloads() as $item) {
		  $fields->addFieldToTab('Root.Actions', new TextField(
		  	'DownloadCountItem['.$item->ID.']', 
		  	'Download Count for '.$item->Object()->Title.' (download limit = '.$item->getDownloadLimit() .')', 
		    $item->DownloadCount
		  ));
		}

	  return $fields;
	}
	
	/**
	 * Trying something for pending options UI in admin area 
	 * 
	 * @return unknown
	 */
	public function PendingOptions() {
	  
	  	//Workflow fields
//		$fields->addFieldToTab("Root", new Tab('WorkFlow'));
//		
//		$statuses = $this->dbObject('Status')->enumValues();
//		unset($statuses['Cart']);
//		$fields->addFieldToTab('Root.WorkFlow', new DropdownField('Status', 'Status', $statuses));
//		
//		SS_Log::log(new Exception(print_r($this->Status, true)), SS_Log::NOTICE);
//		
//		$workflowContent = $this->renderWith('OrderAdminWorkFlow');
//		$fields->addFieldToTab('Root.WorkFlow', new LiteralField('OrderWorkFlow', $workflowContent));

    $fields = new FieldSet();
    
    $fields->push(new DropdownField( 
      'PaymentStatus', 
      'This order is:', 
      $this->dbObject('PaymentStatus')->enumValues(),
      $this->PaymentStatus
    ));
    
    $content = ($this->ReceiptSent) ?'has' :'has not';
    $fields->push(new LiteralField('ReceiptSent', "A receipt <strong>$content</strong> been sent to the customer."));
    
    //Set the form as a hack to set form field IDs
    $form = new Form($this, 'EditForm', new FieldSet(), new FieldSet());
    $fields->setForm($form);
    return $fields;
	}
	
	/**
	 * Set custom CMS actions which call 
	 * OrderAdmin_RecordController actions of the same name
	 * 
	 * @see DataObject::getCMSActions()
	 */
	public function getCMSActions() {
	  $actions = parent::getCMSActions();
	  return $actions;
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
	   
	  if ($this->Payments()) foreach ($this->Payments() as $payment) {
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
	  
	  $this->updatePaymentStatus();

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
	 * Update the order payment status after payment,
	 * send email to customer if order is paid
	 * 
	 * @see Order::onAfterPayment()
	 */
	public function updatePaymentStatus() {

	  if ($this->getPaid()) {
	    $this->PaymentStatus = 'Paid';
	    $this->Status = self::STATUS_PROCESSING;
	    $this->write();
	    
	    if (!$this->PaidEmailSent) {
  	    $paidEmail = new PaidEmail($this->Member(), $this);
    		if ($paidEmail->send()) {
    	    $this->PaidEmailSent = true;
    	    $this->write();
    	  }
	    }
	  }
	  else {
	    $this->PaymentStatus = 'Unpaid';
	    $this->Status = self::STATUS_PENDING;
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
	function PaymentStatusSummary() {
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
	  SS_Log::log(new Exception(print_r("ALTER TABLE $tableName AUTO_INCREMENT = 12547", true)), SS_Log::NOTICE);
	}

}
