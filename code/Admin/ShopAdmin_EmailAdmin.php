<?php

namespace SwipeStripe\Core\Admin;

use SwipeStripe\Core\Admin\ShopAdmin;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * Shop admin area for managing email settings
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_EmailAdmin extends ShopAdmin
{
    private static $tree_class = ShopConfig::class;

    private static $allowed_actions = [
        'EmailSettings',
        'EmailSettingsForm',
        'saveEmailSettings'
    ];

    private static $url_rule = 'ShopConfig/EmailSettings';
    private static $url_priority = 60;
    private static $menu_title = 'Shop Emails';

    private static $url_handlers = [
        'ShopConfig/EmailSettings/EmailSettingsForm' => 'EmailSettingsForm',
        'ShopConfig/EmailSettings' => 'EmailSettings'
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
            'Title' => 'Email Settings',
            'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'EmailSettings'))
        ]));

        return $items;
    }

    public function SettingsForm($request = null)
    {
        return $this->EmailSettingsForm();
    }

    public function EmailSettings($request)
    {
        if ($request->isAjax()) {
            $controller = $this;
            $responseNegotiator = new PjaxResponseNegotiator(
                [
                    'CurrentForm' => function () use (&$controller) {
                        return $controller->EmailSettingsForm()->forTemplate();
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

    public function EmailSettingsForm()
    {
        $shopConfig = ShopConfig::get()->First();

        $fields = new FieldList(
            $rootTab = new TabSet(
                'Root',
                $tabMain = new Tab(
                    'Receipt',
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
                new Tab(
                    'Notification',
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
        if ($form->Fields()->hasTabset()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
        }
        $form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EmailSettings/EmailSettingsForm'));

        $form->loadDataFrom($shopConfig);

        return $form;
    }

    public function saveEmailSettings($data, $form)
    {
        //Hack for LeftAndMain::getRecord()
        // self::$tree_class = 'ShopConfig';

        $config = ShopConfig::get()->First();
        $form->saveInto($config);
        $config->write();
        $form->sessionMessage('Saved Email Settings', 'good');

        $controller = $this;
        $responseNegotiator = new PjaxResponseNegotiator(
            [
                'CurrentForm' => function () use (&$controller) {
                    //return $controller->renderWith('ShopAdminSettings_Content');
                    return $controller->EmailSettingsForm()->forTemplate();
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
            'Title' => 'Email Settings',
            'Help' => 'Order notification and receipt details and recipeients.',
            'Link' => Controller::join_links($this->Link(ShopConfig::class), 'EmailSettings'),
            'LinkTitle' => 'Edit Email Settings'
        ])->renderWith('ShopAdmin_Snippet');
    }
}
