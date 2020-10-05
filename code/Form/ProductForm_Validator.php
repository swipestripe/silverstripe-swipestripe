<?php

namespace SwipeStripe\Core\Form;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\RequiredFields;

/**
 * Validator for {@link AddToCartForm} which validates that the product {@link Variation} is
 * correct for the {@link Product} being added to the cart.
 */
class ProductForm_Validator extends RequiredFields
{
    /**
     * Check that current product variation is valid
     *
     * @param Array $data Submitted data
     * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
     */
    public function php($data)
    {
        $valid = parent::php($data);
        $fields = $this->form->Fields();

        //Check that variation exists if necessary
        $form = $this->form;
        $request = $this->form->getRequest();

        //Get product variations from options sent
        //TODO refactor this

        $productVariations = new ArrayList();

        $options = $request->postVar('Options');
        $product = DataObject::get_by_id($data['ProductClass'], $data['ProductID']);
        $variations = ($product) ? $product->Variations() : new ArrayList();

        if ($variations && $variations->exists()) {
            foreach ($variations as $variation) {
                $variationOptions = $variation->Options()->map('AttributeID', 'ID')->toArray();
                if ($options == $variationOptions && $variation->isEnabled()) {
                    $productVariations->push($variation);
                }
            }
        }

        if ((!$productVariations || !$productVariations->exists()) && $product && $product->requiresVariation()) {
            $this->form->sessionMessage(
                _t('ProductForm.VARIATIONS_REQUIRED', 'This product requires options before it can be added to the cart.'),
                'bad'
            );

            //Have to set an error for Form::validate()
            $this->errors[] = true;
            $valid = false;
            return $valid;
        }

        //Validate that base currency is set for this cart
        $config = ShopConfig::current_shop_config();
        if (!$config->BaseCurrency) {
            $this->form->sessionMessage(
                _t('ProductForm.BASE_CURRENCY_NOT_SET', 'The currency is not set.'),
                'bad'
            );

            //Have to set an error for Form::validate()
            $this->errors[] = true;
            $valid = false;
        }

        return $valid;
    }

    /**
     * Helper so that form fields can access the form and current form data
     *
     * @return Form The current form
     */
    public function getForm()
    {
        return $this->form;
    }
}
