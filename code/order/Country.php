<?php
/**
 * Countries for shipping and billing addresses.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Country extends DataObject {
  
  /**
   * Singular name
   * 
   * @var String
   */
  public static $singular_name = 'Country';
  
  /**
   * Plural name
   * 
   * @var String
   */
  public static $plural_name = 'Countries';
  
  /** 
	 * ISO 3166 Country Codes, used to generate inital billing countries
	 * 
	 * @see Country_Billing::requireDefaultRecords()
	 * @var Array
	 */
	protected static $iso_3166_countryCodes = array(
		'AD' => "Andorra",
		'AE' => "United Arab Emirates",
		'AF' => "Afghanistan",
		'AG' => "Antigua and Barbuda",
		'AI' => "Anguilla",
		'AL' => "Albania",
		'AM' => "Armenia",
		'AN' => "Netherlands Antilles",
		'AO' => "Angola",
		'AP' => "Asia/Pacific Region",
		'AQ' => "Antarctica",
		'AR' => "Argentina",
		'AS' => "American Samoa",
		'AT' => "Austria",
		'AU' => "Australia",
		'AW' => "Aruba",
		'AZ' => "Azerbaijan",
		'BA' => "Bosnia and Herzegovina",
		'BB' => "Barbados",
		'BD' => "Bangladesh",
		'BE' => "Belgium",
		'BF' => "Burkina Faso",
		'BG' => "Bulgaria",
		'BH' => "Bahrain",
		'BI' => "Burundi",
		'BJ' => "Benin",
		'BM' => "Bermuda",
		'BN' => "Brunei Darussalam",
		'BO' => "Bolivia",
		'BR' => "Brazil",
		'BS' => "Bahamas",
		'BT' => "Bhutan",
		'BV' => "Bouvet Island",
		'BW' => "Botswana",
		'BY' => "Belarus",
		'BZ' => "Belize",
		'CA' => "Canada",
		'CC' => "Cocos (Keeling) Islands",
		'CF' => "Central African Republic",
		'CG' => "Congo",
		'CH' => "Switzerland",
		'CI' => "Cote D'Ivoire",
		'CK' => "Cook Islands",
		'CL' => "Chile",
		'CM' => "Cameroon",
		'CN' => "China",
		'CO' => "Colombia",
		'CR' => "Costa Rica",
		'CU' => "Cuba",
		'CV' => "Cape Verde",
		'CX' => "Christmas Island",
		'CY' => "Cyprus",
		'CZ' => "Czech Republic",
		'DE' => "Germany",
		'DJ' => "Djibouti",
		'DK' => "Denmark",
		'DM' => "Dominica",
		'DO' => "Dominican Republic",
		'DZ' => "Algeria",
		'EC' => "Ecuador",
		'EE' => "Estonia",
		'EG' => "Egypt",
		'EH' => "Western Sahara",
		'ER' => "Eritrea",
		'ES' => "Spain",
		'ET' => "Ethiopia",
		'EU' => "Europe",
		'FI' => "Finland",
		'FJ' => "Fiji",
		'FK' => "Falkland Islands (Malvinas)",
		'FM' => "Micronesia - Federated States of",
		'FO' => "Faroe Islands",
		'FR' => "France",
		'FX' => "France (Metropolitan)",
		'GA' => "Gabon",
		'GB' => "United Kingdom",
		'GD' => "Grenada",
		'GE' => "Georgia",
		'GF' => "French Guiana",
		'GH' => "Ghana",
		'GI' => "Gibraltar",
		'GL' => "Greenland",
		'GM' => "Gambia",
		'GN' => "Guinea",
		'GP' => "Guadeloupe",
		'GQ' => "Equatorial Guinea",
		'GR' => "Greece",
		'GS' => "South Georgia and the South Sandwich Islands",
		'GT' => "Guatemala",
		'GU' => "Guam",
		'GW' => "Guinea-Bissau",
		'GY' => "Guyana",
		'HK' => "Hong Kong",
		'HM' => "Heard Island and McDonald Islands",
		'HN' => "Honduras",
		'HR' => "Croatia",
		'HT' => "Haiti",
		'HU' => "Hungary",
		'ID' => "Indonesia",
		'IE' => "Ireland",
		'IL' => "Israel",
		'IN' => "India",
		'IO' => "British Indian Ocean Territory",
		'IQ' => "Iraq",
		'IR' => "Iran - Islamic Republic of",
		'IS' => "Iceland",
		'IT' => "Italy",
		'JM' => "Jamaica",
		'JO' => "Jordan",
		'JP' => "Japan",
		'KE' => "Kenya",
		'KG' => "Kyrgyzstan",
		'KH' => "Cambodia",
		'KI' => "Kiribati",
		'KM' => "Comoros",
		'KN' => "Saint Kitts and Nevis",
		'KP' => "Korea - Democratic People's Republic of",
		'KR' => "Korea - Republic of",
		'KW' => "Kuwait",
		'KY' => "Cayman Islands",
		'KZ' => "Kazakhstan",
		'LA' => "Lao People's Democratic Republic",
		'LB' => "Lebanon",
		'LC' => "Saint Lucia",
		'LI' => "Liechtenstein",
		'LK' => "Sri Lanka",
		'LR' => "Liberia",
		'LS' => "Lesotho",
		'LT' => "Lithuania",
		'LU' => "Luxembourg",
		'LV' => "Latvia",
		'LY' => "Libyan Arab Jamahiriya",
		'MA' => "Morocco",
		'MC' => "Monaco",
		'MD' => "Moldova - Republic of",
		'MG' => "Madagascar",
		'MH' => "Marshall Islands",
		'MK' => "Macedonia - the Former Yugoslav Republic of",
		'ML' => "Mali",
		'MM' => "Myanmar",
		'MN' => "Mongolia",
		'MO' => "Macao",
		'MP' => "Northern Mariana Islands",
		'MQ' => "Martinique",
		'MR' => "Mauritania",
		'MS' => "Montserrat",
		'MT' => "Malta",
		'MU' => "Mauritius",
		'MV' => "Maldives",
		'MW' => "Malawi",
		'MX' => "Mexico",
		'MY' => "Malaysia",
		'MZ' => "Mozambique",
		'NA' => "Namibia",
		'NC' => "New Caledonia",
		'NE' => "Niger",
		'NF' => "Norfolk Island",
		'NG' => "Nigeria",
		'NI' => "Nicaragua",
		'NL' => "Netherlands",
		'NO' => "Norway",
		'NP' => "Nepal",
		'NR' => "Nauru",
		'NU' => "Niue",
		'NZ' => "New Zealand",
		'OM' => "Oman",
		'PA' => "Panama",
		'PE' => "Peru",
		'PF' => "French Polynesia",
		'PG' => "Papua New Guinea",
		'PH' => "Philippines",
		'PK' => "Pakistan",
		'PL' => "Poland",
		'PM' => "Saint Pierre and Miquelon",
		'PN' => "Pitcairn",
		'PR' => "Puerto Rico",
		'PS' => "Palestinian Territory - Occupied",
		'PT' => "Portugal",
		'PW' => "Palau",
		'PY' => "Paraguay",
		'QA' => "Qatar",
		'RE' => "Reunion",
		'RO' => "Romania",
		'RU' => "Russian Federation",
		'RW' => "Rwanda",
		'SA' => "Saudi Arabia",
		'SB' => "Solomon Islands",
		'SC' => "Seychelles",
		'SD' => "Sudan",
		'SE' => "Sweden",
		'SG' => "Singapore",
		'SH' => "Saint Helena",
		'SI' => "Slovenia",
		'SJ' => "Svalbard and Jan Mayen",
		'SK' => "Slovakia",
		'SL' => "Sierra Leone",
		'SM' => "San Marino",
		'SN' => "Senegal",
		'SO' => "Somalia",
		'SR' => "Suriname",
		'ST' => "Sao Tome and Principe",
		'SV' => "El Salvador",
		'SY' => "Syrian Arab Republic",
		'SZ' => "Swaziland",
		'TC' => "Turks and Caicos Islands",
		'TD' => "Chad",
		'TF' => "French Southern Territories",
		'TG' => "Togo",
		'TH' => "Thailand",
		'TJ' => "Tajikistan",
		'TK' => "Tokelau",
		'TL' => "East Timor",
		'TM' => "Turkmenistan",
		'TN' => "Tunisia",
		'TO' => "Tonga",
		'TR' => "Turkey",
		'TT' => "Trinidad and Tobago",
		'TV' => "Tuvalu",
		'TW' => "Taiwan",
		'TZ' => "Tanzania (United Republic of)",
		'UA' => "Ukraine",
		'UG' => "Uganda",
		'UM' => "United States Minor Outlying Islands",
		'US' => "United States",
		'UY' => "Uruguay",
		'UZ' => "Uzbekistan",
		'VA' => "Holy See (Vatican City State)",
		'VC' => "Saint Vincent and the Grenadines",
		'VE' => "Venezuela",
		'VG' => "Virgin Islands - British",
		'VI' => "Virgin Islands - U.S.",
		'VN' => "Vietnam",
		'VU' => "Vanuatu",
		'WF' => "Wallis and Futuna",
		'WS' => "Samoa",
		'YE' => "Yemen",
		'YT' => "Mayotte",
		'YU' => "Yugoslavia",
		'ZA' => "South Africa",
		'ZM' => "Zambia",
		'ZR' => "Zaire",
		'ZW' => "Zimbabwe"
	);

	/**
	 * Basic fields for country code and title
	 * 
	 * @var Array
	 */
  public static $db = array(
		'Code' => 'Varchar(2)', //ISO 3166 
	  'Title' => 'Varchar'
	);
	
	/**
	 * Associated with SiteConfig to enable editing
	 * 
	 * @var Array
	 */
	public static $has_one = array (
    'ShopConfig' => 'ShopConfig'
  );
  
  /**
   * Countries can have many regions
   * 
   * @var Array
   */
  public static $has_many = array (
    'Regions' => 'Region'
  );
  
  /**
   * Summary fields
   * 
   * @var Array
   */
  public static $summary_fields = array(
    'Title' => 'Title',
    'Code' => 'Code'
  );

  public static $default_sort = 'Title ASC';

	public static function get_codes() {
		return self::$iso_3166_countryCodes;
	}

	//TODO validate that Code is unique for each country
}

