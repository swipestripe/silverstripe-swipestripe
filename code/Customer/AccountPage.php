<?php

namespace SwipeStripe\Core\Customer;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;

/**
 * An account page which displays the order history for any given {@link Member} and displays an individual {@link Order}.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage extends Page
{
    /**
     * Automatically create an AccountPage if one is not found
     * on the site at the time the database is built (dev/build).
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!DataObject::get_one(AccountPage::class)) {
            $page = new AccountPage();
            $page->Title = 'Account';
            $page->Content = '';
            $page->URLSegment = 'account';
            $page->ShowInMenus = 0;
            $page->writeToStage('Stage');
            $page->publish('Stage', 'Live');

            DB::alteration_message('Account page \'Account\' created', 'created');
        }
    }

    /**
     * Prevent CMS users from creating another account page.
     *
     * @see SiteTree::canCreate()
     * @return Boolean Always returns false
     */
    public function canCreate($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return false;
    }

    /**
     * Prevent CMS users from deleting the account page.
     *
     * @see SiteTree::canDelete()
     * @return Boolean Always returns false
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return false;
    }

    public function delete()
    {
        if ($this->canDelete(Member::currentUser())) {
            parent::delete();
        }
    }

    /**
     * Prevent CMS users from unpublishing the account page.
     *
     * @see SiteTree::canDeleteFromLive()
     * @see AccountPage::getCMSActions()
     * @return Boolean Always returns false
     */
    public function canDeleteFromLive($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return false;
    }

    /**
     * To remove the unpublish button from the CMS, as this page must always be published
     *
     * @see SiteTree::getCMSActions()
     * @see AccountPage::canDeleteFromLive()
     * @return FieldList Actions fieldset with unpublish action removed
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();
        $actions->removeByName('action_unpublish');
        return $actions;
    }

    /**
     * Remove page type dropdown to prevent users from changing page type.
     *
     * @see Page::getCMSFields()
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('ClassName');
        return $fields;
    }
}
