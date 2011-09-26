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
	  'Amount' => 'Money'
	);

	public static $has_one = array(
	  'Order' => 'Order'
	);
	
	public static $defaults = array(
	);

}