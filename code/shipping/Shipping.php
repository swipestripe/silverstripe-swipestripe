<?php
/**
 * 
 * Shipping abstract class
 * 
 * @author frankmullenger
 *
 */
class Shipping extends DataObject {
	
	public static $supported_methods = array(
		//'FlatFeeShipping',
		//'PerItemShipping'
	);

	static function set_supported_methods($methodMap) {
	  
	  if (is_array($methodMap)) foreach ($methodMap as $className => $desc) {
	    if (!class_exists($className)) user_error("Tried to set a shipping method that does not exist.", E_USER_ERROR);
	  }
	  
	  self::$supported_methods = $methodMap;
	}
	
	static function get_product_dependencies() {
	  
	  $dependencies = array();
	  foreach (self::$supported_methods as $dependentFields) {
	    if (is_array($dependentFields)) foreach ($dependentFields as $fieldName) {
	      $dependencies[] = $fieldName;
	    }
	  }
	  return $dependencies;
	}
	
	static function combined_form_fields($order) {
	  
	  //Get all the fields from all the shipping modules that are enabled in order
	  $fields = new FieldSet();
	  $fields->push(new HeaderField('Shipping'));
	  
	  foreach (self::$supported_methods as $className) {
	    
	    $method = new $className();
	    $methodFields = $method->getFormFields($order);
	    
	    if ($methodFields && $methodFields->exists()) foreach ($methodFields as $field) {
	      $fields->push($field);
	    } 
	  }
	  
	  //Remove the heading if no other fields added
	  if ($fields->Count() == 1) $fields = new FieldSet();
	  
	  return $fields;
	}
	
  function getFormFields($order) {
	  user_error("Please implement getFormFields() on $this->class", E_USER_ERROR);
	}
	
	function getFormRequirements() {
	  user_error("Please implement getFormRequirements() on $this->class", E_USER_ERROR);
	}
	
	function Amount($optionID, $order) {
	  return;
	}
	
	function Description($optionID) {
    return;
	}

}