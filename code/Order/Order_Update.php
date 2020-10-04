<?php

namespace SwipeStripe\Core\Order;

use SwipeStripe\Core\Order\Order;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

class Order_Update extends DataObject
{
    private static $singular_name = 'Update';
    private static $plural_name = 'Updates';

    private static $db = [
        'Status' => "Enum('Pending,Processing,Dispatched,Cancelled')",
        'Note' => 'Text',
        'Visible' => 'Boolean'
    ];

    /**
     * Relations for this class
     *
     * @var Array
     */
    private static $has_one = [
        'Order' => Order::class,
        'Member' => Member::class
    ];

    private static $summary_fields = [
        'Created.Nice' => 'Created',
        'Status' => 'Order Status',
        'Note' => 'Note',
        'Member.Name' => 'Owner',
        'VisibleSummary' => 'Visible'
    ];

    public function canDelete($member = null)
    {
        return false;
    }

    public function delete()
    {
        if ($this->canDelete(Member::currentUser())) {
            parent::delete();
        }
    }

    /**
     * Update stock levels for {@link Item}.
     *
     * @see DataObject::onAfterWrite()
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        //Update the Order, setting the same status
        if ($this->Status) {
            $order = $this->Order();
            if ($order->exists()) {
                $order->Status = $this->Status;
                $order->write();
            }
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $visibleField = DropdownField::create('Visible', 'Visible', [
            1 => 'Yes',
            0 => 'No'
        ])->setRightTitle('Should this update be visible to the customer?');
        $fields->replaceField('Visible', $visibleField);

        $memberField = HiddenField::create('MemberID', Member::class, Member::currentUserID());
        $fields->replaceField('MemberID', $memberField);
        $fields->removeByName('OrderID');

        return $fields;
    }

    public function Created()
    {
        return $this->dbObject('Created');
    }

    public function VisibleSummary()
    {
        return ($this->Visible) ? 'True' : '';
    }
}
