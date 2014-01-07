<?php
/**
 * Grid field detail form
 *
 * @todo Review the configs
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class GridFieldDetailForm_HasManyItemRequest extends GridFieldDetailForm_ItemRequest {
	
	private static $allowed_actions = array(
		'edit',
		'view',
		'ItemEditForm'
	);

	/**
	 * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
	 * popupFormName, however this is an experimental API and may change.
	 * 
	 * @todo In the future, we will probably need to come up with a tigher object representing a partially
	 * complete controller with gaps for extra functionality.  This, for example, would be a better way
	 * of letting Security/login put its log-in form inside a UI specified elsewhere.
	 * 
	 * @return Form 
	 */
	function ItemEditForm() {
		if (empty($this->record)) {
			$controller = Controller::curr();
			$noActionURL = $controller->removeAction($_REQUEST['url']);
			$controller->getResponse()->removeHeader('Location');   //clear the existing redirect
			return $controller->redirect($noActionURL, 302);
		}

		$actions = new FieldList();
		if($this->record->ID !== 0) {
			$actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Save', 'Save'))
				->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept'));
			$actions->push(FormAction::create('doDelete', _t('GridFieldDetailForm.Delete', 'Delete'))
				->addExtraClass('ss-ui-action-destructive'));
		}else{ // adding new record
			//Change the Save label to 'Create'
			$actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Create', 'Create'))
				->setUseButtonTag(true)->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'add'));
				
			// Add a Cancel link which is a button-like link and link back to one level up.
			$curmbs = $this->Breadcrumbs();
			if($curmbs && $curmbs->count()>=2){
				$one_level_up = $curmbs->offsetGet($curmbs->count()-2);
				$cancelText = _t('GridFieldDetailForm.CancelBtn', 'Cancel');
				$text = "
				<a class=\"crumb ss-ui-button ss-ui-action-destructive cms-panel-link ui-corner-all\" href=\"".$one_level_up->Link."\">
					$cancelText
				</a>";
				$actions->push(new LiteralField('cancelbutton', $text));
			}
		}
		
		$fk = $this->gridField->getList()->foreignKey;
		$this->record->$fk = $this->gridField->getList()->foreignID;

		$form = new Form(
			$this,
			'ItemEditForm',
			$this->record->getCMSFields(),
			$actions,
			$this->component->getValidator()
		);
		$form->loadDataFrom($this->record);

		// TODO Coupling with CMS
		$toplevelController = $this->getToplevelController();
		if($toplevelController && $toplevelController instanceof LeftAndMain) {
			// Always show with base template (full width, no other panels), 
			// regardless of overloaded CMS controller templates.
			// TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
			$form->setTemplate('LeftAndMain_EditForm');
			$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
			$form->setAttribute('data-pjax-fragment', 'CurrentForm Content');
			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if($toplevelController->hasMethod('Backlink')) {
				$form->Backlink = $toplevelController->Backlink();
			} elseif($this->popupController->hasMethod('Breadcrumbs')) {
				$parents = $this->popupController->Breadcrumbs(false)->items;
				$form->Backlink = array_pop($parents)->Link;
			} else {
				$form->Backlink = $toplevelController->Link();
			}
		}

		$cb = $this->component->getItemEditFormCallback();
		if($cb) $cb($form, $this);

		return $form;
	}
}
