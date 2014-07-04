<?php
/**
 * Order, created as soon as a user adds a {@link Product} to their cart, the cart is 
 * actually an Order with status of 'Cart'. Has many {@link Item}s and can have {@link Modification}s
 * which might represent a {@link Modifier} like shipping, tax, coupon codes.
 */
class Order extends DataObject implements PermissionProvider {
	
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
	 * DB fields for Order, such as Stauts, Payment Status etc.
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled,Cart','Cart')",
		'PaymentStatus' => "Enum('Unpaid,Paid','Unpaid')",

		'TotalPrice' => 'Decimal(19,8)',
		'SubTotalPrice' => 'Decimal(19,8)',

		'BaseCurrency' => 'Varchar(3)',
		'BaseCurrencySymbol' => 'Varchar(10)',

		'OrderedOn' => 'SS_Datetime',
		'LastActive' => 'SS_Datetime',
		'Env' => 'Varchar(10)',
	);

	public function Total() {

		// TODO: Multi currency

		$amount = Price::create();
		$amount->setAmount($this->TotalPrice);
		$amount->setCurrency($this->BaseCurrency);
		$amount->setSymbol($this->BaseCurrencySymbol);
		return $amount;
	}

	/**
	 * Display price, can decorate for multiple currency etc.
	 * 
	 * @return Price
	 */
	public function TotalPrice() {
		
		$amount = $this->Total();
		$this->extend('updatePrice', $amount);
		return $amount;
	}

	public function SubTotal() {

		// TODO: Multi currency

		$amount = Price::create();
		$amount->setAmount($this->SubTotalPrice);
		$amount->setCurrency($this->BaseCurrency);
		$amount->setSymbol($this->BaseCurrencySymbol);
		return $amount;
	}

	/**
	 * Display price, can decorate for multiple currency etc.
	 * 
	 * @return Price
	 */
	public function SubTotalPrice() {
		
		$amount = $this->SubTotal();
		$this->extend('updatePrice', $amount);
		return $amount;
	}

	public function CartTotalPrice() {

		$total = $this->SubTotal();
		$amount = $total->getAmount();

		//Remove cost of modifications for displaying on the cart
		$mods = $this->SubTotalModifications();

		if ($mods && $mods->exists()) foreach ($mods as $mod) {
			$amount -= $mod->Amount()->getAmount();
		}

		$total->setAmount($amount);
		$this->extend('updatePrice', $total);
		return $total;
	}

	/**
	 * Relations for this Order
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Member' => 'Customer'
	);

	/*
	 * Relations for this Order
	 * 
	 * @var Array
	 */
	private static $has_many = array(
		'Items' => 'Item',
		'Payments' => 'Payment',
		'Modifications' => 'Modification',
		'Updates' => 'Order_Update'
	);
	
	/**
	 * Summary fields for displaying Orders in the admin area
	 * 
	 * @var Array
	 */
	private static $summary_fields = array(
		'ID' => 'Order No',
		'OrderedOn' => 'Ordered On',
		'Member.Name' => 'Customer',
		'Member.Email' => 'Email',
		'SummaryOfTotal' => 'Total',
		'Status' => 'Status'
	);
	
	/**
	 * Searchable fields with search filters
	 * 
	 * @var Array
	 */
	private static $searchable_fields = array(
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
		'Status' => array(
			'title' => 'Status',
			'filter' => 'ShopSearchFilter_OptionSet'
		)
	);
	
	/**
	 * The default sort expression. This will be inserted in the ORDER BY
	 * clause of a SQL query if no other sort expression is provided.
	 * 
	 * @see ShopAdmin
	 * @var String
	 */
	private static $default_sort = 'ID DESC';

	/**
	 * The starting number for Order IDs. If none set starts at 1.
	 * 
	 * @var Int
	 */
	public static $first_id = null;

	public function providePermissions() {
		return array(
			'VIEW_ORDER' => 'View orders',
			'EDIT_ORDER' => 'Edit orders'
		);
	}

	public function canView($member = null) {

		if ($member == null && !$member = Member::currentUser()) return false;

		$administratorPerm = Permission::check('ADMIN') && Permission::check('VIEW_ORDER', 'any', $member);
		$customerPerm = Permission::check('VIEW_ORDER', 'any', $member) && $member->ID == $this->MemberID;

		return $administratorPerm || $customerPerm;
	}
	
	/**
	 * Prevent orders from being edited in the CMS
	 * 
	 * @see DataObject::canEdit()
	 * @return Boolean False always
	 */
	public function canEdit($member = null) {
		$administratorPerm = Permission::check('ADMIN') && Permission::check('EDIT_ORDER', 'any', $member);
		
		return $administratorPerm;
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
		return Permission::check('ADMIN');
	}

	/**
	 * Clean up Order Items (ItemOptions by extension) and Modifications.
	 * All wrapped in a transaction.
	 */
	public function delete() {

		if ($this->canDelete(Member::currentUser())) {
			try {

				DB::getConn()->transactionStart();
				
				$payments = $this->Payments();
				if ($payments && $payments->exists()) foreach ($payments as $payment) {
					$payment->delete();
					$payment->destroy();
				}

				$items = $this->Items();
				if ($items && $items->exists()) foreach ($items as $item) {
					$item->delete();
					$item->destroy();
				}
				
				$modifications = $this->Modifications();
				if ($modifications && $modifications->exists()) foreach ($modifications as $modification) {
					$modification->delete();
					$modification->destroy();
				}
				
				$updates = $this->Updates();
				if ($updates && $updates->exists()) foreach ($updates as $update) {
					$update->delete();
					$update->destroy();
				}
				
				parent::delete();
				DB::getConn()->transactionEnd();
				
			}
			catch (Exception $e) {
				DB::getConn()->transactionRollback();
				SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);
				user_error("$this->class could not be deleted.", E_USER_ERROR);
			}
		}
	}

	/**
	 * Filters for order admin area search.
	 * 
	 * @see DataObject::scaffoldSearchFields()
	 * @return FieldSet
	 */
	public function scaffoldSearchFields($params = array()){

		$fields = parent::scaffoldSearchFields();

		$request = Controller::curr()->getRequest();
		$query = $request->requestVar('q');
		$statusVal = isset($query['Status']) ? $query['Status'] : array();

		$fields->push(CheckboxSetField::create('Status', 'Status', array(
			'Pending' => 'Pending',
			'Processing' => 'Processing',
			'Dispatched' => 'Dispatched'
		))->setValue($statusVal));

		return $fields;
	}

	/**
	 * Get a new search context for filtering
	 * the search results in OrderAdmin
	 * 
	 * @see DataObject::getDefaultSearchContext()
	 * @return ShopSearchContext
	 */
	public function getDefaultSearchContext() {
		return new ShopSearchContext_Order(
			$this->class,
			$this->scaffoldSearchFields(),
			$this->defaultSearchFilters()
		);
	}

	/**
	 * Set the LastActive time when {@link Order} first created.
	 * 
	 * (non-PHPdoc)
	 * @see DataObject::onBeforeWrite()
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if (!$this->ID) $this->LastActive = SS_Datetime::now()->getValue();

		//Set the base currency
		if (!$this->BaseCurrency || !$this->BaseCurrencySymbol) {
			$shopConfig = ShopConfig::current_shop_config();
			$this->BaseCurrency = $shopConfig->BaseCurrency;
			$this->BaseCurrencySymbol = $shopConfig->BaseCurrencySymbol;
		}

		//If orders do not exist set the first ID
		if ((!Order::get()->count() && true) && is_numeric(self::$first_id) && self::$first_id > 0) {
			$this->ID = self::$first_id;
		}

		//Set environment order was placed in
		$this->Env = Director::get_environment_type();

		//Update paid status
		$this->PaymentStatus = ($this->getPaid()) ? 'Paid' : 'Unpaid';
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
	}

	public function onBeforePayment() {
		$this->extend('onBeforePayment');
	}

	/**
	 * Processed if payment is successfully written, send a receipt to the customer
	 * and notification to the admin
	 * 
	 * @see Payment_Extension::onAfterWrite()
	 */
	public function onAfterPayment() {

		$this->Status = ($this->getPaid()) ? self::STATUS_PROCESSING :  self::STATUS_PENDING;
		$this->PaymentStatus = ($this->getPaid()) ? 'Paid' : 'Unpaid';
		$this->write();

		ReceiptEmail::create($this->Member(), $this)
			->send();
		NotificationEmail::create($this->Member(), $this)
			->send();

		$this->extend('onAfterPayment');
	}
	
	/**
	 * Set CMS fields for viewing this Order in the CMS
	 * Cannot change status of an order in the CMS
	 * 
	 * @see DataObject::getCMSFields()
	 */
	public function getCMSFields() {

		$fields = new FieldList();

		$fields->push(new TabSet('Root', 
			Tab::create('Order')
		));

		//Override this in updateOrderCMSFields to change the order template in the CMS
		$htmlSummary = $this->customise(array(
			'MemberEmail' => $this->Member()->Email
		))->renderWith('OrderAdmin');
		$fields->addFieldToTab('Root.Order', new LiteralField('MainDetails', $htmlSummary));

		//Updates
		$listField = new GridField(
			'Updates',
			'Updates',
			$this->Updates(),
			GridFieldConfig_Basic::create()
		);
		$fields->addFieldToTab('Root.Updates', $listField);

		//Ability to edit fields added to CMS here
		$this->extend('updateOrderCMSFields', $fields);

		return $fields;
	}
	
	/**
	 * Set custom CMS actions which call 
	 * OrderAdmin_RecordController actions of the same name
	 * 
	 * @see DataObject::getCMSActions()
	 * @return FieldList
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
	public function SummaryOfTotal() {
		return $this->Total()->Nice();
	}
	
	/**
	 * Generate the URL for viewing this order on the frontend
	 * 
	 * @see PaypalExpressCheckoutaPayment_Handler::doRedirect()
	 * @return String URL for viewing this order
	 */
	public function Link() {
		//get the account page and go to it
		$account = DataObject::get_one('AccountPage');
		return $account->Link()."order/$this->ID";
	}

	/**
	 * Helper to get {@link Payment}s that are made against this Order
	 * 
	 * @return ArrayList Set of Payment objects
	 */
	public function Payments() {
		return Payment::get()
			->where("\"OrderID\" = {$this->ID}");
	}
	
	/**
	 * Calculate the total outstanding for this order that remains to be paid,
	 * all payments except 'Failure', 'Pending' and 'Incomplete' payments are considered - so only 'Success' payments.
	 * 
	 * @return Money With value and currency of total outstanding
	 */
	public function TotalOutstanding() {
		$total = $this->Total()->getAmount();

		foreach ($this->Payments() as $payment) {
			if ($payment->Status == 'Success') {
				$total -= $payment->Amount->getAmount();
			}
		}
		
		//Total outstanding cannot be negative 
		if ($total < 0) $total = 0;

		// TODO: Multi currency
		
		$outstanding = Price::create();
		$outstanding->setAmount($total);
		$outstanding->setCurrency($this->BaseCurrency);
		$outstanding->setSymbol($this->BaseCurrencySymbol);
		
		return $outstanding;
	}
	
	/**
	 * Calculate the total paid for this order, only 'Success' payments
	 * are considered.
	 * 
	 * @return Price With value and currency of total paid
	 */
	public function TotalPaid() {
		 $paid = 0;
		 
		if ($this->Payments()) foreach ($this->Payments() as $payment) {
			if ($payment->Status == 'Success') {
				$paid += $payment->Amount->getAmount();
			}
		}
		
		$totalPaid = Price::create();
		$totalPaid->setAmount($paid);
		$totalPaid->setCurrency($this->BaseCurrency);
		$totalPaid->setSymbol($this->BaseCurrencySymbol);
		
		return $totalPaid;
	}
	
	/**
	 * If the order has been totally paid.
	 * 
	 * @return Boolean
	 */
	public function getPaid() {
		return ($this->Total()->getAmount() - $this->TotalPaid()->getAmount()) <= 0;
	}
	
	/**
	 * Add an item to the order representing the product, 
	 * if an item for this product exists increase the quantity. Update the Order total afterward.
	 * 
	 * @param DataObject $product The product to be represented by this order item
	 * @param ArrayList $productOptions The product variations to be added, usually just one
	 */
	public function addItem(Product $product, Variation $variation, $quantity = 1, ArrayList $options = null) {

		//Increment the quantity if this item exists already
		$item = $this->findIdenticalItem($product, $variation, $options);

		if ($item && $item->exists()) {
			$item->Quantity = $item->Quantity + $quantity;
			$item->write();
		}
		else {

			DB::getConn()->transactionStart();
			try {

				$item = new Item();
				$item->ProductID = $product->ID;
				$item->ProductVersion = $product->Version;

				//TODO: Think about percentage discounts and stuff like that, needs to apply to variation as well for total price to be correct
				//TODO: Do not use Amount() here, need another accessor to support price discounts and changes though
				$item->Price = $product->Amount()->getAmount();
				$item->Currency = $product->Amount()->getCurrency();

				if ($variation && $variation->exists()) {
					$item->VariationID = $variation->ID;
					$item->VariationVersion = $variation->Version;

					//TODO: Do not use Amount() here, need another accessor to support price discounts and changes though
					$item->Price += $variation->Amount()->getAmount();
				}

				$item->Quantity = $quantity;
				$item->OrderID = $this->ID;
				$item->write();

				if ($options->exists()) foreach ($options as $option) {
					$option->ItemID = $item->ID;
					$option->write();
				}
			}
			catch (Exception $e) {

				DB::getConn()->transactionRollback();
				SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);
				throw $e;
			}
			DB::getConn()->transactionEnd();

		}
		
		$this->updateTotal();
		
		return $item;
	}
	
	/**
	 * Find an identical item in the order/cart, item is identical if the 
	 * productID, version and the options for the item are the same. Used to increase 
	 * quantity of items that already exist in the cart/Order.
	 * 
	 * @see Order::addItem()
	 * @param DatObject $product
	 * @param ArrayList $options
	 * @return DataObject
	 */
	public function findIdenticalItem($product, $variation, ArrayList $options) {

		$items = $this->Items();

		$filtered = $items->filter(array(
			'ProductID' => $product->ID, 
			'ProductVersion' => $product->Version
		));

		if ($variation && $variation->exists()) {
			$filtered = $filtered->filter(array(
				'VariationID' => $variation->ID, 
				'VariationVersion' => $variation->Version
			));
		}

		//Could have many products of same variation at this point, need to check product options carefully
		$optionsMap = $options->map('Description', 'Price');
		$existingItems = clone $filtered;
		foreach ($existingItems as $existingItem) {

			$existingOptionsMap = $existingItem->ItemOptions()->map('Description', 'Price')->toArray();

			if ($optionsMap != $existingOptionsMap) {
				$filtered = $filtered->exclude('ID', $existingItem->ID);
			}
		}
		return $filtered->first();
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
		$items = $this->Items();
		$modifications = $this->Modifications();
		$shopConfig = ShopConfig::current_shop_config();
		
		if ($items) foreach ($items as $item) {
			$total += $item->Total()->Amount;
			$subTotal += $item->Total()->Amount;
		}

		if ($modifications) foreach ($modifications as $modification) {
			
			if ($modification->SubTotalModifier) {
				$total += $modification->Amount()->getAmount();
				$subTotal += $modification->Amount()->getAmount();
			}
			else {
				$total += $modification->Amount()->getAmount();
			}
		}

		$this->SubTotalPrice = $subTotal;
		$this->TotalPrice = $total;

		//TODO: change this so doesn't write() in here
		$this->write();
	}

	/**
	 * Retreive products for this order from the order {@link Item}s.
	 * 
	 * @return ArrayList Set of {@link Product}s
	 */
	public function Products() {
		$items = $this->Items();
		$products = new ArrayList();
		foreach ($items as $item) {
			$products->push($item->Product());
		}
		return $products;
	}
	
	/**
	 * Helper to summarize payment status for an order.
	 * 
	 * @return String List of payments and their status
	 */
	public function SummaryOfPaymentStatus() {
		$payments = $this->Payments();
		$status = null;

		if ($payments instanceof DataList) {
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
	public function updateModifications(Array $data) {

		//Remove existing Modifications
		$existingModifications = $this->Modifications();
		foreach ($existingModifications as $modification) {
			$modification->delete();
		}
		$this->updateTotal();

		$mods = Modification::get_all();

		foreach ($mods as $modification) {

			$class = get_class($modification);
			$value = isset($data['Modifiers'][$class]) ? Convert::raw2sql($data['Modifiers'][$class]) : null;

			$modification->add($this, $value);
			$this->updateTotal();
		}

		return $this;
	}
	
	/**
	 * Valdiate this Order for use in Validators at checkout. Makes sure
	 * Items exist and each Item is valid.
	 * 
	 * @return ValidationResult
	 */
	public function validateForCart() {
		
		$result = new ValidationResult(); 
		$items = $this->Items();

		if (!$this->BaseCurrency) {
			$result->error(
				'Base currency is not set for this order',
				'BaseCurrencyError'
			);
		}
		
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
	public function validate() {
		$result = parent::validate();
		return $result;
	}
	
	/**
	 * Delete abandoned carts according to the Order timeout. This will release the stock 
	 * in the carts back to the shop. Can be run from a cron job task, also run on Product, Cart and
	 * Checkout pages so that cron job is not necessary.
	 * 
	 * @return Void
	 */
	public static function delete_abandoned() {

		$shopConfig = ShopConfig::current_shop_config();

		$timeout = DateInterval::createFromDateString($shopConfig->CartTimeout . ' ' . $shopConfig->CartTimeoutUnit);
		$ago = new DateTime();
		$ago->sub($timeout);

		//Get orders that were last active over x ago according to shop config cart lifetime
		$orders = Order::get()
			->where("\"Order\".\"LastActive\" < '" . $ago->format('Y-m-d H:i:s') . "' AND \"Order\".\"Status\" = 'Cart' AND \"Payment\".\"ID\" IS NULL")
			->leftJoin('Payment', "\"Payment\".\"OrderID\" = \"Order\".\"ID\"");

		if ($orders && $orders->exists()) foreach ($orders as $order) {
			$order->delete();
			$order->destroy();      
		}
	}
	
	/**
	 * Get modifications that apply changes to the Order sub total.
	 * 
	 * @return DataList Set of Modification DataObjects
	 */
	public function SubTotalModifications() {
		$mods = $this->Modifications();
		if ($mods && $mods->exists()) {
			return $mods->where("\"SubTotalModifier\" = 1");
		}
		return null;
	}
	
	/**
	 * Get modifications that apply changes to the Order total (not the order sub total).
	 * 
	 * @return DataList Set of Modification DataObjects
	 */
	public function TotalModifications() {
		$mods = $this->Modifications();
		if ($mods && $mods->exists()) {
			return $mods->where("\"SubTotalModifier\" = 0");
		}
		return null;
	}

	public function CustomerUpdates() {
		return $this->Updates()->where("\"Visible\" = 1");
	}

}

class Order_Update extends DataObject {

	private static $singular_name = 'Update';
	private static $plural_name = 'Updates';

	private static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled')",
		'Note' => 'Text',
		'Visible' => 'Boolean'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Order' => 'Order',
		'Member' => 'Member'
	);

	private static $summary_fields = array(
		'Created.Nice' => 'Created',
		'Status' => 'Order Status',
		'Note' => 'Note',
		'Member.Name' => 'Owner',
		'VisibleSummary' => 'Visible'
	);

	public function canDelete($member = null) {
		return false;
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}

	/**
	 * Update stock levels for {@link Item}.
	 * 
	 * @see DataObject::onAfterWrite()
	 */
	public function onAfterWrite() {

		parent::onAfterWrite();
		
		//Update the Order, setting the same status
		if ($this->Status) {
			$order = $this->Order();
			if ($order->exists()) {
				$order->Status = $this->Status;
				$order->write();
			}
		}
	}

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		$visibleField = DropdownField::create('Visible', 'Visible', array(
			1 => 'Yes',
			0 => 'No'
		))->setRightTitle('Should this update be visible to the customer?');
		$fields->replaceField('Visible', $visibleField);

		$memberField = HiddenField::create('MemberID', 'Member', Member::currentUserID());
		$fields->replaceField('MemberID', $memberField);
		$fields->removeByName('OrderID');

		return $fields;
	}

	public function Created() {
		return $this->dbObject('Created');
	}

	public function VisibleSummary() {
		return ($this->Visible) ? 'True' : '';
	}
}
