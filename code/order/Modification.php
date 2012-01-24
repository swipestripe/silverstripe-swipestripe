<?php
/**
 * Modification for the {@link Order}, saves data that is set by {@link Modifier}s 
 * e.g: shipping, tax, vouchers etc. Instead of linking to a {@link Modifier} it takes the Amount
 * that the modifier will ammend the {@link Order} total by and the Description of the Modifier
 * and saves that - denormalising the data - so that Modifiers can be deleted without losing
 * any information from the Order.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 * @version 1.0
 */
class Modification extends DataObject {

  /**
   * DB fields for the order Modification, the actual {@link Modifier} data is saved into
   * this class so if a modifier is deleted the order still has the necessary 
   * details.
   * 
   * @var Array
   */
	public static $db = array(
	  'ModifierClass' => 'Varchar',
		'ModifierOptionID' => 'Int', 
	  'Amount' => 'Money',
	  'Description' => 'Text'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
	  'Order' => 'Order'
	);
	
	/**
	 * Modifier currency
	 * TODO move currency to one central location
	 * 
	 * @var String 3 letter ISO 4217 currency code e.g. "NZD"
	 */
	protected static $currency = 'NZD';
	
	/**
	 * Set table to InnoDB in preparation for transaction support.
	 * 
	 * @var Array
	 */
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Set the currency code that this site uses for Order Modifications
	 * 
	 * @param string $currency 3 letter ISO 4217 currency code e.g. "NZD"
	 */
	public static function set_currency($currency) {
		self::$currency = $currency;
	}
	
	/**
	 * Return the currency set for Order Modifications
	 * 
	 * @return string 3 letter ISO 4217 currency code e.g. "NZD"
	 */
	public static function currency() {
		return self::$currency;
	}
	
	/**
	 * By default Modifications are valid
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}

}
