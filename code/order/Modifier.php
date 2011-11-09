<?php
/**
 * Modifiers for the {@link Order}, e.g: shipping, tax, vouchers etc.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage order
 * @version 1.0
 */
class Modifier extends DataObject {

  /**
   * DB fields for the order modifier, the actual modifier data is saved into
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
	
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Set the currency code that this site uses for modifiers
	 * 
	 * @param string $currency 3 letter ISO 4217 currency code e.g. "NZD"
	 */
	public static function set_currency($currency) {
		self::$currency = $currency;
	}
	
	/**
	 * Return the currency set for modifiers
	 * 
	 * @return string 3 letter ISO 4217 currency code e.g. "NZD"
	 */
	public static function currency() {
		return self::$currency;
	}
	
	/**
	 * By default modifiers are valid
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}
	
	/**
	 * This might return empty if the modifier has been deleted. The modifier
	 * data is saved in Modifier table anyway.
	 */
	function Object() {
	  return DataObject::get_by_id($this->ModifierClass, $this->ModifierOptionID);
	}

}
