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
				'MobilePhone' => 'Varchar(100)',
				'Notes' => 'HTMLText'
			)
		);
	}

	function updateCMSFields($fields) {
		$fields->removeByName('Country');
		$fields->addFieldToTab('Root.Main', new DropdownField('Country', 'Country', Geoip::getCountryDropDown()));
	}

}
