<?php

namespace SwipeStripe\Core\Admin;

use SwipeStripe\Core\Admin\ShopAdmin;
use SwipeStripe\Core\Admin\ShopConfig;
use SwipeStripe\Core\Product\Attribute;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * Shop admin area for managing product attributes.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdminAttribute extends ShopAdmin
{
    private static $tree_class = ShopConfig::class;

    private static $allowed_actions = [
        'AttributeSettings',
        'AttributeSettingsForm',
        'saveAttributeSettings'
    ];

    private static $url_rule = 'ShopConfig/Attribute';
    private static $url_priority = 75;
    private static $menu_title = 'Shop Product Attributes';

    private static $url_handlers = [
        'ShopConfig/Attribute/AttributeSettingsForm' => 'AttributeSettingsForm',
        'ShopConfig/Attribute' => 'AttributeSettings'
    ];

    public function init()
    {
        parent::init();
        if (!in_array(get_class($this), self::$hidden_sections)) {
            $this->modelClass = ShopConfig::class;
        }
    }

    public function Breadcrumbs($unlinked = false)
    {
        $request = $this->getRequest();
        $items = parent::Breadcrumbs($unlinked);

        if ($items->count() > 1) {
            $items->remove($items->pop());
        }

        $items->push(ArrayData::create([
            'Title' => 'Attribute Settings',
            'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), Attribute::class))
        ]));

        return $items;
    }

    public function SettingsForm($request = null)
    {
        return $this->AttributeSettingsForm();
    }

    public function AttributeSettings($request)
    {
        if ($request->isAjax()) {
            $controller = $this;
            $responseNegotiator = new PjaxResponseNegotiator(
                [
                    'CurrentForm' => function () use (&$controller) {
                        return $controller->AttributeSettingsForm()->forTemplate();
                    },
                    'Content' => function () use (&$controller) {
                        return $controller->renderWith('ShopAdminSettings_Content');
                    },
                    'Breadcrumbs' => function () use (&$controller) {
                        return $controller->renderWith('CMSBreadcrumbs');
                    },
                    'default' => function () use (&$controller) {
                        return $controller->renderWith($controller->getViewer('show'));
                    }
                ],
                $this->response
            );
            return $responseNegotiator->respond($this->getRequest());
        }

        return $this->renderWith('ShopAdminSettings');
    }

    public function AttributeSettingsForm()
    {
        $shopConfig = ShopConfig::get()->First();

        $fields = new FieldList(
            $rootTab = new TabSet(
                'Root',
                $tabMain = new Tab(
                    Attribute::class,
                    GridField::create(
                        'Attributes',
                        'Attributes',
                        $shopConfig->Attributes(),
                        GridFieldConfig_RelationEditor::create()
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
        if ($form->Fields()->hasTabset()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
        }
        $form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'Attribute/AttributeSettingsForm'));

        $form->loadDataFrom($shopConfig);

        return $form;
    }

    public function saveAttributeSettings($data, $form)
    {
        //Hack for LeftAndMain::getRecord()
        self::$tree_class = ShopConfig::class;

        $config = ShopConfig::get()->First();
        $form->saveInto($config);
        $config->write();
        $form->sessionMessage('Saved Attribute Settings', 'good');

        $controller = $this;
        $responseNegotiator = new PjaxResponseNegotiator(
            [
                'CurrentForm' => function () use (&$controller) {
                    //return $controller->renderWith('ShopAdminSettings_Content');
                    return $controller->AttributeSettingsForm()->forTemplate();
                },
                'Content' => function () use (&$controller) {
                    //return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
                },
                'Breadcrumbs' => function () use (&$controller) {
                    return $controller->renderWith('CMSBreadcrumbs');
                },
                'default' => function () use (&$controller) {
                    return $controller->renderWith($controller->getViewer('show'));
                }
            ],
            $this->response
        );
        return $responseNegotiator->respond($this->getRequest());
    }

    public function getSnippet()
    {
        if (!$member = Member::currentUser()) {
            return false;
        }
        if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) {
            return false;
        }

        return $this->customise([
            'Title' => 'Attribute Management',
            'Help' => 'Create default attributes',
            'Link' => Controller::join_links($this->Link(ShopConfig::class), Attribute::class),
            'LinkTitle' => 'Edit default attributes'
        ])->renderWith('SwipeStripe\Core\Admin\ShopAdmin_Snippet');
    }
}
