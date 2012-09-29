<?php

class ShopAdmin extends ModelAdmin {

	static $url_segment = 'shop';

	static $url_priority = 50;

	static $menu_title = 'Shop';

	public $showImportForm = false;

	// static $required_permission_codes = 'CMS_ACCESS_CMSMain';
	// static $session_namespace = 'CMSMain';

	public static $managed_models = array(
		'ShopConfig',
		'Product',
		'Order',
		'Customer'
	);

	public static $url_handlers = array(
		'$ModelClass/$Action' => 'handleAction',
		'$ModelClass/$Action/$ID' => 'handleAction',
	);

	protected $shopConfigSection;

	public function init() {

		// set reading lang
		// if(Object::has_extension('SiteTree', 'Translatable') && !$this->request->isAjax()) {
		// 	Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
		// }
		
		parent::init();

		// if ($this->modelClass == 'ShopConfig') {
		// 	$request = $this->getRequest();
		// 	$this->shopConfigSection = $request->param('Action');
		// }
		
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

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);
		return $items;
	}

	public function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}
		if(!count($models)) {
			user_error(
				'ModelAdmin::getManagedModels(): 
				You need to specify at least one DataObject subclass in public static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"', 
				E_USER_ERROR
			);
		}

		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				$models[$v] = array('title' => singleton($v)->i18n_plural_name());
				unset($models[$k]);
			}
		}
		return $models;
	}

	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return SS_List of forms 
	 */
	protected function getManagedModelTabs() {

		$forms  = new ArrayList();

		$models = $this->getManagedModels();
		foreach($models as $class => $options) { 
			$forms->push(new ArrayData(array (
				'Title'     => $options['title'],
				'ClassName' => $class,
				'Link' => $this->Link($this->sanitiseClassName($class)),
				'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
			)));
		}
		
		return $forms;
	}

	public function Tools() {
		if ($this->modelClass == 'ShopConfig') return false;
		else return parent::Tools();
	}

	public function Content() {
		return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
	}

	public function EditForm($request = null) {
		return $this->getEditForm();
	}

	public function getEditForm($id = null, $fields = null) {

		//If editing the shop settings get the first back and edit that basically...
		if ($this->modelClass == 'ShopConfig') {

			//TODO Licence warning on the settings home page
			//    if (file_exists(BASE_PATH . '/swipestripe') && ShopConfig::get_license_key() == null) {
			    
			//      $warning = _t('ShopConfig.LICENCE_WARNING','
			//        Warning: You have SwipeStripe installed without a license key. 
			//        Please <a href="http://swipestripe.com" target="_blank">purchase a license key here</a> before this site goes live.
			// 		');
			    
			// 		$fields->addFieldToTab("Root.Main", new LiteralField("SwipeStripeLicenseWarning", 
			// 			'<p class="message warning">'.$warning.'</p>'
			// 		), "Title");
			// 	}
			// }

			return $this->renderWith('ShopAdmin_ConfigEditForm');
		}
		
		$list = $this->getList();

		$exportButton = new GridFieldExportButton('before');
		$exportButton->setExportColumns($this->getExportFields());

		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->addComponent($exportButton)
				->removeComponentsByType('GridFieldFilterHeader')
				->removeComponentsByType('GridFieldToolbarHeader')
				->removeComponentsByType('GridFieldExportButton');

		if ($this->modelClass == 'Product') {
			$detailForm = new GridFieldDetailForm();
			$detailForm->setItemRequestClass('ShopAdmin_ItemRequest');

			$fieldConfig
				->removeComponentsByType('GridFieldDetailForm')
				->addComponents($detailForm);
		}

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

	public function SettingsContent() {
		return $this->renderWith('ShopAdminSettings_Content');
	}

	public function SettingsForm($request = null) {
		return;
	}

	public function Snippets() {

		$snippets = new ArrayList();
		$subClasses = ClassInfo::subclassesFor('ShopAdmin');

		foreach ($subClasses as $className) {
			$obj = new $className();
			$snippet = $obj->getSnippet();

			if ($snippet) {
				$snippets->push(new ArrayData(array(
					'Content' => $snippet
				)));
			}
		}
		return $snippets;
	}

	public function getSnippet() {
		return false;
	}

}

class ShopAdmin_EmailAdmin extends ShopAdmin {

	static $url_rule = 'ShopConfig/EmailSettings';
	static $url_priority = 55;

	public static $url_handlers = array(
		'ShopConfig/EmailSettings/EmailSettingsForm' => 'EmailSettingsForm',
		'ShopConfig/EmailSettings' => 'EmailSettings'
	);

