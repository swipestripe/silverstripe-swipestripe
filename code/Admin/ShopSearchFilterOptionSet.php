<?php

namespace SwipeStripe\Core\Admin;

use SilverStripe\ORM\DataQuery;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\Filters\SearchFilter;

/**
 * Search filter for option sets, used for searching {@link Order} statuses in the CMS.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopSearchFilterOptionSet extends SearchFilter
{
    /**
     * Apply filter query SQL to a search query
     *
     * @see SearchFilter::apply()
     * @return SQLQuery
     */
    public function apply(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $values = $this->getValue();

        if (count($values)) {
            foreach ($values as $value) {
                $matches[] = sprintf(
                    "%s LIKE '%s%%'",
                    $this->getDbName(),
                    Convert::raw2sql(str_replace("'", '', $value))
                );
            }

            return $query->where(implode(' OR ', $matches));
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
    public function isEmpty()
    {
        if (is_array($this->getValue())) {
            return count($this->getValue()) == 0;
        } else {
            return $this->getValue() == null || $this->getValue() == '';
        }
    }

    protected function applyOne(DataQuery $query)
    {
        return;
    }

    protected function excludeOne(DataQuery $query)
    {
        return;
    }
}
