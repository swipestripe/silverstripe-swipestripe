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

	public function init() {

		// set reading lang
		// if(Object::has_extension('SiteTree', 'Translatable') && !$this->request->isAjax()) {
		// 	Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		// }
		
		parent::init();
		
		Requirements::css(CMS_DIR . '/css/screen.css');
		Requirements::css('swipestripe/css/ShopAdmin.css');
		
		Requirements::combine_files(
			'cmsmain.js',
			array_merge(
				array(
					CMS_DIR . '/javascript/CMSMain.js',
					CMS_DIR . '/javascript/CMSMain.EditForm.js',
					CMS_DIR . '/javascript/CMSMain.AddForm.js',
					CMS_DIR . '/javascript/CMSPageHistoryController.js',
					CMS_DIR . '/javascript/CMSMain.Tree.js',
					CMS_DIR . '/javascript/SilverStripeNavigator.js',
					CMS_DIR . '/javascript/SiteTreeURLSegmentField.js'
				),
				Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang', true, true)
			)
		);
	}

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

		$minorActions = CompositeField::create()->setTag('fieldset')->addExtraClass('ss-ui-buttonset');
		$actions = new FieldList($minorActions);

		$actions->push(FormAction::create('doPublish', _t('SiteTree.BUTTONSAVEPUBLISH', 'Save & Publish'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));


		if($this->record->ID !== 0) {

			if ($this->record->isPublished()) {
				$minorActions->push(
					FormAction::create('doUnpublish', _t('SiteTree.BUTTONUNPUBLISH', 'Unpublish'))
						->setUseButtonTag(true)
						->setDescription(_t('SiteTree.BUTTONUNPUBLISHDESC', 'Remove this page from the published site'))
						->addExtraClass('ss-ui-action-destructive')
						->setAttribute('data-icon', 'unpublish')
				);
			}
		}
		else { // adding new record

			$crumbs = $this->Breadcrumbs();
			if($crumbs && $crumbs->count()>=2){
				$one_level_up = $crumbs->offsetGet($crumbs->count()-2);
				$text = "
				<a class=\"crumb ss-ui-button ss-ui-action-destructive cms-panel-link ui-corner-all\" href=\"".$one_level_up->Link."\" data-icon=\"decline\" >
					Cancel
				</a>";
				$minorActions->push(new LiteralField('cancelbutton', $text));
			}
		}

		$minorActions->push(
			FormAction::create('doSave', _t('CMSMain.SAVEDRAFT','Save Draft'))
				->setUseButtonTag(true)
				->setAttribute('data-icon', 'addpage')
		);

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
		if ($toplevelController && $toplevelController instanceof LeftAndMain) {

			// Always show with base template (full width, no other panels), 
			// regardless of overloaded CMS controller templates.
			// TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
			$form->setTemplate('LeftAndMain_EditForm');
			$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
			$form->setAttribute('data-pjax-fragment', 'CurrentForm Content');

			if ($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');

			if ($toplevelController->hasMethod('Backlink')) {
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
		if ($cb) $cb($form, $this);

		return $form;
	}

	function doSave($data, $form) {

		$new_record = $this->record->ID == 0;
		$controller = Controller::curr();

		try {
			$form->saveInto($this->record);
			$this->record->write();
		} 
		catch(ValidationException $e) {
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

		$message = sprintf(
			'Published %s %s',
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		if ($new_record) {
			return Controller::curr()->redirect($this->Link());
		} 
		elseif ($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit(Controller::curr()->getRequest());
		} 
		else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content'); 
			return $controller->redirect($noActionURL, 302); 
		}
	}

	function doPublish($data, $form) {

		$new_record = $this->record->ID == 0;
		$controller = Controller::curr();

		try {
			$form->saveInto($this->record);
			$this->record->write();
			$this->record->doPublish();
		} 
		catch(ValidationException $e) {
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

		$message = sprintf(
			'Published %s %s',
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		if ($new_record) {
			return Controller::curr()->redirect($this->Link());
		} 
		elseif ($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit(Controller::curr()->getRequest());
		} 
		else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content'); 
			return $controller->redirect($noActionURL, 302); 
		}
	}

	function doUnpublish($data, $form) {

		$new_record = $this->record->ID == 0;
		$controller = Controller::curr();

		try {
			$form->saveInto($this->record);
			$this->record->write();
			$this->record->doUnpublish();
		} 
		catch(ValidationException $e) {
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

		$message = sprintf(
			'Published %s %s',
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		if ($new_record) {
			return Controller::curr()->redirect($this->Link());
		} 
		elseif ($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit(Controller::curr()->getRequest());
		} 
		else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content'); 
			return $controller->redirect($noActionURL, 302); 
		}
	}

	function doDelete($data, $form) {

		$new_record = $this->record->ID == 0;
		$controller = Controller::curr();

		try {
			$form->saveInto($this->record);
			$this->record->delete();
		} 
		catch(ValidationException $e) {
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

		$message = sprintf(
			'Published %s %s',
			$this->record->singular_name(),
			'<a href="' . $this->Link('edit') . '">"' . htmlspecialchars($this->record->Title, ENT_QUOTES) . '"</a>'
		);
		
		$form->sessionMessage($message, 'good');

		if ($new_record) {
			return Controller::curr()->redirect($this->Link());
		} 
		elseif ($this->gridField->getList()->byId($this->record->ID)) {
			// Return new view, as we can't do a "virtual redirect" via the CMS Ajax
			// to the same URL (it assumes that its content is already current, and doesn't reload)
			return $this->edit(Controller::curr()->getRequest());
		} 
		else {
			// Changes to the record properties might've excluded the record from
			// a filtered list, so return back to the main view if it can't be found
			$noActionURL = $controller->removeAction($data['url']);
			$controller->getRequest()->addHeader('X-Pjax', 'Content'); 
			return $controller->redirect($noActionURL, 302); 
		}
	}
}