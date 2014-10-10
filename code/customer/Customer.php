<?php

class Customer_Extension extends DataExtension {

	private static $has_many = array(
		'Orders' => 'Order'
	);

	/**
	 * Prevent customers from being deleted.
	 * 
	 * @see Member::canDelete()
	 */
	public function canDelete($member = null) {

		$orders = $this->Orders();
		if ($orders && $orders->exists()) {
			return false;
		}
		return Permission::check('ADMIN', 'any', $member);
	}

	/**
	 * Override getter to return only non-cart orders
	 * TODO issue with this function not called in the object being extended, needs testing and possibly different approach
	 * 
	 * @return ArrayList Set of previous orders for this member
	 */
	public function Orders() {
		return Order::get()
			->where("\"MemberID\" = " . $this->owner->ID . " AND \"Order\".\"Status\" != 'Cart'")
			->sort("\"Created\" DESC");
	}
}
