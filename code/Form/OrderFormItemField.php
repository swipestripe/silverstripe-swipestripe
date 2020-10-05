<?php

namespace SwipeStripe\Core\Form;

use SilverStripe\Forms\FormField;

/**
 * Represent each {@link Item} in the {@link Order} on the {@link OrderForm}.
 */
class OrderFormItemField extends FormField
{
    /**
     * Template for rendering
     *
     * @var String
     */
    protected $template = OrderFormItemField::class;

    /**
     * Current {@link Item} this field represents.
     *
     * @var Item
     */
    protected $item;

    /**
     * Construct the form field and set the {@link Item} it represents.
     *
     * @param Item $item
     * @param Form $form
     */
    public function __construct($item, $form = null)
    {
        $this->item = $item;
        $name = 'OrderItem' . $item->ID;
        parent::__construct($name, null, '', null, $form);
    }

    /**
     * Render the form field with the correct template.
     *
     * @see FormField::FieldHolder()
     * @return String
     */
    public function FieldHolder($properties = [])
    {
        return $this->renderWith($this->template);
    }

    /**
     * Retrieve the {@link Item} this field represents.
     *
     * @return Item
     */
    public function Item()
    {
        return $this->item;
    }

    /**
     * Set the {@link Item} this field represents.
     *
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    /**
     * Validate this form field, make sure the {@link Item} exists, is in the current
     * {@link Order} and the item is valid for adding to the cart.
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

        //Check that item exists and is in the current order
        if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
            $errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Order.');
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

        return $valid;
    }
}
