<?php
class GridFieldConfig_HasManyRelationEditor extends GridFieldConfig {
	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		
		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));
		$this->addComponent(new GridFieldAddExistingAutocompleter('buttons-before-left'));
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