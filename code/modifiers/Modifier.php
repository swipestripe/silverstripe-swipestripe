<?php
/**
 * A modifier is something that changes the amount of an order via a modification
 * 
 * @author frankmullenger
 *
 */
class Modifier extends DataObject {
	
	public static $supported_methods = array(
		//'FlatFeeShipping',
		//'PerItemShipping'
	);
	
	/**
	 * Get al the fields from all the shipping modules that are enabled
	 * 
	 * @param Order $order
	 */
	static function combined_form_fields($order) {
	  $fields = new FieldSet();
	  
	  foreach (self::$supported_methods as $modifierClassName) {
	    
	    $modifier = new $modifierClassName();
	    $modifierFields = $modifier->getFormFields($order);
	    
	    if ($modifierFields && $modifierFields->exists()) foreach ($modifierFields as $field) {
	      $fields->push($field);
	    } 
	  }
	  return $fields;
	}
	
  static function combined_form_requirements($order) {
	  //TODO is this really needed? all modifier fields go into Validation as required to perform validation on
	}
	
}

interface Modifier_Interface {

  public function getFormFields($order);
  public function getFormRequirements($order);
  public function Amount($order, $value);
  public function Description($order, $value);
}



