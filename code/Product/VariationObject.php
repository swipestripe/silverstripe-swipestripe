<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\Product\Variation;
use SwipeStripe\Core\Product\Option;
use SilverStripe\ORM\DataObject;

class Variation_Options extends DataObject
{
    private static $has_one = [
        'Variation' => Variation::class,
        'Option' => Option::class
    ];
}
