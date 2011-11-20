<?php
/**
 * Represents the type of object that injects its details into an {@Link Order}
 * {@link Modification}. Has a set interface so that all necessary details by 
 * {@link Modification} are available. 
 * 
 * Modifiers can be deleted so the pertinant details such as the Amount that they 
 * alter the {@link Order} by and the Description of what they are for, are both
 * saved to the {@link Modification} - denormalised in a sense.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage modifiers
 * @version 1.0
 */
class Modifier extends DataObject {
	
  /**
   * Methods supported, array of class names of classes that extend Modifier
   * and have been enabled, e.g: FlatFeeShipping
   * 
   * @var Array
   */
	public static $supported_methods = array(
	);
	
	/**
	 * Get all the form fields from all the supported methods.
	 * 
	 * @see Modifier::$supported_methods
	 * @param Order $order
	 * @return FieldSet
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
	
	/**
	 * Get all the form requirements from all the supported methods.
	 * 
	 * //TODO is this really needed? all modifier fields go into Validation as required to perform validation on
	 * 
	 * @see Modifier::$supported_methods
	 * @param unknown_type $order
	 * @return FieldSet
	 */
  static function combined_form_requirements($order) {
	  return new FieldSet();
	}
	
}

/**
 * Interface for Modifier classes to ensure they deliver the minimum details required by {@link Modification}s,
 * namely: Amount() and Description().
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage modifiers
 * @version 1.0
 */
interface Modifier_Interface {

  /**
   * Get form fields for this modifier.
   * 
   * @see Modifier::combined_form_fields()
   * @param Order $order
   * @return FieldSet
   */
  public function getFormFields($order);
  
  /**
   * Get form requirements for this modifier.
   * 
   * @see Modifier::combined_form_fields()
   * @param Order $order
   * @return FieldSet
   */
  public function getFormRequirements($order);
  
  /**
   * Get Amount for this modifier so that it can be saved into an {@link Order} {@link Modification}.
   * 
   * @see Modification
   * @param Order $order
   * @param Mixed $value A value passed from the Checkout form POST data usually
   * @return Money
   */
  public function Amount($order, $value);
  
  /**
   * Get Description for this modifier so that it can be saved into an {@link Order} {@link Modification}.
   * 
   * @see Modification
   * @param Order $order
   * @param Mixed $value A value passed from the Checkout form POST data usually
   * @return String
   */
  public function Description($order, $value);
}
