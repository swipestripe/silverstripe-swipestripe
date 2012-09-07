<?php

class ShopAdmin extends ModelAdmin {

	static $url_segment = 'shop';

	static $menu_title = 'Shop';

	public $showImportForm = false;

	// static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	// static $session_namespace = 'CMSMain';

	public static $managed_models = array(
		'Product'
	);

	function getEditForm($id = null, $fields = null) {

		$list = $this->getList();

		$exportButton = new GridFieldExportButton('before');
		$exportButton->setExportColumns($this->getExportFields());

		$detailForm = new GridFieldDetailForm();
		$detailForm->setItemRequestClass('ShopAdmin_ItemRequest');

		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->addComponent($exportButton)
				->removeComponentsByType('GridFieldFilterHeader')
				->removeComponentsByType('GridFieldToolbarHeader')
				->removeComponentsByType('GridFieldExportButton')
				->removeComponentsByType('GridFieldDetailForm')
				->addComponents($detailForm);

		// SS_Log::log(new Exception(print_r($fieldConfig->getComponents(), true)), SS_Log::NOTICE);

		$listField = new GridField(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig
		);

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}

		$form = new Form(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		);
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm'));
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);
		
		return $form;
	}

}

class ShopAdmin_ItemRequest extends GridFieldDetailForm_ItemRequest {

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
				->setUseButtonTag(true)
				->addExtraClass('ss-ui-action-constructive')
				->setAttribute('data-icon', 'accept'));

			$actions->push(FormAction::create('doDelete', _t('GridFieldDetailForm.Delete', 'Delete'))
				->addExtraClass('ss-ui-action-destructive'));
		}
		else { // adding new record

			//Change the Save label to 'Create'
			$actions->push(FormAction::create('doSave', _t('GridFieldDetailForm.Create', 'Create'))
				->setUseButtonTag(true)
				->addExtraClass('ss-ui-action-constructive')
				->setAttribute('data-icon', 'add'));
				
			// Add a Cancel link which is a button-like link and link back to one level up.
			$curmbs = $this->Breadcrumbs();
			if($curmbs && $curmbs->count() >= 2){

				$one_level_up = $curmbs->offsetGet($curmbs->count()-2);
				$text = "
				<a class=\"crumb ss-ui-button ss-ui-action-destructive cms-panel-link ui-corner-all\" href=\"".$one_level_up->Link."\">
					Cancel
				</a>";
				$actions->push(new LiteralField('cancelbutton', $text));
			}
		}

		//$actions = $this->record->getCMSActions();
		$actions->push(
			FormAction::create('doRandom', 'Random', 'random')
					->setDescription('Random')
					->addExtraClass('ss-ui-action-destructive')
					->setAttribute('data-icon', 'random')
		);

		$form = new Form(
			$this,
			'ItemEditForm',
			$this->record->getCMSFields(),
			$actions,
			$this->component->getValidator()
		);

		if($this->record->ID !== 0) {
		  $form->loadDataFrom($this->record);
		}

		// TODO Coupling with CMS
		$toplevelController = $this->getToplevelController();
		if ($toplevelController && $toplevelController instanceof LeftAndMain) {

			// Always show with base template (full width, no other panels), 
			// regardless of overloaded CMS controller templates.
			// TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
			$form->setTemplate('LeftAndMain_EditForm');
			$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
			$form->setAttribute('data-pjax-fragment', 'CurrentForm Content');

			if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if($toplevelController->hasMethod('Backlink')) {
				$form->Backlink = $toplevelController->Backlink();
			} 
			elseif ($this->popupController->hasMethod('Breadcrumbs')) {
				$parents = $this->popupController->Breadcrumbs(false)->items;
				$form->Backlink = array_pop($parents)->Link;
			} 
			else {
				$form->Backlink = $toplevelController->Link();
			}
		}

		$cb = $this->component->getItemEditFormCallback();
		if($cb) $cb($form, $this);

		return $form;
	}

	function doRandom($data, $form) {

		SS_Log::log(new Exception(print_r('random stuff here', true)), SS_Log::NOTICE);

		$new_record = $this->record->ID == 0;
		$controller = Controller::curr();

		try {
			$form->saveInto($this->record);
			$this->record->write();
			$this->gridField->getList()->add($this->record);
		} catch(ValidationException $e) {
			$form->sessionMessage($e->getResult()->message(), 'bad');
			$responseNegotiator = new PjaxResponseNegotiator(array(
				'CurrentForm' => function() use(&$form) {
					return $form->forTemplate();
				},
				'default' => function() use(&$controller) {
					return $controller->redirectBack();
				}
			));
			if($controller->getRequest()->isAjax()){
				$controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
			}
			return $responseNegotiator->respond($controller->getRequest());
		}

		// TODO Save this item into the given relationship

		$message = sprintf(
			'Random %s %s',
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		if($new_record) {
			return Controller::curr()->redirect($this->Link());
		} elseif($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit(Controller::curr()->getRequest());
		} else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content'); 
			return $controller->redirect($noActionURL, 302); 
		}
	}
}