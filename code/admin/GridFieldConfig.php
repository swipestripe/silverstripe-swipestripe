<?php
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
class GridFieldConfig_Basic extends GridFieldConfig {

	/**
	 * Constructor
	 * 
	 * @param Int $itemsPerPage How many items on each page to display
	 */
	public function __construct($itemsPerPage=null) {
		
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

/**
 * Grid field basic sortable configuration
 *
 * @todo Review the configs
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class GridFieldConfig_BasicSortable extends GridFieldConfig {

	/**
	 * Constructor
	 * 
	 * @param Int $itemsPerPage How many items on each page to display
	 */
	public function __construct($itemsPerPage = null) {
		
		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent(new GridFieldDetailForm());

		if (class_exists('GridFieldSortableRows')) {
			$this->addComponent(new GridFieldSortableRows('SortOrder'));
		}

		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));
		$this->addComponent(new GridFieldPageCount('toolbar-header-right'));
		$pagination->setThrowExceptionOnBadDataType(false);

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		
	}
}

/**
 * Grid field basic has many configuration
 *
 * @todo Review the configs
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class GridFieldConfig_HasManyRelationEditor extends GridFieldConfig {

	/**
	 * Constructor
	 * 
	 * @param Int $itemsPerPage How many items on each page to display
	 */
	public function __construct($itemsPerPage=null) {
		
		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());
		$this->addComponent(new GridFieldDataColumns());
		$this->addComponent(new GridFieldEditButton());
		$this->addComponent(new GridFieldDeleteAction());
		$this->addComponent($pagination = new GridFieldPaginator($itemsPerPage));

		$detailForm = new GridFieldDetailForm();
		$detailForm->setItemRequestClass('GridFieldDetailForm_HasManyItemRequest');
		$this->addComponent($detailForm);

		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);
	}
}
