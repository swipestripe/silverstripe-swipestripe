<?php
/**
 * 
 * Shipping abstract class
 * 
 * @author frankmullenger
 *
 */
class Shipping extends OrderModifier {

	public static $db = array(
	);

	public static $has_one = array(
	);
	
	public static $defaults = array(
	);
	
	protected static $supported_methods = array(
		'ItemDiscountShipping' => 'Item Discount'
	);
	
	function getFormFields() {
	  
	}
	
	function getFormRequirements() {
	  
	}
	
	static function combined_form_fields() {
	  
	}
	
	static function combined_form_requirements() {
	  
	}
	
	static function set_supported_methods($methodMap) {
	  self::$supported_methods = $methodMap;
	}

}