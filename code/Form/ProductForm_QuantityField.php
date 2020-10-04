<?php

namespace SwipeStripe\Core\Form;

/**
 * Represent each {@link Item} in the {@link Order} on the {@link Product} {@link AddToCartForm}.
 */
class ProductForm_QuantityField extends NumericField
{
    public function Type()
    {
        return 'quantity';
    }

    /**
     * Validate the quantity is above 0.
     *
     * @see FormField::validate()
     * @return Boolean
     */
    public function validate($validator)
    {
        $valid = true;
        $quantity = $this->Value();

        if ($quantity == null || !is_numeric($quantity)) {
            $errorMessage = _t('ProductForm.ITEM_QUANTITY_INCORRECT', 'The quantity must be a number');
            if ($msg = $this->getCustomValidationMessage()) {
                $errorMessage = $msg;
            }

            $validator->validationError(
                $this->getName(),
                $errorMessage,
                'error'
            );
            $valid = false;
        } elseif ($quantity <= 0) {
            $errorMessage = _t('ProductForm.ITEM_QUANTITY_LESS_ONE', 'The quantity must be at least 1');
            if ($msg = $this->getCustomValidationMessage()) {
                $errorMessage = $msg;
            }

            $validator->validationError(
                $this->getName(),
                $errorMessage,
                'error'
            );
            $valid = false;
        } elseif ($quantity > 2147483647) {
            $errorMessage = _t('ProductForm.ITEM_QUANTITY_INCORRECT', 'The quantity must be less than 2,147,483,647');
            if ($msg = $this->getCustomValidationMessage()) {
                $errorMessage = $msg;
            }

            $validator->validationError(
                $this->getName(),
                $errorMessage,
                'error'
            );
            $valid = false;
        }

        return $valid;
    }
}
