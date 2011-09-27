<?php
/**
 * 
 * Flat fee shipping
 * 
 * @author frankmullenger
 *
 */
class FlatFeeShipping extends Shipping {

	public static $db = array(
	);

	public static $has_one = array(
	);
	
	public static $defaults = array(
	);
	
  function getFormFields() {
	  $fields = new FieldSet();
	  
	  $fields->push(new LiteralField('Flat Fee', 'Shipping costs $5.00'));
	  
	  $fields->push(new OptionsetField(
	  	'Shipping[FlatFee]', 
	  	'Flat Fee Shipping',
	  	array(
	  	  '5' => 'Flat Fee Shipping'
	  	),
	  	5
	  ));
	  
	  return $fields;
	}

}