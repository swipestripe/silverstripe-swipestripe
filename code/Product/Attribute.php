<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\code\Product\Option;
use SwipeStripe\Core\code\Product\Product;
use SwipeStripe\Core\code\Product\Attribute_Default;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SwipeStripe\Core\code\Admin\GridFieldConfig_BasicSortable;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;
use SwipeStripe\Core\code\Admin\GridFieldConfig_Basic;

/**
 * Represents a {@link Product} Attribute, e.g: Size, Color, Material etc.
 * Attributes are created in the {@link ShopAdmin} where they can be set with default
 * Options. They are then selected on each product they relate to. Once an attribute
 * is added to a Product, that Product needs to define some Options for that Attribute
 * and also have some Variations. If the Product does not have Variations when it needs to
 * then it cannot be purchased.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */
class Attribute extends DataObject implements PermissionProvider
{
    private static $singular_name = 'Attribute';
    private static $plural_name = 'Attributes';

    public $firstWrite = false;

    /**
     * DB fields for the Attribute
     *
     * @see Product_Controller::AddToCartForm()
     * @var Array
     */
    private static $db = [
        'Title' => 'Varchar(100)',
        'Description' => 'Text',
        'SortOrder' => 'Int'
    ];

    /**
     * Has many relations for the Attribute
     *
     * @var Array
     */
    private static $has_many = [
        'Options' => Option::class
    ];

    private static $has_one = [
        'Product' => Product::class,
        'DefaultAttribute' => Attribute_Default::class
    ];

    /**
     * Searchable fields for Attributes
     *
     * @var Array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * Summary fields for Attributes
     *
     * @var Array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'Description' => 'Description',
        'OptionSummary' => 'Options'
    ];

    private static $default_sort = 'SortOrder';

    public function providePermissions()
    {
        return [
            'EDIT_ATTRIBUTES' => 'Edit Attributes',
        ];
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('EDIT_ATTRIBUTES');
    }

    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return true;
    }

    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('EDIT_ATTRIBUTES');
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('EDIT_ATTRIBUTES');
    }

    /**
     * Add some fields to the CMS for managing Attributes.
     *
     * @see DataObject::getCMSFields()
     * @return FieldList
     */
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

        if (!$this->ID) {
            $defaultAttributes = Attribute_Default::get();
            if ($defaultAttributes && $defaultAttributes->exists()) {
                $fields->addFieldToTab(
                    'Root.Attribute',
                    DropdownField::create(
                        'DefaultAttributeID',
                        'Use existing attribute',
                        $defaultAttributes->map('ID', 'TitleOptionSummary')->toArray()
                    )->setHasEmptyDefault(true),
                    'Title'
                );
                $fields->addFieldToTab('Root.Attribute', new HeaderField('AttributeOr', 'Or create new one...', 5), 'Title');
            }
        }

        if ($this->ID) {
            $fields->addFieldToTab('Root.Options', GridField::create(
                'Options',
                'Options',
                $this->Options(),
                GridFieldConfig_BasicSortable::create()
            ));
        }

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function OptionSummary()
    {
        $summary = '';
        $options = $this->Options();
        if ($options && $options->exists()) {
            $summary = implode(', ', $options->map()->values());
        }
        return $summary;
    }

    public function TitleOptionSummary()
    {
        $optionString = '';
        $options = $this->Options();
        if ($options && $options->exists()) {
            $optionString = implode(', ', $options->map()->toArray());
        }
        return $this->Title . " - $optionString";
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->firstWrite = !$this->isInDB();

        if ($this->firstWrite) {
            $defaultAttribute = $this->DefaultAttribute();
            if ($defaultAttribute && $defaultAttribute->exists()) {
                $this->Title = $defaultAttribute->Title;
                $this->Description = $defaultAttribute->Description;
            }
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        //Check if first write
        if ($this->firstWrite) {
            $defaultAttribute = $this->DefaultAttribute();
            if ($defaultAttribute && $defaultAttribute->exists()) {
                $options = $defaultAttribute->Options();
                if ($options && $options->exists()) {
                    foreach ($options as $option) {
                        $newOption = new Option();
                        $newOption->update($option->tomap());
                        $newOption->ID = null;
                        $newOption->AttributeID = $this->ID;
                        $newOption->write();
                    }
                }
            }
        }

        //If product variation does not have a complete set of valid options, then disable it
        $product = $this->Product();
        $variations = $product->Variations();

        if ($variations) {
            foreach ($variations as $variation) {
                if (!$variation->hasValidOptions()) {
                    $variation->Status = 'Disabled';
                    $variation->write();
                }
            }
        }
    }

    public function getOptionField($prev = null)
    {
        return Attribute_OptionField::create($this, $prev);
    }
}
