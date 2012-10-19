<?php
/**
 * An option for an {@link Item} in the {@link Order}. Items can have many ItemOptions.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class ItemOption extends DataObject {

  /**
   * DB fields for this class
   * 
   * @var Array
   */
	public static $db = array(
	  'Description' => 'Varchar',
	  'Price' => 'Decimal(19,4)',
    'Currency' => 'Varchar(3)',
	);

	public function Amount() {

		// TODO: Multi currency

    $amount = new Price();
		$amount->setCurrency($this->Currency);
    $amount->setAmount($this->Price);
    $amount->setSymbol(ShopConfig::current_shop_config()->BaseCurrencySymbol);
    return $amount;
  }

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
	  'Item' => 'Item'
	);
}
