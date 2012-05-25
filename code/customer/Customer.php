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
		'HomePhone' => 'Varchar(100)',
		'Notes' => 'HTMLText' //TODO remove? Is this necessary for Payment class or something?
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
  function onBeforeDelete() {
    
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
	 * @return FieldSet
	 */
	public function getCMSFields() {
	  
	  $fields = parent::getCMSFields();
	  
		$fields->addFieldToTab('Root.Address', new TextField('Address', _t('Customer.ADDRESS', "Address")));
		$fields->addFieldToTab('Root.Address', new TextField('AddressLine2', ''));
		$fields->addFieldToTab('Root.Address', new TextField('City', _t('Customer.CITY', "City")));
		$fields->addFieldToTab('Root.Address', new TextField('State', _t('Customer.STATE', "State")));
		$fields->addFieldToTab('Root.Address', new TextField('PostalCode', _t('Customer.POSTAL_CODE', "Postal Code")));
		$fields->addFieldToTab('Root.Address', new DropdownField('Country', _t('Customer.COUNTRY', "Country"), Geoip::getCountryDropDown()));
		
		$fields->removeByName('Street');
		$fields->removeByName('Suburb');
		$fields->removeByName('CityTown');
		$fields->removeByName('DateFormat');
		$fields->removeByName('TimeFormat');
		$fields->removeByName('Notes');
		$fields->removeByName('Orders');
		$fields->removeByName('Addresses');
		$fields->removeByName('Groups');
		$fields->removeByName('Permissions');
		
		return $fields;
	}
	
	/**
	 * Retrieve the last used billing address for this Member from their previous saved addresses.
	 * TODO make this more efficient
	 * 
	 * @return Address The last billing address
	 */
  function BillingAddress() {
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
  function ShippingAddress() {
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
	 * @return DataObjectSet Set of previous orders for this member
	 */
	function Orders() {
	  $orders = DataObject::get('Order', "\"MemberID\" = " . $this->ID . " AND \"Order\".\"Status\" != 'Cart'", "\"Created\" DESC");
	  if (!$orders) $orders = new DataObjectSet(); //No idea why this is necessary, StockLevelTest was failing suddenly though
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
