<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\code\Product\Attribute;
use SwipeStripe\Core\code\Product\Product;
use SwipeStripe\Core\code\Product\Variation;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\PermissionProvider;

/**
 * Represents an Option for an Attribute, e.g: Small, Medium, Large, Red etc.
 * Default Options can be created for Attributes, they are pre populated and duplicated into the Product
 * when the Attribute is added to a Product. Options can be changed for each Product.
 * Default Options will have a ProductID of 0.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */
class Option extends DataObject implements PermissionProvider
{
    private static $table_name = 'Option';
    private static $singular_name = 'Option';
    private static $plural_name = 'Options';

    /**
     * DB fields for this Option
     *
     * @var Array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Description' => 'Text',
        'SortOrder' => 'Int'
    ];

    /**
     * Has one relations for an Option
     *
     * @var Array
     */
    private static $has_one = [
        'Attribute' => Attribute::class,
        'Product' => Product::class
    ];

    /**
     * Belongs many many relations for an Option
     *
     * @var Array
     */
    private static $belongs_many_many = [
        'Variations' => Variation::class
    ];

    private static $default_sort = 'SortOrder';

    public function providePermissions()
    {
        return [
            'EDIT_OPTIONS' => 'Edit Options',
        ];
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('EDIT_OPTIONS');
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
        return Permission::check('EDIT_OPTIONS');
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('EDIT_OPTIONS');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Variations');
        $fields->removeByName('ProductID');
        $fields->removeByName('AttributeID');
        $fields->removeByName('SortOrder');
        return $fields;
    }
}
