<?php
/**
 * Search filter for {@link Product} categories, filtering search results for 
 * certain {@link ProductCategory}s in the CMS.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage search
 * @version 1.0
 */
class ProductCategorySearchFilter extends SearchFilter {

  /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   * @return SQLQuery
   */
	public function apply(SQLQuery $query) {
	  
	  $query = $this->applyRelation($query);
	  $value = $this->getValue();

	  if ($value) {
	    
	    $query->innerJoin(
  			$table = 'ProductCategory_Products', // framework already applies quotes to table names here!
  			$onPredicate = "\"ProductCategory_Products\".\"ProductID\" = \"Product\".\"ID\"",
  			$tableAlias = null
  		);
  		$query->where("\"ProductCategory_Products\".\"ProductCategoryID\" = " . Convert::raw2sql($value));
	  }
	  return $query;
	  
	  /*
	  $values = $this->getValue();

		if (count($values)) {
		  
		  $query->innerJoin(
  			$table = 'ProductCategory_Products', // framework already applies quotes to table names here!
  			$onPredicate = "\"ProductCategory_Products\".\"ProductID\" = \"Product\".\"ID\"",
  			$tableAlias = null
  		);
		  
			foreach ($values as $value) {
				$matches[] = "\"ProductCategory_Products\".\"ProductCategoryID\" LIKE " . Convert::raw2sql($value);
			}
			$query->where(implode(" OR ", $matches));
		}
		return $query;
		*/
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
	  
	  /*
		if(is_array($this->getValue())) {
			return count($this->getValue()) == 0;
		}
		else {
			return $this->getValue() == null || $this->getValue() == '';
		}
		*/
	}
}
