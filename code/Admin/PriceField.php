<?php

namespace SwipeStripe\Core\Admin;

use SilverStripe\Forms\CurrencyField;

/**
 * Price field for managing prices.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */
class PriceField extends CurrencyField
{
    /**
     * Render field with custom template
     *
     * @param array $properties
     */
    public function FieldHolder($properties = [])
    {
        $shopConfig = ShopConfig::current_shop_config();
        $properties = array_merge($properties, [
            'BaseCurrency' => $shopConfig->BaseCurrency
        ]);

        $obj = ($properties) ? $this->customise($properties) : $this;
        return $obj->renderWith(self::class . '_holder');
    }

    /**
     * Set value of the field with explicitly formatted numbers.
     *
     * @param mixed $value
     */
    public function setValue($value, $data = null)
    {
        if (!$value) {
            $value = 0.00;
        }
        $shopConfig = ShopConfig::current_shop_config();
        $precision = $shopConfig->BaseCurrencyPrecision;

        $this->value = number_format((double)preg_replace('/[^0-9.\-]/', '', $value), $precision);
        return $this;
    }

    public function validate($validator)
    {
        if (!empty($this->value)
                //validate against any number of digits after the decimal place
                && !preg_match('/^\s*(\-?\$?|\$\-?)?(\d{1,3}(\,\d{3})*|(\d+))(\.\d+)?\s*$/', $this->value)) {
            $validator->validationError(
                $this->name,
                _t('Form.VALIDCURRENCY', 'Please enter a valid currency'),
                'validation',
                false
            );
            return false;
        }
        return true;
    }
}
