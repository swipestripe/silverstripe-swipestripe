<?php

class ShopSearchContext_Order extends SearchContext {

	public function getResults($searchParams, $sort = false, $limit = false) {

		$searchParams = array_filter((array)$searchParams, array($this,'clearEmptySearchFields'));

		//Only orders which have been processed are displayed
		$searchParams['HasPayment'] = array(1 => 1);

		// getQuery actually returns a DataList
		return $this->getQuery($searchParams, $sort, $limit);
	}
}