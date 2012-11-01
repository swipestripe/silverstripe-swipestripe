<?php
/**
 * An Item for an {@link Order}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage order
 */
class Status extends DataObject {

	public static $db = array(
		'Status' => "Enum('Pending,Processing,Dispatched,Cancelled')",
	  'Note' => 'Text'
	);

	/**
	 * Relations for this class
	 * 
	 * @var Array
	 */
	public static $has_one = array(
		'Order' => 'Order',
		'Member' => 'Member'
	);

	public function canEdit($member = null) {
    return false;
	}

	public function canDelete($member = null) {
    return false;
	}

  /**
	 * Clean up Order Items (ItemOptions by extension), Addresses and Modifications.
	 * All wrapped in a transaction.
	 */
	public function delete() {
	  if ($this->canDelete(Member::currentUser())) {
      parent::delete();
    }
	}

  /**
   * Update stock levels for {@link Item}.
   * 
   * @see DataObject::onAfterWrite()
   */
	public function onAfterWrite() {
	  parent::onAfterWrite();
	  
	  //Update the Order, setting the same status
	}

}