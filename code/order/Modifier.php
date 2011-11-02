<?php
/**
 * 
 * Modifiers for the order, e.g: shipping, tax, vouchers etc.
 * 
 * @author frankmullenger
 *
 */
class Modifier extends DataObject {

	public static $db = array(
	  'ModifierClass' => 'Varchar',
		'ModifierOptionID' => 'Int',
	  'Amount' => 'Money',
	  'Description' => 'Text'
	);

	public static $has_one = array(
	  'Order' => 'Order'
	);
	
	protected static $currency = 'NZD';
	
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Set the currency code that this site uses.
	 * 
	 * TODO pick up currency from a central location
	 * 
	 * @param string $currency Currency code. e.g. "NZD"
	 */
	public static function set_currency($currency) {
		self::$currency = $currency;
	}
	
	/**
	 * Return the site currency in use.
	 * @return string
	 */
	public static function currency() {
		return self::$currency;
	}
	
	/**
	 * TODO validate before write()
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}
	
	/**
	 * This might return empty if the modifier has been deleted
	 */
	function Object() {
	  return DataObject::get_by_id($this->ModifierClass, $this->ModifierOptionID);
	}

}
