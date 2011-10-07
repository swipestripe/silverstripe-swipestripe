<?php

class CustomerDecorator extends DataObjectDecorator {

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
				'Notes' => 'HTMLText'
			),
			'has_many' => array(
			  'Addresses' => 'Address'
			)
		);
	}

	function updateCMSFields($fields) {
		$fields->removeByName('Country');
		$fields->addFieldToTab('Root.Main', new DropdownField('Country', 'Country', Geoip::getCountryDropDown()));
		$fields->removeByName('Notes');
	}
	
  function BillingAddress() {
	  $address = null;

	  $addresses = $this->owner->Addresses();
	  $addresses->sort('Created', 'ASC');
	  if ($addresses && $addresses->exists()) foreach ($addresses as $billingAddress) {
	    if ($billingAddress->Type == 'Billing') $address = $billingAddress; 
	  }
	  
	  return $address;
	}
	
  function ShippingAddress() {
	  $address = null;

	  $addresses = $this->owner->Addresses();
	  $addresses->sort('Created', 'ASC');
	  if ($addresses && $addresses->exists()) foreach ($addresses as $shippingAddress) {
	    if ($shippingAddress->Type == 'Shipping') $address = $shippingAddress; 
	  }
	  return $address;
	}

}
