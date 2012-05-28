<?php
/**
 * Search context for date ranges, used in the CMS for searching {@link Order}s.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage search
 */
class DateRangeSearchContext extends SearchContext {

  /**
   * Replace the default form fields for the 'Created' search 
   * field with a single text field which we can use to apply 
   * jquery date range widget to.
   * 
   * @see Order::$searchable_fields
   * @see Order::getDefaultSearchContext()
   * @see SearchContext::getSearchFields()
   * @return FieldSet
   */
	public function getSearchFields() {
	  
		$fields = ($this->fields) ? $this->fields : singleton($this->modelClass)->scaffoldSearchFields();
		if($fields) {

			$dates = array ();
			foreach($fields as $f) {
				$type = singleton($this->modelClass)->obj($f->Name())->class;
				if($type == "Date" || $type == "SS_Datetime") {
					$dates[] = $f;
				}
			}

			foreach($dates as $d) {
				$fields->removeByName($d->Name());
				$fields->push(new TextField($d->Name(), 'Date Range'));
			}
		}
		return $fields;
	}
	
	/**
	 * Alter the existing SQL query object by adding some filters for the search
	 * so that the query finds Orders between two dates min and max.
	 * 
	 * @see SearchContext::getQuery()
	 * @return SQLQuery Query with filters applied for search
	 */
	public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null) {

		$query = parent::getQuery($searchParams, $sort, $limit, $existingQuery);
		$searchParamArray = (is_object($searchParams)) ?$searchParams->getVars() :$searchParams;

 		foreach($searchParamArray as $key => $value) {

 		  if ($key == 'OrderedOn') {
 		    
 		    $filter = $this->getFilter($key);
 		    if ($filter && get_class($filter) == "DateRangeSearchFilter") {

	        $filter->setModel($this->modelClass);

	        preg_match('/([^\s]*)(\s-\s(.*))?/i', $value, $matches);
					$min_val = (isset($matches[1])) ?$matches[1] :null;
					
					$max_val = null;
					if (isset($matches[3])) {
					  $max_val = date('Y-m-d', strtotime("+1 day",strtotime($matches[3])));
					}
					elseif (isset($min_val)) {
					  $max_val = date('Y-m-d', strtotime("+1 day",strtotime($min_val)));
					}

					if($min_val && $max_val) {
						$filter->setMin($min_val);
						$filter->setMax($max_val);
						$filter->apply($query);
					}
 		    }
 		  }
		}
		return $query;
	}

}