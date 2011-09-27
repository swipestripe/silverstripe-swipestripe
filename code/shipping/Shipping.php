<?php
/**
 * 
 * Shipping abstract class
 * 
 * @author frankmullenger
 *
 */
class Shipping extends DataObject {

	public static $db = array(
	);

	public static $has_one = array(
	);
	
	public static $defaults = array(
	);
	
	protected static $supported_methods = array(
		'FlatFeeShipping' => 'Flat Fee Shipping'
	);
	
	function getFormFields() {
	  user_error("Please implement getFormFields() on $this->class", E_USER_ERROR);
	}
	
	function getFormRequirements() {
	  user_error("Please implement getFormRequirements() on $this->class", E_USER_ERROR);
	}
	
	static function set_supported_methods($methodMap) {
	  self::$supported_methods = $methodMap;
	}
	
	static function combined_form_fields() {
	  
	  //Get all the fields from all the shipping modules that are enabled in order
	  
	  $fields = new FieldSet();
	  
	  $fields->push(new LiteralField('Flat Fee', 'Shipping costs $5.00'));
	  
	  $fields->push(new OptionsetField(
	  	'OrderModifiers[FlatFeeShipping]', 
	  	'Flat Fee Shipping',
	  	array(
	  	  1 => 'Flat Fee Shipping $5',
	  	  2 => 'Some other shipping that is $5',
	  	  3 => 'Air shipping $10.95'
	  	),
	  	1
	  ));
	  
	  return $fields;
	}
	
	/**
	 * 
	 * 
	 * @param Int $optionID
	 * @return Money
	 */
	static function calculate_amount($optionID) {
	  
	  $amount = new Money();
	  
	  $currency = OrderModifier::currency();
	  $amount->setCurrency($currency);
	  
	  $shippingCosts = array(
	    1 => '5.00',
	    2 => '5.00',
	    3 => '10.95'
	  );
	  $amount->setAmount($shippingCosts[$optionID]);
	  
	  return $amount;
	}
	
	/**
	 * 
	 * 
	 * @param Int $optionID
	 * @return String
	 */
	static function description($optionID) {

	  $shippingDescriptions = array(
	    1 => 'Flat Fee Shipping',
	    2 => 'Some Other Shipping',
	    3 => 'Air Shipping'
	  );
	  return $shippingDescriptions[$optionID];
	}

}