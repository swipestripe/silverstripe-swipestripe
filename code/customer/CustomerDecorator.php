<?php
/**
 * Add some fields to customer to facilitate the Payment class - mostly address related fields.
 * Payment class requires these fields in order to pass address information to the payment gateway.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage customer
 * @version 1.0
 */
class CustomerDecorator extends DataObjectDecorator {

  /**
   * Extra DB fields, mostly for address - requirements for the Payment class.
   * Also ties Member class with Address and Order classes.
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'db' => array(
				'Address' => 'Varchar(255)',
				'AddressLine2' => 'Varchar(255)',
				'City' => 'Varchar(100)',
				'PostalCode' => 'Varchar(30)',
				'State' => 'Varchar(100)',
				'Country' => 'Varchar',
				'HomePhone' => 'Varchar(100)',
				'Notes' => 'HTMLText' //TODO remove? Is this necessary for Payment class or something?
			),
			'has_many' => array(
			  'Addresses' => 'Address',
			  'Orders' => 'Order'
			)
		);
	}
	
	/**
	 * If this Member has Orders, then prevent member from being deleted.
	 * 
	 * @see DataObjectDecorator::onBeforeDelete()
	 */
  function onBeforeDelete() {

    $member = $this->owner;
    if ($member->inGroup('customers')) {
      
      $orders = $member->Orders();
      if ($orders && $orders->exists()) {
        throw new Exception("Cound not delete this customer they have orders.");
      }
    }
	}

	/**
	 * Add some fields for managing Members in the CMS.
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateCMSFields($fields) {
		$fields->removeByName('Country');
		$fields->addFieldToTab('Root.Main', new DropdownField('Country', 'Country', Geoip::getCountryDropDown()));
		$fields->removeByName('Notes');
		$fields->removeByName('Orders');
		$fields->removeByName('Addresses');
	}
	
	/**
	 * Retrieve the last used billing address for this Member from their previous saved addresses.
	 * 
	 * TODO make this more efficient
	 * 
	 * @return Address The last billing address
	 */
  function BillingAddress() {
	  $address = null;

	  $addresses = $this->owner->Addresses();
	  $addresses->sort('Created', 'ASC');
	  if ($addresses && $addresses->exists()) foreach ($addresses as $billingAddress) {
	    if ($billingAddress->Type == 'Billing') $address = $billingAddress; 
	  }
	  
	  return $address;
	}
	
	/**
	 * Retrieve the last used shipping address for this Member from their previous saved addresses.
	 * 
	 * TODO make this more efficient
	 * 
	 * @return Address The last shipping address
	 */
  function ShippingAddress() {
	  $address = null;

	  $addresses = $this->owner->Addresses();
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
	  return DataObject::get('Order', "`MemberID` = " . $this->owner->ID . " AND `Order`.`Status` != 'Cart'", "`Created` DESC");
	}

}
