<?php
/**
 * Represents a {@link Customer}, a type of {@link Member}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Customer extends Member {
  
  /**
   * Extra DB fields, mostly for address - requirements for the Payment class.
   * Also ties Member class with Address and Order classes.
   * 
   * @var Array
   */
  static $db = array(
		'Address' => 'Varchar(255)',
		'AddressLine2' => 'Varchar(255)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'State' => 'Varchar(100)',
		'Country' => 'Varchar',
		'HomePhone' => 'Varchar(100)'
	);
	
	/**
	 * Link customers to {@link Address}es and {@link Order}s.
	 * 
	 * @var Array
	 */
	static $has_many = array(
	  'Addresses' => 'Address',
	  'Orders' => 'Order'
	);
	
	/**
	 * If this Member has Orders, then prevent member from being deleted.
	 * Belt and braces now, @see Customer::canDelete()
	 * 
	 * @see DataObject::onBeforeDelete()
	 */
	public function onBeforeDelete() {
    
    parent::onBeforeDelete();

    $member = $this;
    if ($member->inGroup('customers')) {
      
      $orders = $member->Orders();
      if ($orders && $orders->exists()) {
        throw new Exception(_t('Customer.CANNOT_DELETE_CUSTOMER', "Could not delete this customer they have orders."));
      }
    }
	}
	
	/**
	 * Prevent customers from being deleted.
	 * 
	 * @see Member::canDelete()
	 */
  public function canDelete($member = null) {
	  return false;
	}

	/**
	 * Add some fields for managing Members in the CMS.
	 * 
	 * @return FieldList
	 */
	public function getCMSFields() {

		$fields = new FieldList();

    $fields->push(new TabSet('Root', 
      Tab::create('Customer'),
      Tab::create('Address')
    ));

    $password = new ConfirmedPasswordField(
			'Password', 
			null, 
			null, 
			null, 
			true // showOnClick
		);
		$password->setCanBeEmpty(true);
		if(!$this->ID) $password->showOnClick = false;

    $fields->addFieldsToTab('Root.Customer', array(
    	new TextField('FirstName'),
    	new TextField('Surname'),
    	new EmailField('Email'),
    	new ConfirmedPasswordField('Password'),
    	$password
    ));

    $fields->addFieldsToTab('Root.Address', array(
    	new TextField('Address', _t('Customer.ADDRESS', "Address")),
    	new TextField('AddressLine2', ' '),
    	new TextField('City', _t('Customer.CITY', 'City')),
    	new TextField('State', _t('Customer.STATE', 'State')),
    	new TextField('PostalCode', _t('Customer.POSTAL_CODE', 'Postal Code')),
    	new DropdownField('Country', _t('Customer.COUNTRY', 'Country'), Country::get_codes())
    ));

    return $fields;
	}
	
	/**
	 * Retrieve the last used billing address for this Member from their previous saved addresses.
	 * TODO make this more efficient
	 * 
	 * @return Address The last billing address
	 */
	public function BillingAddress() {
	  $address = null;

	  $addresses = $this->Addresses();
	  $addresses->sort('Created', 'ASC');
	  if ($addresses && $addresses->exists()) foreach ($addresses as $billingAddress) {
	    if ($billingAddress->Type == 'Billing') $address = $billingAddress; 
	  }
	  
	  return $address;
	}
	
	/**
	 * Retrieve the last used shipping address for this Member from their previous saved addresses.
	 * TODO make this more efficient
	 * 
	 * @return Address The last shipping address
	 */
	public function ShippingAddress() {
	  $address = null;

	  $addresses = $this->Addresses();
	  $addresses->sort('Created', 'ASC');
	  if ($addresses && $addresses->exists()) foreach ($addresses as $shippingAddress) {
	    if ($shippingAddress->Type == 'Shipping') $address = $shippingAddress; 
	  }
	  return $address;
	}
	
	/**
	 * Overload getter to return only non-cart orders
	 * 
	 * @return ArrayList Set of previous orders for this member
	 */
	public function Orders() {
	  $orders = DataObject::get('Order', "\"MemberID\" = " . $this->ID . " AND \"Order\".\"Status\" != 'Cart'", "\"Created\" DESC");
	  if (!$orders) $orders = new ArrayList(); //No idea why this is necessary, StockLevelTest was failing suddenly though
	  return $orders;
	}
	
	/**
	 * Returns the current logged in customer
	 *
	 * @return bool|Member Returns the member object of the current logged in
	 *                     user or FALSE.
	 */
  static function currentUser() {
		$id = Member::currentUserID();
		if($id) {
			return DataObject::get_one("Customer", "\"Member\".\"ID\" = $id");
		}
	}
}
