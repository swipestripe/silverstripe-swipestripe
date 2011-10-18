<?php

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
	 */
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '' || $this->getValue() == 0;
	}
}

