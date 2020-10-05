<?php

namespace SwipeStripe\Core\Admin;

use SwipeStripe\Core\Order\Order;
use SwipeStripe\Core\Customer\Customer;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SwipeStripe\Core\Product\Attribute;
use SilverStripe\Core\Extension;

/**
 * Shop admin area for managing orders, customers and shop settings.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin extends ModelAdmin
{
    private static $url_segment = 'shop';

    private static $url_priority = 50;

    private static $menu_title = 'Shop';

    public $showImportForm = false;

    private static $managed_models = [
        //'Product',
        Order::class,
        Customer::class,
        ShopConfig::class
    ];

    private static $url_handlers = [
        '$ModelClass/$Action' => 'handleAction',
        '$ModelClass/$Action/$ID' => 'handleAction',
    ];

    public static $hidden_sections = [];

    private static $allowed_actions = [
        'EditForm',
        'SettingsContent',
        'SettingsForm'
    ];

    public function init()
    {
        // set reading lang
        // if(Object::has_extension('SiteTree', 'Translatable') && !$this->request->isAjax()) {
        // Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('SiteTree')));
        // }

        parent::init();

        // Requirements::css(CMS_DIR . '/css/screen.css');
        Requirements::css('swipestripe/swipestripe: css/ShopAdmin.css');

        // Requirements::combine_files(
        //     'cmsmain.js',
        //     array_merge(
        //         [
        //             CMS_DIR . '/javascript/CMSMain.js',
        //             CMS_DIR . '/javascript/CMSMain.EditForm.js',
        //             CMS_DIR . '/javascript/CMSMain.AddForm.js',
        //             CMS_DIR . '/javascript/CMSPageHistoryController.js',
        //             CMS_DIR . '/javascript/CMSMain.Tree.js',
        //             CMS_DIR . '/javascript/SilverStripeNavigator.js',
        //             CMS_DIR . '/javascript/SiteTreeURLSegmentField.js'
        //         ],
        //         Requirements::add_i18n_javascript(CMS_DIR . '/javascript/lang', true, true)
        //     )
        // );
    }

    /**
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $request = $this->getRequest();
        $items = parent::Breadcrumbs($unlinked);
        return $items;
    }

    public function getManagedModels()
    {
        $models = $this->stat('managed_models');
        if (is_string($models)) {
            $models = [$models];
        }
        if (!count($models)) {
            user_error(
                'ModelAdmin::getManagedModels(): 
				You need to specify at least one DataObject subclass in private static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"',
                E_USER_ERROR
            );
        }

        // Normalize models to have their model class in array key
        foreach ($models as $k => $v) {
            if (is_numeric($k)) {
                $models[$v] = ['title' => singleton($v)->i18n_plural_name()];
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
    protected function getManagedModelTabs()
    {
        $forms = new ArrayList();

        $models = $this->getManagedModels();
        foreach ($models as $class => $options) {
            $forms->push(new ArrayData([
                'Title' => $options['title'],
                'ClassName' => $class,
                'Link' => $this->Link($this->sanitiseClassName($class)),
                'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
            ]));
        }

        return $forms;
    }

    public function Tools()
    {
        if ($this->modelClass == ShopConfig::class) {
            return false;
        } else {
            return parent::Tools();
        }
    }

    public function Content()
    {
        return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
    }

    public function EditForm($request = null)
    {
        return $this->getEditForm();
    }

    public function getEditForm($id = null, $fields = null)
    {
        //If editing the shop settings get the first back and edit that basically...
        if ($this->modelClass == ShopConfig::class) {
            return $this->renderWith('SwipeStripe\Core\Admin\ShopAdmin_ConfigEditForm');
        }

        $list = $this->getList();

        $buttonAfter = new GridFieldButtonRow('after');
        $exportButton = new GridFieldExportButton('buttons-after-left');
        $exportButton->setExportColumns($this->getExportFields());

        $fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
                ->addComponent($buttonAfter)
                ->addComponent($exportButton);

        if ($this->modelClass == Order::class || $this->modelClass == Customer::class) {
            $fieldConfig->removeComponentsByType(GridFieldAddNewButton::class);
        }

        $listField = new GridField(
            $this->sanitiseClassName($this->modelClass),
            false,
            $list,
            $fieldConfig
        );

        // Validation
        if (singleton($this->modelClass)->hasMethod('getCMSValidator')) {
            $detailValidator = singleton($this->modelClass)->getCMSValidator();
            $listField->getConfig()->getComponentByType(GridFieldDetailForm::class)->setValidator($detailValidator);
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

    public function SettingsContent()
    {
        return $this->renderWith('ShopAdminSettings_Content');
    }

    public function SettingsForm($request = null)
    {
        return;
    }

    public function Snippets()
    {
        $snippets = new ArrayList();
        $subClasses = ClassInfo::subclassesFor(ShopAdmin::class);

        $classes = [];
        foreach ($subClasses as $className) {
            $classes[$className] = Config::inst()->get($className, 'url_priority');
        }
        asort($classes);

        foreach ($classes as $className => $order) {
            $obj = new $className();
            $snippet = $obj->getSnippet();

            if ($snippet && !in_array($className, self::$hidden_sections)) {
                $snippets->push(new ArrayData([
                    'Content' => $snippet
                ]));
            }
        }
        return $snippets;
    }

    public function getSnippet()
    {
        return false;
    }
}
