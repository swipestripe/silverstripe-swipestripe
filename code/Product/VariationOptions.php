<?php

namespace SwipeStripe\Core\Product;

use SwipeStripe\Core\Product\Variation;
use SwipeStripe\Core\Product\Option;
use SilverStripe\ORM\DataObject;

class VariationOptions extends DataObject
{
    private static $table_name = 'VariationOptions';
    private static $has_one = [
        'Variation' => Variation::class,
        'Option' => Option::class
    ];
}
