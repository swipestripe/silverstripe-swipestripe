<?php
/**
 * 
 * Modifiers for the order, e.g: shipping, tax, vouchers etc.
 * 
 * @author frankmullenger
 *
 */
class OrderModifier extends DataObject {

	public static $db = array(
	  'ModifierClass' => 'Varchar',
		'ModifierOptionID' => 'Int',
	  'Amount' => 'Money',
	  'Description' => 'Text'
	);

	public static $has_one = array(
	  'Order' => 'Order'
	);
	
	public static $defaults = array(
	);
	
	protected static $currency = 'NZD';
	
	/**
	 * Set the currency code that this site uses.
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

}