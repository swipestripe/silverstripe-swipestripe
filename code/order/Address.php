<?php
/**
 * Represents a shipping or billing address which are both attached to {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Address extends DataObject {

  /**
   * DB fields for an address
   * 
   * @var Array
   */
	public static $db = array(
	  'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
	  'Company' => 'Varchar',
	  'Address' => 'Varchar(255)',
		'AddressLine2' => 'Varchar(255)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'State' => 'Varchar(100)',

		//De-normalise these values in case region or country is deleted
	  'CountryName' => 'Varchar',
	  'CountryCode' => 'Varchar(2)', //ISO 3166 
	  'RegionName' => 'Varchar',
	  'RegionCode' => 'Varchar(2)'
	);

	/**
	 * Relations for address
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Order' => 'Order',
	  'Member' => 'Customer',  
	  'Country' => 'Country',
	  'Region' => 'Region'
	);
	
}

class Address_Shipping extends Address {

	function onBeforeWrite() {
		parent::onBeforeWrite();

		$code = $this->CountryCode;
		$country = Country_Shipping::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($country && $country->exists()) {
			$this->CountryName = $country->Title;
			$this->CountryID = $country->ID;
		}

		$code = $this->RegionCode;
		$region = Region_Shipping::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($region && $region->exists()) {
			$this->RegionName = $region->Title;
			$this->RegionID = $region->ID;
		}
	}

	/**
	 * Return data in an Array with keys formatted to match the field names
	 * on the checkout form so that it can be loaded into an order form.
	 * 
	 * @see Form::loadDataFrom()
	 * @return Array Data for loading into the form
	 */
	function getCheckoutFormData() {
	  $formattedData = array();
	  
	  $formattedData['Shipping[FirstName]'] = $this->FirstName;
	  $formattedData['Shipping[Surname]'] = $this->Surname;
	  $formattedData['Shipping[Company]'] = $this->Company;
	  $formattedData['Shipping[Address]'] = $this->Address;
	  $formattedData['Shipping[AddressLine2]'] = $this->AddressLine2;
	  $formattedData['Shipping[City]'] = $this->City;
	  $formattedData['Shipping[PostalCode]'] = $this->PostalCode;
	  $formattedData['Shipping[State]'] = $this->State;
	  $formattedData['Shipping[CountryCode]'] = $this->CountryCode;
	  $formattedData['Shipping[RegionCode]'] = $this->RegionCode;
	  
	  return $formattedData;
	}
}

class Address_Billing extends Address {

	function onBeforeWrite() {
		parent::onBeforeWrite();

		$code = $this->CountryCode;
		$country = Country_Billing::get()
			->where("\"Code\" = '$code'")
			->first();

		if ($country && $country->exists()) {
			$this->CountryName = $country->Title;
			$this->CountryID = $country->ID;
		}
	}

	/**
	 * Return data in an Array with keys formatted to match the field names
	 * on the checkout form so that it can be loaded into an order form.
	 * 
	 * @see Form::loadDataFrom()
	 * @return Array Data for loading into the form
	 */
	function getCheckoutFormData() {
	  $formattedData = array();
	  
	  $formattedData['Billing[FirstName]'] = $this->FirstName;
	  $formattedData['Billing[Surname]'] = $this->Surname;
	  $formattedData['Billing[Company]'] = $this->Company;
	  $formattedData['Billing[Address]'] = $this->Address;
	  $formattedData['Billing[AddressLine2]'] = $this->AddressLine2;
	  $formattedData['Billing[City]'] = $this->City;
	  $formattedData['Billing[PostalCode]'] = $this->PostalCode;
	  $formattedData['Billing[State]'] = $this->State;
	  $formattedData['Billing[CountryCode]'] = $this->CountryCode;
	  
	  return $formattedData;
	}
}
