<?php

class ProductCategorySearchFilter extends SearchFilter {

  /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   */
	public function apply(SQLQuery $query) {
	  
	  $query = $this->applyRelation($query);
	  $values = $this->getValue();

		if (count($values)) {
		  
		  $query->innerJoin(
  			$table = 'ProductCategory_Products', // framework already applies quotes to table names here!
  			$onPredicate = "`ProductCategory_Products`.`ProductID` = `Product`.`ID`",
  			$tableAlias = null
  		);
		  
			foreach ($values as $value) {
				$matches[] = "`ProductCategory_Products`.`ProductCategoryID` LIKE " . Convert::raw2sql($value);
			}
			$query->where(implode(" OR ", $matches));
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
