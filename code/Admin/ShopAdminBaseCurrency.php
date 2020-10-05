<?php

namespace SwipeStripe\Core\Admin;

use SwipeStripe\Core\Admin\ShopAdmin;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\Control\Controller;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\Form;

/**
 * Shop admin area for managing base currency
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdminBaseCurrency extends ShopAdmin
{
    private static $tree_class = ShopConfig::class;

    private static $allowed_actions = [
        'BaseCurrencySettings',
        'BaseCurrencySettingsForm',
        'saveBaseCurrencySettings'
    ];

    private static $url_rule = 'ShopConfig/BaseCurrency';
    private static $url_priority = 65;
    private static $menu_title = 'Shop Base Currency';

    private static $url_handlers = [
        'ShopConfig/BaseCurrency/BaseCurrencySettingsForm' => 'BaseCurrencySettingsForm',
        'ShopConfig/BaseCurrency' => 'BaseCurrencySettings'
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

        $items->push(new ArrayData([
            'Title' => 'Base Currency',
            'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'BaseCurrency'))
        ]));

        return $items;
    }

    public function SettingsForm($request = null)
    {
        return $this->BaseCurrencySettingsForm();
    }

    public function BaseCurrencySettings($request)
    {
        if ($request->isAjax()) {
            $controller = $this;
            $responseNegotiator = new PjaxResponseNegotiator(
                [
                    'CurrentForm' => function () use (&$controller) {
                        return $controller->BaseCurrencySettingsForm()->forTemplate();
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

    public function BaseCurrencySettingsForm()
    {
        $shopConfig = ShopConfig::get()->First();

        $fields = new FieldList(
            $rootTab = new TabSet(
                'Root',
                $tabMain = new Tab(
                    'BaseCurrency',
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
        if ($form->Fields()->hasTabset()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
        }
        $form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'BaseCurrency/BaseCurrencySettingsForm'));

        $form->loadDataFrom($shopConfig);

        return $form;
    }

    public function saveBaseCurrencySettings($data, $form)
    {
        //Hack for LeftAndMain::getRecord()
        self::$tree_class = ShopConfig::class;

        $config = ShopConfig::get()->First();
        $form->saveInto($config);
        $config->write();
        $form->sessionMessage('Saved BaseCurrency Key', 'good');

        $controller = $this;
        $responseNegotiator = new PjaxResponseNegotiator(
            [
                'CurrentForm' => function () use (&$controller) {
                    //return $controller->renderWith('ShopAdminSettings_Content');
                    return $controller->BaseCurrencySettingsForm()->forTemplate();
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
            'Title' => 'Base Currency',
            'Help' => 'Set base currency.',
            'Link' => Controller::join_links($this->Link(ShopConfig::class), 'BaseCurrency'),
            'LinkTitle' => 'Edit base currency'
        ])->renderWith('SwipeStripe\Core\Admin\ShopAdmin_Snippet');
    }
}
