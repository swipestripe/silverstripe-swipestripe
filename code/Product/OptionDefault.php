<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\Product\Option;

class OptionDefault extends Option
{
    private static $singular_name = 'Option';
    private static $plural_name = 'Options';

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ProductID = 0;
    }
}
