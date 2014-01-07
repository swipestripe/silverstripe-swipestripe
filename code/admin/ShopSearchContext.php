<?php
/**
 * Search context for orders.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopSearchContext_Order extends SearchContext {

	public function getResults($searchParams, $sort = false, $limit = false) {

		$searchParams = array_filter((array)$searchParams, array($this,'clearEmptySearchFields'));

		// getQuery actually returns a DataList
		$query = $this->getQuery($searchParams, $sort, $limit);

		//Only orders which have been processed are displayed
		$query = $query->leftJoin(
			$table = 'Payment',
			$onPredicate = "\"Payment\".\"OrderID\" = \"Order\".\"ID\"",
			$tableAlias = 'Payment'
		);
		$query = $query->where('"Payment"."ID" IS NOT NULL');

		return $query;
	}
}