/**
 * Shipping country
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Country_Shipping extends Country {

	public function getCMSFields() {

		$fields = new FieldList(
      $rootTab = new TabSet('Root',
        $tabMain = new Tab('Country',
          TextField::create('Code', _t('Country.CODE', 'Code')),
          TextField::create('Title', _t('Country.TITLE', 'Title'))
        )
      )
    );

		if ($this->isInDB()) {

			$config = GridFieldConfig_BasicSortable::create();
			// $detailForm = $config->getComponentByType('GridFieldDetailForm');
			// $detailForm->setItemRequestClass('GridFieldDetailForm_HasManyItemRequest');

			$listField = new GridField(
	      'Regions',
	      'Regions',
	      $this->Regions(),
	      $config
	    );

	    $fields->addFieldToTab('Root.Regions', $listField);
		}

    return $fields;
	}

	public function Regions() {
		return Region_Shipping::get()
			->where("\"CountryID\" = " . $this->ID);
	}
}

/**
 * Billing country
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2012, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Country_Billing extends Country {
  
  /**
   * Build default list of billing countries
   * 
   * @see Country::$iso_3166_countryCodes
   * @see DataObject::requireDefaultRecords()
   */
  public function requireDefaultRecords() {
    
    parent::requireDefaultRecords();

		if (!DataObject::get_one('Country_Billing')) {

			$shopConfig = ShopConfig::current_shop_config();

		  foreach (self::$iso_3166_countryCodes as $code => $title) {
		    $country = new Country_Billing();
		    $country->Code = $code;
		    $country->Title = $title;
		    $country->ShopConfigID = $shopConfig->ID;
		    $country->write();
		  }
			DB::alteration_message('Billing countries created', 'created');
		}
  }

  public function Regions() {
		return Region_Billing::get()
			->where("\"CountryID\" = " . $this->ID);
	}
}