	public function init() {
		$this->shopConfigSection = 'EmailSettings';
		parent::init();
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Email Settings',
			'Link' => false
		)));

		return $items;
	}

	public function SettingsForm($request = null) {

		$this->shopConfigSection = 'EmailSettings';
		return $this->EmailSettingsForm();
	}

	public function EmailSettings($request) {
		return $this->renderWith('ShopAdminSettings');
	}

	public function EmailSettingsForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabMain = new Tab('Receipt',
					new HiddenField('ShopConfigSection', null, 'EmailSettings'),
					new TextField('ReceiptFrom', _t('ShopConfig.FROM', 'From')),
					TextField::create('ReceiptTo', _t('ShopConfig.TO', 'To'))
						->setValue(_t('ShopConfig.RECEIPT_TO', 'Sent to customer'))
						->performReadonlyTransformation(),
					new TextField('ReceiptSubject', _t('ShopConfig.SUBJECT_LINE', 'Subject line')),
					new TextareaField('ReceiptBody', _t('ShopConfig.MESSAGE', 'Message (order details are included in the email)')),
					new TextareaField('EmailSignature', _t('ShopConfig.SIGNATURE', 'Signature'))
				),
				new Tab('Notification',
					TextField::create('NotificationFrom', _t('ShopConfig.FROM', 'From'))
						->setValue(_t('ShopConfig.NOTIFICATION_FROM', 'Customer email address'))
						->performReadonlyTransformation(),
					new TextField('NotificationTo', _t('ShopConfig.TO', 'To')),
					new TextField('NotificationSubject', _t('ShopConfig.SUBJECT_LINE', 'Subject line')),
					new TextareaField('NotificationBody', _t('ShopConfig.MESSAGE', 'Message (order details are included in the email)'))
				)
			)
		);

		$actions = new FieldList();
		$actions->push(FormAction::create('saveEmailSettings', _t('GridFieldDetailForm.Save', 'Save'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EmailSettings/EmailSettingsForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveEmailSettings($data, $form) {

		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved Email Settings', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					//return $controller->renderWith('ShopAdminSettings_Content');
					return $controller->EmailSettingsForm()->forTemplate();
				},
				'Content' => function() use(&$controller) {
					//return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
				},
				'Breadcrumbs' => function() use (&$controller) {
					return $controller->renderWith('CMSBreadcrumbs');
				},
				'default' => function() use(&$controller) {
					return $controller->renderWith($controller->getViewer('show'));
				}
			),
			$this->response
		); 
		return $responseNegotiator->respond($this->getRequest());
	}

	public function getSnippet() {
		return $this->customise(array(
			'Title' => 'Email Settings',
			'Help' => 'Order notification and receipt details and recipeients.',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'EmailSettings'),
			'LinkTitle' => 'Edit Email Settings'
		))->renderWith('ShopAdmin_Snippet');
	}

}

class ShopAdmin_CountriesAdmin extends ShopAdmin {

	static $url_rule = 'ShopConfig/Countries';
	static $url_priority = 55;

	public static $url_handlers = array(
		'ShopConfig/Countries/CountriesForm' => 'CountriesForm',
		'ShopConfig/Countries' => 'Countries'
	);

	public function init() {
		$this->shopConfigSection = 'Countries';
		parent::init();
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Countries',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'Countries'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {

		$this->shopConfigSection = 'Countries';
		return $this->CountriesForm();
	}

	public function Countries($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->CountriesForm()->forTemplate();
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith('ShopAdminSettings_Content');
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			); 
			return $responseNegotiator->respond($this->getRequest());
		}

		return $this->renderWith('ShopAdminSettings');
	}

	public function CountriesForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabMain = new Tab('Shipping',
					new HiddenField('ShopConfigSection', null, 'Countries'),
					new GridField(
			      'ShippingCountries',
			      'Shipping Countries',
			      $shopConfig->ShippingCountries(),
			      GridFieldConfig_RelationEditor::create()
							->removeComponentsByType('GridFieldFilterHeader')
							->removeComponentsByType('GridFieldAddExistingAutocompleter')
			    )
				),
				new Tab('Billing',
					new GridField(
			      'BillingCountries',
			      'Billing Countries',
			      $shopConfig->BillingCountries(),
			      GridFieldConfig_RelationEditor::create()
							->removeComponentsByType('GridFieldFilterHeader')
							->removeComponentsByType('GridFieldAddExistingAutocompleter')
			    )
				)
			)
		);

		$actions = new FieldList();
		$actions->push(FormAction::create('saveCountries', _t('GridFieldDetailForm.Save', 'Save'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'Countries/CountriesForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveCountries($data, $form) {

		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved Countries', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					return $controller->CountriesForm()->forTemplate();
				},
				'Content' => function() use(&$controller) {
					return $controller->renderWith('ShopAdminSettings_Content');
				},
				'Breadcrumbs' => function() use (&$controller) {
					return $controller->renderWith('CMSBreadcrumbs');
				},
				'default' => function() use(&$controller) {
					return $controller->renderWith($controller->getViewer('show'));
				}
			),
			$this->response
		); 
		return $responseNegotiator->respond($this->getRequest());
	}

	public function getSnippet() {
		return $this->customise(array(
			'Title' => 'Countries and Regions',
			'Help' => 'Shipping and billing countries and regions.',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'Countries'),
			'LinkTitle' => 'Edit Countries and Regions'
		))->renderWith('ShopAdmin_Snippet');
	}

}

class ShopAdmin_LeftAndMainExtension extends Extension {

	public function alternateMenuDisplayCheck($className) {
		if (class_exists($className)) {
			$obj = new $className();
			if (is_subclass_of($obj, 'ShopAdmin')) {
				return false;
			}
		}
		return true;
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
