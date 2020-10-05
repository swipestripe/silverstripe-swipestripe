<?php

namespace SwipeStripe\Core\Admin;

use SwipeStripe\Core\Admin\ShopAdmins;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Extension for admin area to apply shop admin CSS etc.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class ShopAdmin_LeftAndMainExtension extends Extension
{
    public function onAfterInit()
    {
        Requirements::css('swipestripe/css/ShopAdmin.css');
    }

    public function alternateMenuDisplayCheck($className)
    {
        if (class_exists($className)) {
            $obj = new $className();
            if (is_subclass_of($obj, ShopAdmin::class)) {
                return false;
            }
        }
        return true;
    }
}
