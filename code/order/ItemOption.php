<?php
/**
 * An option for an {@link Item} in the {@link Order}. Items can have many ItemOptions.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 * @version 1.0
 */
class ItemOption extends DataObject {

  /**
   * DB fields for this class
   * 
   * @var Array
   */
	public static $db = array(
	  'ObjectID' => 'Int',
	  'ObjectClass' => 'Varchar',
	  'ObjectVersion' => 'Int',
	  'Amount' => 'Money'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
	  'Item' => 'Item'
	);
	
	/**
	 * Set table type to InnoDB to support transactions which are not currently implemented.
	 * 
	 * @var Array
	 */
	static $create_table_options = array(
		'MySQLDatabase' => 'ENGINE=InnoDB'
	);
	
	/**
	 * Retrieve the object this item represents, usually a {@link Variation}.
	 * Uses the object version so that the correct object details such as price are
	 * retrieved.
	 * 
	 * @return DataObject 
	 */
	function Object() {
	  return Versioned::get_version($this->ObjectClass, $this->ObjectID, $this->ObjectVersion);
	}
	
	/**
	 * By default all ItemOptions are valid.
	 * 
	 * @see DataObject::validate()
	 */
	function validate() {
	  return parent::validate();
	}

  public function onAfterWrite() {
    
    //Update stock levels if a variation is being saved here
    parent::onAfterWrite();
    $item = $this->Item();
    $variation = $this->Object();
	  if ($variation && $variation->exists() && $variation instanceof Variation) {
	    $item->updateStockLevels();
	  }
	}

}
