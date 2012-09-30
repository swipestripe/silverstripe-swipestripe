<?php
/**
 * Search filter for {@link Product} categories, filtering search results for 
 * certain {@link ProductCategory}s in the CMS.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage search
 */
class ProductCategorySearchFilter extends SearchFilter {

  /**
   * Apply filter query SQL to a search query
   * 
   * @see SearchFilter::apply()
   * @return SQLQuery
   */
	public function apply(DataQuery $query) {

	  $this->model = $query->applyRelation($this->relation);
	  $value = $this->getValue();

	  if ($value) {

	    $query->innerJoin(
  			'ProductCategory_Products',
  			"\"ProductCategory_Products\".\"ProductID\" = \"SiteTree\".\"ID\""
  		);
  		$query->innerJoin(
  			'SiteTree_Live',
  			"\"SiteTree_Live\".\"ID\" = \"ProductCategory_Products\".\"ProductCategoryID\""
  		);
  		$query->where("\"SiteTree_Live\".\"Title\" LIKE '%" . Convert::raw2sql($value) . "%'");
	  }
	  return $query;
	}

	/**
	 * Determine whether the filter should be applied, depending on the 
	 * value of the field being passed
	 * 
	 * @see SearchFilter::isEmpty()
	 * @return Boolean
	 */
	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
