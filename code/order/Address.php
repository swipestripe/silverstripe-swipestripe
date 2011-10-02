<?php

class Address extends DataObject {

	public static $db = array(
	  'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
	  'Company' => 'Varchar',
	  'Type' => "Enum('Billing,Shipping','Billing')",
	  'Address' => 'Varchar(255)',
		'AddressLine2' => 'Varchar(255)',
		'City' => 'Varchar(100)',
		'PostalCode' => 'Varchar(30)',
		'State' => 'Varchar(100)',
		'Country' => 'Varchar',
	);

	public static $has_one = array(
		'Order' => 'Order',
	  'Member' => 'Member'
	);

}
