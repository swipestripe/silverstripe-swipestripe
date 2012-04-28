<?php
/**
 * Represents a shipping or billing address which are both attached to {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 * @version 1.0
 */
class Address extends DataObject {
  
  /**
	 * Countries for the billing address of the Checkout form.
	 * 
	 * @var Array List of countries for billing address e.g:'NZ' => 'New Zealand'
	 */
  public static $billing_countries = array(
  );
  
  /**
	 * Regions allowed to be shipped to, currently unused.
	 */
  public static $billing_regions = array(
  );
  
  /**
	 * Countries allowed to be shipped to, these will be options in the shipping address
	 * of the Checkout form.
	 * 
	 * @var Array List of countries that goods can be shipped to e.g:'NZ' => 'New Zealand'
	 */
  public static $shipping_countries = array(
  );
  
  /**
	 * Regions allowed to be shipped to, these will be options in the shipping address
	 * of the Checkout form.
	 * 
	 * @var Array List of regions that goods can be shipped to e.g:
	 *  'NZ' => array(
   *    'NI' => 'North Island',
   *    'SI' => 'South Island')
	 */
  public static $shipping_regions = array(
  );


  /**
   * DB fields for an address
   * 
   * @var Array
   */
	public static $db = array(
		'Type' => "Enum('Billing,Shipping','Billing')",
	  'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
	  'Company' => 'Varchar',
	  'Address' => 'Varchar(255)',
		'AddressLine2' => 'Varchar(255)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'State' => 'Varchar(100)',
		'Country' => 'Varchar',
	  'CountryName' => 'Varchar',
	  'Region' => 'Varchar',
	  'RegionName' => 'Varchar'
	);

	/**
	 * Relations for address
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Order' => 'Order',
	  'Member' => 'Customer'
	);
	
	/**
	 * Table type needs to be InnoDB for transaction support (not currently implemented).
	 * 
	 * @var Array
	 */
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Return data in an Array with keys formatted to match the field names
	 * on the checkout form so that it can be loaded into an order form.
	 * 
	 * @see Form::loadDataFrom()
	 * @return Array Data for loading into the form
	 */
	function getCheckoutFormData($prefix = 'Billing') {
	  $formattedData = array();
	  
	  $formattedData[$prefix . "[FirstName]"] = $this->FirstName;
	  $formattedData[$prefix . "[Surname]"] = $this->Surname;
	  $formattedData[$prefix . "[Company]"] = $this->Company;
	  $formattedData[$prefix . "[Address]"] = $this->Address;
	  $formattedData[$prefix . "[AddressLine2]"] = $this->AddressLine2;
	  $formattedData[$prefix . "[City]"] = $this->City;
	  $formattedData[$prefix . "[PostalCode]"] = $this->PostalCode;
	  $formattedData[$prefix . "[State]"] = $this->State;
	  $formattedData[$prefix . "[Country]"] = $this->Country;
	  
	  return $formattedData;
	}
	
	/**
	 * By default an order is always valid. Empty orders are often created and saved
	 * in the DB to represent a cart, so cannot validate that Items and Addresses 
	 * exist for the order until the checkout process.
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}

}
