<?php

namespace SwipeStripe\Core\Product;

use SilverStripe\View\Requirements;
use SilverStripe\Forms\DropdownField;

class AttributeOptionField extends DropdownField
{
    public function __construct($attr, $prev = null)
    {
        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript('swipestripe/swipestripe: javascript/Attribute_OptionField.js');

        $product = $attr->Product();

        // Pass in the attribute ID
        $name = 'Options[' . $attr->ID . ']';
        $title = $attr->Title;
        $source = $product->getOptionsForAttribute($attr->ID)->map();
        $value = null;

        $this->addExtraClass('dropdown');

        //If previous attribute field exists, listen to it and react with new options
        if ($prev && $prev->exists()) {
            $this->setAttribute('data-prev', 'Options[' . $prev->ID . ']');

            $variations = $product->Variations();

            $options = [];
            $temp = [];
            if ($variations && $variations->exists()) {
                foreach ($variations as $variation) {
                    $prevOption = $variation->getOptionForAttribute($prev->ID);
                    $option = $variation->getOptionForAttribute($attr->ID);

                    if ($prevOption && $prevOption->exists() && $option && $option->exists()) {
                        $temp[$prevOption->ID][$option->SortOrder][$option->ID] = $option->Title;
                    }
                }
            }

            //Using SortOrder to sort the options
            foreach ($temp as $prevID => $optionArray) {
                ksort($optionArray);
                $sorted = [];
                foreach ($optionArray as $sort => $optionData) {
                    $sorted += $optionData;
                }
                $options[$prevID] = $sorted;
            }

            $this->setAttribute('data-map', json_encode($options));
        }

        parent::__construct($name, $title, $source, $value);
    }
}
