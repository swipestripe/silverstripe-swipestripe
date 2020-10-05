<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\Product\Attribute;
use SwipeStripe\Core\Admin\ShopConfig;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HiddenField;

class AttributeDefault extends Attribute
{
    private static $table_name = 'AttributeDefault';
    private static $singular_name = 'Attribute';
    private static $plural_name = 'Attributes';

    private static $has_one = [
        'ShopConfig' => ShopConfig::class
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ProductID = 0;
    }

    public function getCMSFields()
    {
        $fields = new FieldList(
            $rootTab = new TabSet(
                'Root',
                $tabMain = new Tab(
                    Attribute::class,
                    TextField::create('Title')
                        ->setRightTitle('For displaying on the product page'),
                    TextField::create('Description')
                        ->setRightTitle('For displaying on the order'),
                    HiddenField::create('ProductID')
                )
            )
        );

        if ($this->ID) {
            $fields->addFieldToTab('Root.Options', GridField::create(
                'Options',
                'Options',
                $this->Options(),
                GridFieldConfig_Basic::create()
            ));
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }
}
