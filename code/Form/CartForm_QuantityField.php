<?php

namespace SwipeStripe\Core\Form;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\NumericField;

/**
 * Quantity field for displaying each {@link Item} in an {@link Order} on the {@link CartPage}.
 */
class CartForm_QuantityField extends TextField
{
    /**
     * Template for rendering the field
     *
     * @var String
     */
    protected $template = CartForm_QuantityField::class;

    /**
     * Current {@link Item} represented by this field.
     *
     *  @var Item
     */
    protected $item;

    /**
     * Construct the field and set the current {@link Item} that this field represents.
     *
     * @param String $name
     * @param String $title
     * @param String $value
     * @param Int $maxLength
     * @param Form $form
     * @param Item $item
     */
    public function __construct($name, $value = '', $item = null)
    {
        $this->item = $item;
        parent::__construct($name, '', $value, null, null);
    }

    /**
     * Render the field with the appropriate template.
     *
     * @see FormField::FieldHolder()
     */
    public function FieldHolder($properties = [])
    {
        $obj = ($properties) ? $this->customise($properties) : $this;
        return $this->renderWith($this->template);
    }

    /**
     * Retrieve the current {@link Item} this field represents. Used in the template.
     *
     * @return Item
     */
    public function Item()
    {
        return $this->item;
    }

    /**
     * Set the current {@link Item} this field represents
     *
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    /**
     * Validate this field, check that the current {@link Item} is in the current
     * {@Link Order} and is valid for adding to the cart.
     *
     * @see FormField::validate()
     * @return Boolean
     */
    public function validate($validator)
    {
        $valid = true;
        $item = $this->Item();
        $currentOrder = Cart::get_current_order();
        $items = $currentOrder->Items();
        $quantity = $this->Value();

        $removingItem = false;
        if ($quantity <= 0) {
            $removingItem = true;
        }

        //Check that item exists and is in the current order
        if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
            $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Cart.');
            if ($msg = $this->getCustomValidationMessage()) {
                $errorMessage = $msg;
            }

            $validator->validationError(
                $this->getName(),
                $errorMessage,
                'error'
            );
            $valid = false;
        } elseif ($item) {
            //If removing item, cannot subtract past 0
            if ($removingItem) {
                if ($quantity < 0) {
                    $errorMessage = _t('Form.ITEM_QUANTITY_LESS_ONE', 'The quantity must be at least 0');
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
            } else {
                //If quantity is invalid
                if ($quantity == null || !is_numeric($quantity)) {
                    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be a number');
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
                    $errorMessage = _t('Form.ITEM_QUANTITY_INCORRECT', 'The quantity must be less than 2,147,483,647');
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

                $validation = $item->validateForCart();
                if (!$validation->valid()) {
                    $errorMessage = $validation->message();
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
            }
        }

        return $valid;
    }

    public function Type()
    {
        return 'cartquantity';
    }
}
