<?php

namespace SwipeStripe\Core\Admin;

use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SwipeStripe\Core\Admin\GridFieldDetailForm_HasManyItemRequest;

/**
 * Grid field basic configuration
 *
 * @todo Review the configs
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class GridFieldConfig_Basic extends GridFieldConfig
{
    /**
     * Constructor
     *
     * @param Int $itemsPerPage How many items on each page to display
     */
    public function __construct($itemsPerPage = null)
    {
        $this->addComponent(new GridFieldButtonRow('before'));
        $this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
        $this->addComponent(new GridFieldToolbarHeader());
        $this->addComponent($sort = new GridFieldSortableHeader());
        $this->addComponent($filter = new GridFieldFilterHeader());
        $this->addComponent(new GridFieldDataColumns());
        $this->addComponent(new GridFieldEditButton());
        $this->addComponent(new GridFieldDeleteAction());
        $this->addComponent(new GridFieldPageCount('toolbar-header-right'));
        $this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
        $this->addComponent(new GridFieldDetailForm());

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);
        $pagination->setThrowExceptionOnBadDataType(false);
    }
}
