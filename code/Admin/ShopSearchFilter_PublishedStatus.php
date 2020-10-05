<?php

namespace SwipeStripe\Core\Admin;

use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\DataQuery;

/**
 * Search filter for {@link Product} status, whether a {@link Product} is published
 * or unpublished.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopSearchFilter_PublishedStatus extends SearchFilter
{
    /**
     * Apply filter query SQL to a search query
     *
     * @see SearchFilter::apply()
     */
    public function apply(DataQuery $query)
    {
        return $query;

        $query = $this->applyRelation($query);
        $value = $this->getValue();

        if ($value) {
            if ($value == 1) {
                return $query->where("Status = 'Published'");
            }
            if ($value == 2) {
                return $query->where("Status != 'Published'");
            }
        }
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
        return $this->getValue() == null || $this->getValue() == '' || $this->getValue() == 0;
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
