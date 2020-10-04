<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\Product\Option;

class Option_Default extends Option
{
    private static $singular_name = 'Option';
    private static $plural_name = 'Options';

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ProductID = 0;
    }
}
