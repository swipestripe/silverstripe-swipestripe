<?php
/**
 * Search filter for determining whether an {@link Order} has a {@link Payment} attached.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage search
 * @version 1.0
 */
class PaymentSearchFilter extends SearchFilter {

  /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   */
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		$value = $this->getValue();
		if($value) {
			return $query->innerJoin(
				$table = "Payment", // framework already applies quotes to table names here!
				$onPredicate = "\"Payment\".\"OrderID\" = \"Order\".\"ID\"",
				$tableAlias = null
			);
		}
	}

	/**
	 * Determine whether the filter should be applied, depending on the 
	 * value of the field being passed
	 * 
	 * @see SearchFilter::isEmpty()
	 * @return Boolean
	 */
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '' || $this->getValue() == 0;
	}
}

