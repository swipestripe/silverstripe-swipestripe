<?php
/**
 * Order, created as soon as a user adds a {@link Product} to their cart, the cart is 
 * actually an Order with status of 'Cart'. Has many {@link Item}s and can have {@link Modifier}s
 * which might represent shippng, gift wrapping, coupon codes etc.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage order
 * @version 1.0
 */
class Order extends DataObject {
  
  /**
   * Order status once Order has been made, waiting for payment to clear/be approved
   * 
   * @var String
   */
  const STATUS_PENDING = 'Pending';
  
  /**
   * Order status once payment approved, order being processed before being dispatched
   * 
   * @var String
   */
  const STATUS_PROCESSING = 'Processing';
  
  /**
   * Order status once Order has been sent
   * 
   * @var String
   */
  const STATUS_DISPATCHED = 'Dispatched';
  
  /**
   * Related to timeouts on checkout page for stock management. Not implemented yet.
   * 
   * @var Int
   */
  protected static $timeout = 0;

  /**
   * DB fields for Order, such as Stauts, Payment Status etc.
   * 
   * @var Array
   */
	public static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled,Cart','Cart')",
	  'PaymentStatus' => "Enum('Unpaid,Paid','Unpaid')",
	  'Total' => 'Money',
	  'SubTotal' => 'Money',
		'ReceiptSent' => 'Boolean',
	  'PaidEmailSent' => 'Boolean',
	  'OrderedOn' => 'SS_Datetime',
	  'LastActive' => 'SS_Datetime',
	  'Notes' => 'Text'
	);
	
	/**
	 * Default values for Order
	 * 
	 * @var Array
	 */
	public static $defaults = array(
	  'ReceiptSent' => false,
	  'PaidEmailSent' => false
	);

	/**
	 * Relations for this Order
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Member' => 'Member'
	);

	/*
	 * Relations for this Order
	 * 
	 * @var Array
	 */
	public static $has_many = array(
	  'Items' => 'Item',
		'Payments' => 'Payment',
	  'Modifiers' => 'Modifier',
	  'Addresses' => 'Address'
	);
	
	/**
	 * Overview fields for displaying Orders in the admin area
	 * 
	 * @var Array
	 */
	public static $table_overview_fields = array(
		'ID' => 'Order No',
		'Created' => 'Created',
		'Member.FirstName' => 'First Name',
		'Member.Surname' => 'Surname',
		'Total' => 'Total',
		'Status' => 'Status'
	);
	
	/**
	 * Summary fields for displaying Orders in the admin area
	 * 
	 * @var Array
	 */
	public static $summary_fields = array(
	  'ID' => 'Order No',
		'OrderedOn' => 'Date',
		'Member.Name' => 'Customer',
		'SummaryOfTotal' => 'Total',
		'Status' => 'Status'
	);
	
	/**
	 * Searchable fields with search filters
	 * 
	 * @var Array
	 */
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
	
	/**
	 * Castings for the searchable fields
	 * 
	 * @var Array
	 */
	public static $casting = array(
		'HasPayment' => 'Money'
	);
	
	/**
	 * Table type for Orders should be InnoDB to support transactions - which are not implemented yet.
	 * 
	 * @var Array
	 */
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Filters for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 * @return FieldSet
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
	 * @return DateRangeSearchContext
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
	 * @return Boolean False always
	 */
  public function canCreate($member = null) {
    return false;
	}
	
	/**
	 * Prevent orders from being deleted in the CMS
	 * 
	 * @see DataObject::canDelete()
	 * @return Boolean False always
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
	    'PaymentStatus',
	    'Modifiers',
	    'Addresses',
	    'SubTotal',
	    'LastActive',
	    'Notes'
	  );
	  foreach($toBeRemoved as $field) {
			$fields->removeByName($field);
		}

		$htmlSummary = $this->customise(array(
			'MemberEmail' => $this->Member()->Email
		))->renderWith("OrderAdmin");
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
		
		if ($this->Payments()) foreach ($this->Payments() as $item) {
		  
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
		
		//Remanant of an earlier version of the cart.
		//TODO move this to virtual products
		/*
		if ($this->Downloads() && $this->Downloads()->exists()) {
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
		}
		*/
		
	  return $fields;
	}
	
	/**
	 * Set custom CMS actions which call 
	 * OrderAdmin_RecordController actions of the same name
	 * 
	 * @see DataObject::getCMSActions()
	 * @return FieldSet
	 */
	public function getCMSActions() {
	  $actions = parent::getCMSActions();
	  return $actions;
	}
	
	/**
	 * Helper to get a nicely formatted total of the order
	 * 
	 * @return String Order total formatted with Nice()
	 */
	function SummaryOfTotal() {
	  return $this->dbObject('Total')->Nice();
	}
	
	/**
	 * Generate the URL for viewing this order on the frontend
	 * 
	 * @see PaypalExpressCheckoutaPayment_Handler::doRedirect()
	 * @return String URL for viewing this order
	 */
	function Link() {
	  //get the account page and go to it
	  $account = DataObject::get_one('AccountPage');
		return $account->Link()."order/$this->ID";
	}

	/**
	 * Helper to get {@link Payment}s that are made against this Order
	 * 
	 * @return DataObjectSet Set of Payment objects
	 */
	function Payments() {
	  return DataObject::get('Payment', "PaidForID = $this->ID AND PaidForClass = '$this->class'");
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
	 * Processed if payment is successfully written, send a receipt to the customer
	 * TODO move sending receipts to CheckoutPage::ProcessOrder()
	 * 
	 * @see PaymentDecorator::onAfterWrite()
	 */
	function onAfterPayment() {
	  
	  $this->updatePaymentStatus();

	  //Send a receipt to customer if payment has been completed
		if(!$this->ReceiptSent && $this->PaymentStatus == 'Paid'){
		  
		  //TODO Need some kind of payment completed flag because 
		  //this is being sent too soon, before payment details have been filled out
			$receipt = new ReceiptEmail($this->Member(), $this);
  		if ($receipt->send()) {
  	    $this->ReceiptSent = true;
  	    $this->write();
  	  }
  	  
  	  //Send a notification to website owner
  		$orderEmail = new OrderEmail($this->Member(), $this);
  		$orderEmail->send();
		}
		
		//TODO if payment is processing send an email too?
		//TODO if payment Status = Failure send a payment email?
	}
	
	/**
	 * Update the order payment status after Payment is made.
	 * 
	 * @see Order::onAfterPayment()
	 */
	public function updatePaymentStatus() {

	  if ($this->getPaid()) {
	    $this->PaymentStatus = 'Paid';
	    $this->Status = self::STATUS_PROCESSING;
	    $this->write();
	  }
	  else {
	    $this->PaymentStatus = 'Unpaid';
	    $this->Status = self::STATUS_PENDING;
	    $this->write();
	  }
	}
	
	/**
	 * If the order has been totally paid.
	 * 
	 * @return Boolean
	 */
	public function getPaid() {
	  return $this->TotalPaid()->getAmount() == $this->Total->getAmount();
	}
	
	/**
	 * Add an item to the order representing the product, 
	 * if an item for this product exists increase the quantity. Update the Order total afterward.
	 * 
	 * @param DataObject $product The product to be represented by this order item
	 * @param DataObjectSet $productOptions The product variations to be added, usually just one
	 */
	function addItem(DataObject $product, $quantity = 1, DataObjectSet $productOptions = null) {

	  //Check that product options exist if product requires them
	  //TODO perform this validation in Item->validate(), cannot at this stage because Item is written before ItemOption, no transactions, chicken/egg problem
	  if ((!$productOptions || !$productOptions->exists()) && $product->requiresVariation()) {
	    user_error("Cannot add item to cart, product options are required.", E_USER_WARNING);
	    //Debug::friendlyError();
	    return;
	  }

    //Increment the quantity if this item exists already
    $item = $this->findIdenticalItem($product, $productOptions);
    
    if ($item && $item->exists()) {
      $item->Quantity = $item->Quantity + $quantity;
      $item->write();
    }
    else {

      //TODO this needs transactions for Item->validate() to check that ItemOptions exist for Item before it is written
      $item = new Item();
      $item->ObjectID = $product->ID;
      $item->ObjectClass = $product->class;
      $item->ObjectVersion = $product->Version;
      $item->Amount->setAmount($product->Amount->getAmount());
      $item->Amount->setCurrency($product->Amount->getCurrency());
      $item->Quantity = $quantity;
      $item->OrderID = $this->ID;
      $item->write();
      
      
      if ($productOptions && $productOptions->exists()) foreach ($productOptions as $productOption) {
        
        $itemOption = new ItemOption();
        $itemOption->ObjectID = $productOption->ID;
        $itemOption->ObjectClass = $productOption->class;
        $itemOption->ObjectVersion = $productOption->Version;
        $itemOption->Amount->setAmount($productOption->Amount->getAmount());
        $itemOption->Amount->setCurrency($productOption->Amount->getCurrency());
        $itemOption->ItemID = $item->ID;
        $itemOption->write();
      }
    }
    
    $this->updateTotal();
	}
	
	/**
	 * Find an identical item in the order/cart, item is identical if the 
	 * productID, version and the options for the item are the same. Used to increase 
	 * quantity of items that already exist in the cart/Order.
	 * 
	 * @see Order::addItem()
	 * @param DatObject $product
	 * @param DataObjectSet $productOptions
	 * @return DataObject
	 */
	function findIdenticalItem($product, DataObjectSet $productOptions) {
	  
	  foreach ($this->Items() as $item) {

	    if ($item->ObjectID == $product->ID && $item->ObjectVersion == $product->Version) {
	      
  	    $productOptionsMap = array();
  	    $existingOptionsMap = array();
  	    
    	  if ($productOptions) {
    	    $productOptionsMap = $productOptions->map('ID', 'Version');
    	  }

    	  if ($item) foreach ($item->ItemOptions() as $itemOption) {
    	    $productOption = $itemOption->Object();
    	    $existingOptionsMap[$productOption->ID] = $productOption->Version;
    	  }
    	  
    	  if ($productOptionsMap == $existingOptionsMap) {
    	    return $item;
    	  }
	    }
	  }
	}
	
	/**
	 * Go through items and modifiers and update cart total
	 * 
	 * Had to use DataObject::get() to retrieve Items because
	 * $this->Items() was not returning any items after first call
	 * to $this->addItem().
	 */
	public function updateTotal() {
	  
	  $total = 0;
	  $subTotal = 0;
	  $items = DataObject::get('Item', 'OrderID = '.$this->ID);
	  $modifiers = DataObject::get('Modifier', 'OrderID = '.$this->ID);
	  
	  if ($items) foreach ($items as $item) {
	    $total += $item->Total()->Amount;
	    $subTotal += $item->Total()->Amount;
	  }

	  if ($modifiers) foreach ($modifiers as $modifier) {
	    $total += $modifier->Amount->getAmount();
	  }

    $this->SubTotal->setAmount($subTotal); 
	  $this->SubTotal->setCurrency(Payment::site_currency());
	  $this->Total->setAmount($total); 
	  $this->Total->setCurrency(Payment::site_currency());
    $this->write();
	}

	/**
	 * Retreive products for this order from the order {@link Item}s.
	 * 
	 * @return DataObjectSet Set of {@link Product}s
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
	function SummaryOfPaymentStatus() {
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
	 * Save modifiers for this Order at the checkout process. 
	 * 
	 * @param Array $data
	 */
	function addModifiersAtCheckout(Array $data) {

	  //Save the order modifiers
    $existingModifiers = $this->Modifiers();
	  if (isset($data['Modifiers']) && is_array($data['Modifiers'])) foreach ($data['Modifiers'] as $modifierClass => $optionID) {
	    
	    //If the exact modifier exists on this order do not add it again,
	    //protects against resubmission of checkout form
	    if ($existingModifiers) foreach ($existingModifiers as $modifier) {
	      
	      if ($modifier->ModifierClass == $modifierClass) {
	          //&& $modifier->ModifierOptionID == $optionID) {
          
          //Update the current modifier
          $modifier->ModifierOptionID = $optionID;
	    
          $modifierInstance = new $modifierClass();
          $modifier->Amount = call_user_func(array($modifierInstance, 'Amount'), $optionID, $this);
          $modifier->Description = call_user_func(array($modifierInstance, 'Description'), $optionID);
          
          $modifier->OrderID = $this->ID;
          $modifier->write();
	            
	        continue 2;
	      }
	    }
	    
	    $modifier = new Modifier();
	    $modifier->ModifierClass = $modifierClass;
	    $modifier->ModifierOptionID = $optionID;
	    
	    $modifierInstance = new $modifierClass();
	    $modifier->Amount = call_user_func(array($modifierInstance, 'Amount'), $optionID, $this);
	    $modifier->Description = call_user_func(array($modifierInstance, 'Description'), $optionID);
	    
	    $modifier->OrderID = $this->ID;
	    $modifier->write();
	  }
	  $this->updateTotal();
	}
	
	/**
	 * Add addresses to this Order at the checkout.
	 * 
	 * @param Array $data
	 */
	function addAddressesAtCheckout(Array $data) {

	  $member = Member::currentUser() ? Member::currentUser() : singleton('Member');
    $order = CartControllerExtension::get_current_order();
    
    //If there is a current billing and shipping address, update them, otherwise create new ones
    $existingBillingAddress = $this->BillingAddress();
    $existingShippingAddress = $this->ShippingAddress();

    if ($existingBillingAddress && $existingBillingAddress->exists()) {
      $newData = array();
      if (is_array($data['Billing'])) foreach ($data['Billing'] as $fieldName => $value) {
        $newData[$fieldName] = $value;
      }
      $existingBillingAddress->update($newData);
      $existingBillingAddress->write();
    }
    else {
      $billingAddress = new Address();
  	  $billingAddress->OrderID = $order->ID;
  	  if ($member->ID) $billingAddress->MemberID = $member->ID;
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
    }

    if ($existingShippingAddress && $existingShippingAddress->exists()) {
      $newData = array();
      if (is_array($data['Shipping'])) foreach ($data['Shipping'] as $fieldName => $value) {
        $newData[$fieldName] = $value;
      }
      $existingShippingAddress->update($newData);
      $existingShippingAddress->write();
    }
    else {
  	  $shippingAddress = new Address();
  	  $shippingAddress->OrderID = $order->ID;
  	  if ($member->ID) $shippingAddress->MemberID = $member->ID;
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
    }
	}
	
	/**
	 * Retrieve the billing {@link Address} for this Order.
	 * 
	 * @return Address
	 */
	function BillingAddress() {
	  $address = null;
	  
	  $addresses = $this->Addresses();
	  if ($addresses && $addresses->exists()) {
	    $address = $addresses->find('Type', 'Billing');
	  }
	  
	  return $address;
	}
	
	/**
	 * Retrieve the shipping {@link Address} for this Order.
	 * 
	 * @return Address
	 */
  function ShippingAddress() {
	  $address = null;
	  
	  $addresses = $this->Addresses();
	  if ($addresses && $addresses->exists()) {
	    $address = $addresses->find('Type', 'Shipping');
	  }
	  
	  return $address;
	}
	
	/**
	 * Valdiate this Order for use in Validators at checkout. Makes sure
	 * Items exist and each Item is valid.
	 * 
	 * @return ValidationResult
	 */
	function validateForCart() {
	  
	  $result = new ValidationResult(); 
	  $items = $this->Items();
	  
	  if (!$items || !$items->exists()) {
	    $result->error(
	      'There are no items in this order',
	      'ItemExistsError'
	    );
	  }
	  
	  if ($items) foreach ($items as $item) {
	    
	    $validation = $item->validateForCart();
	    if (!$validation->valid()) {

	      $result->error(
  	      'Some of the items in this order are no longer available, please go to the cart and remove them.',
  	      'ItemValidationError'
  	    );
	    }
	  }
	  
	  return $result;
	}
	
	/**
	 * By default Orders are always valid
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}
	
	/**
	 * Set an order timeout, must be less than session timeouts, 
	 * timeout prevents products in the order being sold out in the mean 
	 * time. Not yet implemented.
	 * 
	 * @param Int $timeout
	 */
  public static function set_timeout($timeout) {
    
    //TODO check that session 
    $ssSessionTimeout = Session::get_timeout();
    $phpSessionTimeout = session_cache_expire();
    
		self::$timeout = intval($timeout);
	}
	
	/**
	 * Get the order timeout, for managing stock levels. Not yet implemented.
	 * 
	 * @return Int
	 */
	public static function get_timeout() {
		return self::$timeout;
	}
	
	/**
	 * Testing to add auto increment to table
	 * 
	 * @deprecated
	 */
	public function augmentDatabase() {
//	  $tableName = $this->class;
//	  DB::query("ALTER TABLE $tableName AUTO_INCREMENT = 12547");
//	  
	  //SS_Log::log(new Exception(print_r("ALTER TABLE $tableName AUTO_INCREMENT = 12547", true)), SS_Log::NOTICE);
	}
	
	/**
	 * Retrieving the downloadable virtual products for this order. This is a remanant of
	 * a previous version of the cart.
	 * 
	 * @return DataObjectSet Items for this order that can be downloaded
	 * @deprecated
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
}
