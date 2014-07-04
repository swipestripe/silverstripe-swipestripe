<?php
/**
 * Modification for the {@link Order}, saves data that is set by {@link Modifier}s 
 * e.g: shipping, tax, vouchers etc. Instead of linking to a {@link Modifier} it takes the Amount
 * that the modifier will ammend the {@link Order} total by and the Description of the Modifier
 * and saves that - denormalising the data - so that Modifiers can be deleted without losing
 * any information from the Order.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Modification extends DataObject {

	/**
	 * DB fields for the order Modification, the actual {@link Modifier} data is saved into
	 * this class so if a modifier is deleted the order still has the necessary 
	 * details.
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Value' => 'Int',
		'Price' => 'Decimal(19,8)',
		'Description' => 'Text',
		'SubTotalModifier' => 'Boolean',
		'SortOrder' => 'Int'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	private static $has_one = array(
		'Order' => 'Order'
	);

	private static $default_sort = 'SortOrder ASC';

	public static function get_all() {
		$mods = new ArrayList();
		$temp = array();

		$classes = ClassInfo::subclassesFor('Modification');
		foreach ($classes as $class) {

			if ($class != 'Modification') {
				$mod = new $class();
				$temp[$mod->SortOrder] = $mod;
			}
		}
		
		//Sorting the modifications so they are applied in correct order
		ksort($temp);	

		foreach ($temp as $mod) {
			$mods->push($mod);
		}
		return $mods;
	}

	public function Amount() {

		// TODO: Multi currency
		$order = $this->Order();

		$amount = Price::create();
		$amount->setAmount($this->Price);
		$amount->setCurrency($order->BaseCurrency);
		$amount->setSymbol($order->BaseCurrencySymbol);
		return $amount;
	}

	/**
	 * Display price, can decorate for multiple currency etc.
	 * 
	 * @return Price
	 */
	public function Price() {
		
		$amount = $this->Amount();
		$this->extend('updatePrice', $amount);
		return $amount;
	}

	public function add($order, $value = null) {
		return;
	}

	public function getFormFields() {
		return new FieldList();
	}
	
}
