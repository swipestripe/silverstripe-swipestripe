<?php

class OptionSetSearchFilter extends SearchFilter {

  /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   */
	public function apply(SQLQuery $query) {
		$query = $this->applyRelation($query);
		$values = $this->getValue();
		if(count($values)) {
			foreach($values as $value) {
				$matches[] = sprintf("%s LIKE '%s%%'",
					$this->getDbName(),
					Convert::raw2sql(str_replace("'", '', $value))
				);
			}

			return $query->where(implode(" OR ", $matches));
		}
		return $query;
	}

	/**
	 * Determine whether the filter should be applied, depending on the 
	 * value of the field being passed
	 * 
	 * @see SearchFilter::isEmpty()
	 */
	public function isEmpty() {
		if(is_array($this->getValue())) {
			return count($this->getValue()) == 0;
		}
		else {
			return $this->getValue() == null || $this->getValue() == '';
		}
	}
}
