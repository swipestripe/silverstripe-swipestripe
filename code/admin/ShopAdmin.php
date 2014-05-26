<?php
/**
 * Shop admin area for managing orders, customers and shop settings.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin extends ModelAdmin {

	private static $url_segment = 'shop';

	private static $url_priority = 50;

	private static $menu_title = 'Shop';

	public $showImportForm = false;

	private static $managed_models = array(
		//'Product',
		'Order',
		'Customer',
		'ShopConfig'
	);

	private static $url_handlers = array(
		'$ModelClass/$Action' => 'handleAction',
		'$ModelClass/$Action/$ID' => 'handleAction',
	);

	public static $hidden_sections = array();
	
	private static $allowed_actions = array(
		'EditForm',
		'SettingsContent',
		'SettingsForm'
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
				You need to specify at least one DataObject subclass in private static $managed_models.
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
			return $this->renderWith('ShopAdmin_ConfigEditForm');
		}
		
		$list = $this->getList();

		$buttonAfter = new GridFieldButtonRow('after');
		$exportButton = new GridFieldExportButton('buttons-after-left');
		$exportButton->setExportColumns($this->getExportFields());

		$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->addComponent($buttonAfter)
				->addComponent($exportButton);

		if ($this->modelClass == 'Order' || $this->modelClass == 'Customer') {
			$fieldConfig->removeComponentsByType('GridFieldAddNewButton');
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

		$classes = array();
		foreach ($subClasses as $className) {
			$classes[$className] = Config::inst()->get($className, 'url_priority');
		}
		asort($classes);

		foreach ($classes as $className => $order) {
			$obj = new $className();
			$snippet = $obj->getSnippet();

			if ($snippet && !in_array($className, self::$hidden_sections)) {
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

/**
 * Shop admin area for managing email settings
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_EmailAdmin extends ShopAdmin {
	
	private static $tree_class = 'ShopConfig';
	
	private static $allowed_actions = array(
		'EmailSettings',
		'EmailSettingsForm',
		'saveEmailSettings'
	);

	private static $url_rule = 'ShopConfig/EmailSettings';
	private static $url_priority = 60;
	private static $menu_title = 'Shop Emails';

	private static $url_handlers = array(
		'ShopConfig/EmailSettings/EmailSettingsForm' => 'EmailSettingsForm',
		'ShopConfig/EmailSettings' => 'EmailSettings'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Email Settings',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'EmailSettings'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->EmailSettingsForm();
	}

	public function EmailSettings($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->EmailSettingsForm()->forTemplate();
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
					TextareaField::create('ReceiptBody', _t('ShopConfig.MESSAGE', 'Message'))
						->setRightTitle(_t('ShopConfig.MESSAGE_DETAILS', 'Order details are included in the email below this message')),
					new TextareaField('EmailSignature', _t('ShopConfig.SIGNATURE', 'Signature'))
				),
				new Tab('Notification',
					TextField::create('NotificationFrom', _t('ShopConfig.FROM', 'From'))
						->setValue(_t('ShopConfig.NOTIFICATION_FROM', 'Customer email address'))
						->performReadonlyTransformation(),
					new TextField('NotificationTo', _t('ShopConfig.TO', 'To')),
					new TextField('NotificationSubject', _t('ShopConfig.SUBJECT_LINE', 'Subject line')),
					TextareaField::create('NotificationBody', _t('ShopConfig.MESSAGE', 'Message'))
						->setRightTitle(_t('ShopConfig.MESSAGE_DETAILS', 'Order details are included in the email below this message'))
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
		// self::$tree_class = 'ShopConfig';

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

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Email Settings',
			'Help' => 'Order notification and receipt details and recipeients.',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'EmailSettings'),
			'LinkTitle' => 'Edit Email Settings'
		))->renderWith('ShopAdmin_Snippet');
	}

}

/**
 * Shop admin area for managing base currency
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_BaseCurrency extends ShopAdmin {
	
	private static $tree_class = 'ShopConfig';
	
	private static $allowed_actions = array(
		'BaseCurrencySettings',
		'BaseCurrencySettingsForm',
		'saveBaseCurrencySettings'
	);

	private static $url_rule = 'ShopConfig/BaseCurrency';
	private static $url_priority = 65;
	private static $menu_title = 'Shop Base Currency';

	private static $url_handlers = array(
		'ShopConfig/BaseCurrency/BaseCurrencySettingsForm' => 'BaseCurrencySettingsForm',
		'ShopConfig/BaseCurrency' => 'BaseCurrencySettings'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Base Currency',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'BaseCurrency'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->BaseCurrencySettingsForm();
	}

	public function BaseCurrencySettings($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->BaseCurrencySettingsForm()->forTemplate();
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

	public function BaseCurrencySettingsForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet("Root",
				$tabMain = new Tab('BaseCurrency',
					TextField::create('BaseCurrency', _t('ShopConfig.BASE_CURRENCY', 'Base Currency'))
						->setRightTitle('3 letter code for base currency - <a href="http://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank">available codes</a>'),
					TextField::create('BaseCurrencySymbol', _t('ShopConfig.BASE_CURRENCY_SYMBOL', 'Base Currency Symbol'))
						->setRightTitle('Symbol to be used for the base currency e.g: $'),
					NumericField::create('BaseCurrencyPrecision', _t('ShopConfig.BASE_CURRENCY_PRECISION', 'Base Currency Precision'))
						->setRightTitle('Most currencies use two digits after the decimal place. If using digital currencies like Bitcoin, precision should be at least eight digits after the decimal.')
				)
			)
		);

		if ($shopConfig->BaseCurrency) {
			$fields->addFieldToTab('Root.BaseCurrency', new LiteralField('BaseCurrencyNotice', '
				<p class="message warning">Base currency has already been set, do not change unless you know what you are doing.</p>
			'), 'BaseCurrency');
		}

		$actions = new FieldList();
		$actions->push(FormAction::create('saveBaseCurrencySettings', _t('GridFieldDetailForm.Save', 'Save'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));

		$validator = new RequiredFields('BaseCurrency');

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions,
			$validator
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'BaseCurrency/BaseCurrencySettingsForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveBaseCurrencySettings($data, $form) {

		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved BaseCurrency Key', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					//return $controller->renderWith('ShopAdminSettings_Content');
					return $controller->BaseCurrencySettingsForm()->forTemplate();
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

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Base Currency',
			'Help' => 'Set base currency.',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'BaseCurrency'),
			'LinkTitle' => 'Edit base currency'
		))->renderWith('ShopAdmin_Snippet');
	}

}

/**
 * Shop admin area for managing product attributes.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_Attribute extends ShopAdmin {
	
	private static $tree_class = 'ShopConfig';
	
	private static $allowed_actions = array(
		'AttributeSettings',
		'AttributeSettingsForm',
		'saveAttributeSettings'
	);

	private static $url_rule = 'ShopConfig/Attribute';
	private static $url_priority = 75;
	private static $menu_title = 'Shop Product Attributes';

	private static $url_handlers = array(
		'ShopConfig/Attribute/AttributeSettingsForm' => 'AttributeSettingsForm',
		'ShopConfig/Attribute' => 'AttributeSettings'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Attribute Settings',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'Attribute'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->AttributeSettingsForm();
	}

	public function AttributeSettings($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->AttributeSettingsForm()->forTemplate();
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

	public function AttributeSettingsForm() {

		$shopConfig = ShopConfig::get()->First();

		$fields = new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('Attribute',
					GridField::create(
						'Attributes',
						'Attributes',
						$shopConfig->Attributes(),
						GridFieldConfig_HasManyRelationEditor::create()
					)
				)
			)
		);

		$actions = new FieldList();
		$actions->push(FormAction::create('saveAttributeSettings', _t('GridFieldDetailForm.Save', 'Save'))
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
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'Attribute/AttributeSettingsForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveAttributeSettings($data, $form) {

		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved Attribute Settings', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					//return $controller->renderWith('ShopAdminSettings_Content');
					return $controller->AttributeSettingsForm()->forTemplate();
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

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Attribute Management',
			'Help' => 'Create default attributes',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'Attribute'),
			'LinkTitle' => 'Edit default attributes'
		))->renderWith('ShopAdmin_Snippet');
	}

}

/**
 * Extension for admin area to apply shop admin CSS etc.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_LeftAndMainExtension extends Extension {

	public function onAfterInit() {
		Requirements::css('swipestripe/css/ShopAdmin.css');
	}

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

