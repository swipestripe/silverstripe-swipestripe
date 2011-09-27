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

/**
 * Attempt to standardize the modifier classes, not perfect,
 * would rather use an abstract class but any class that extends DataObject
 * gets instantiated when site is built, and trying to instantiate an 
 * abstract class throws an error.
 * 
 * @author frankmullenger
 *
 */
interface Modifier_Interface {
  
  public static function combined_form_fields();
  
  /**
	 * Get the amount for the modifier option
	 * 
	 * @param Int $optionID
	 * @return Money
	 */
  public function Amount($optionID);
  
  /**
	 * Get the description for the modifier option
	 * 
	 * @param Int $optionID
	 * @return String
	 */
  public function Description($optionID);
  
}
