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
	public static $db = array(
		'Value' => 'Int',
	  'Price' => 'Decimal(19,4)',
    'Currency' => 'Varchar(3)',
	  'Description' => 'Text',
	  'SubTotalModifier' => 'Boolean',
	  'SortOrder' => 'Int'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
	  'Order' => 'Order'
	);

	public static $default_sort = 'SortOrder ASC';

	public static function get_all() {
		$mods = new ArrayList();

		$classes = ClassInfo::subclassesFor('Modification');
		foreach ($classes as $class) {

			if ($class != 'Modification') $mods->push(new $class());
		}
		$mods->sort('SortOrder');
		return $mods;
	}

	public function Amount() {

		// TODO: Multi currency

		$amount = new Price();
		$amount->setCurrency($this->Currency);
    $amount->setAmount($this->Price);
    $amount->setSymbol(ShopConfig::current_shop_config()->BaseCurrencySymbol);
    return $amount;
  }

	public function add($order, $value = null) {
		return;
	}

	public function getFormFields() {

		$fields = new FieldList();
		return $fields;
	}
	
}
