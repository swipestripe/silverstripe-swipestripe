<?php

namespace SwipeStripe\Core\Customer;

use SwipeStripe\Core\Order\Order;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\EmailField;

/**
 * Represents a {@link Customer}, a type of {@link Member}.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class Customer extends Member
{
    private static $table_name = 'Customer';

    private static $db = [
        'Phone' => 'Text',
        'Code' => 'Int' //Just to trigger creating a Customer table
    ];

    /**
     * Link customers to {@link Address}es and {@link Order}s.
     *
     * @var Array
     */
    private static $has_many = [
        'Orders' => Order::class
    ];

    private static $searchable_fields = [
        'Surname',
        'Email'
    ];

    /**
     * Prevent customers from being deleted.
     *
     * @see Member::canDelete()
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        $orders = $this->Orders();
        if ($orders && $orders->exists()) {
            return false;
        }
        return Permission::check('ADMIN', 'any', $member);
    }

    public function delete()
    {
        if ($this->canDelete(Member::currentUser())) {
            parent::delete();
        }
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        //Create a new group for customers
        $allGroups = DataObject::get(Group::class);
        $existingCustomerGroup = $allGroups->find('Title', 'Customers');
        if (!$existingCustomerGroup) {
            $customerGroup = new Group();
            $customerGroup->Title = 'Customers';
            $customerGroup->setCode($customerGroup->Title);
            $customerGroup->write();

            Permission::grant($customerGroup->ID, 'VIEW_ORDER');
        }
    }

    /**
     * Add some fields for managing Members in the CMS.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();

        $fields->push(new TabSet(
            'Root',
            Tab::create(Customer::class)
        ));

        $password = new ConfirmedPasswordField(
            'Password',
            null,
            null,
            null,
            true // showOnClick
        );
        $password->setCanBeEmpty(true);
        if (!$this->ID) {
            $password->showOnClick = false;
        }

        $fields->addFieldsToTab('Root.Customer', [
            new TextField('FirstName'),
            new TextField('Surname'),
            new EmailField(Email::class),
            new TextField('Phone'),
            new ConfirmedPasswordField('Password'),
            $password
        ]);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Overload getter to return only non-cart orders
     *
     * @return ArrayList Set of previous orders for this member
     */
    public function Orders()
    {
        return Order::get()
            ->where('"MemberID" = ' . $this->ID . " AND \"Order\".\"Status\" != 'Cart'")
            ->sort('"Created" DESC');
    }

    /**
     * Returns the current logged in customer
     *
     * @return bool|Member Returns the member object of the current logged in
     *                     user or FALSE.
     */
    public static function currentUser()
    {
        $id = Member::currentUserID();
        if ($id) {
            return DataObject::get_one(Customer::class, "\"Member\".\"ID\" = $id");
        }
    }
}
