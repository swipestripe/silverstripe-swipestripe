<?php

namespace SwipeStripe\Core\Order;

use SwipeStripe\Core\Product\Price;
use SwipeStripe\Core\Order\Order;
use SwipeStripe\Core\Product\Product;
use SwipeStripe\Core\Product\Variation;
use SwipeStripe\Core\Order\ItemOption;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\ValidationResult;

/**
 * An Item for an {@link Order}.
 */
class Item extends DataObject
{
    private static $table_name = 'Item';
    /**
     * DB fields for an Item, the object this Item represents (e.g. {@link Product}
     * has a version ID saved as well, so if price is changed or something then
     * a record of the price at time of ordering exists and can be retrieved.
     *
     * @var Array
     */
    private static $db = [
        'Price' => 'Decimal(19,8)',
        'Quantity' => 'Int',
        'ProductVersion' => 'Int',
        'VariationVersion' => 'Int'
    ];

    public function Amount()
    {
        // TODO: Multi currency

        $order = $this->Order();

        $amount = Price::create();
        $amount->setAmount($this->Price);
        $amount->setCurrency($order->BaseCurrency);
        $amount->setSymbol($order->BaseCurrencySymbol);

        $this->extend('updateAmount', $amount);

        return $amount;
    }

    /**
     * Display price, can decorate for multiple currency etc.
     *
     * @return Price
     */
    public function Price()
    {
        $amount = $this->Amount();

        //Transform price here for display in different currencies etc.
        $this->extend('updatePrice', $amount);

        return $amount;
    }

    /**
     * Relations for this class
     *
     * @var Array
     */
    private static $has_one = [
        'Order' => Order::class,
        'Product' => Product::class,
        'Variation' => Variation::class
    ];

    /**
     * Relations for this class
     *
     * @var Array
     */
    private static $has_many = [
        'ItemOptions' => ItemOption::class
    ];

    /**
     * Default values for this class
     *
     * @var Array
     */
    private static $defaults = [
        'Quantity' => 1
    ];

    /**
     * Find item options and delete them to clean up DB.
     *
     * @see DataObject::onBeforeDelete()
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        $itemOptions = DataObject::get(ItemOption::class, 'ItemID = ' . $this->ID);
        if ($itemOptions && $itemOptions->exists()) {
            foreach ($itemOptions as $itemOption) {
                $itemOption->delete();
                $itemOption->destroy();
            }
        }
    }

    public function UnitAmount()
    {
        $itemAmount = $this->Amount();

        $amount = $itemAmount->getAmount();

        foreach ($this->ItemOptions() as $itemOption) {
            $amount += $itemOption->Amount()->getAmount();
        }

        $unitAmount = clone $itemAmount;
        $unitAmount->setAmount($amount);
        return $unitAmount;
    }

    /**
     * Get unit price for this Item including price of any {@link ItemOption}s.
     *
     * @return Money Item price inclusive of item options prices
     */
    public function UnitPrice()
    {
        $itemPrice = $this->Price();
        $amount = $itemPrice->getAmount();

        foreach ($this->ItemOptions() as $itemOption) {
            $amount += $itemOption->Price()->getAmount();
        }

        // TODO: Multi currency

        $unitPrice = clone $itemPrice;
        $unitPrice->setAmount($amount);
        return $unitPrice;
    }

    /**
     * Get unit price for this item including item options price and quantity.
     *
     * @return Price Item total inclusive of item options prices and quantity
     */
    public function Total()
    {
        $unitAmount = $this->UnitAmount();
        $unitAmount->setAmount($unitAmount->getAmount() * $this->Quantity);
        return $unitAmount;
    }

    public function TotalPrice()
    {
        $unitPrice = $this->UnitPrice();
        $unitPrice->setAmount($unitPrice->getAmount() * $this->Quantity);
        return $unitPrice;
    }

    /**
     * Get the variation for the item if a Variation exists in the ItemOptions
     * This assumes only one variation per item.
     *
     * @return Mixed Variation if it exists, otherwise null
     */
    public function Variation()
    {
        return ($this->VariationID) ? Versioned::get_version(Variation::class, $this->VariationID, $this->VariationVersion) : null;
    }

    /**
     * Get the product for the item
     *
     * @return Mixed Product if it exists, otherwise null
     */
    public function Product()
    {
        return Versioned::get_version(Product::class, $this->ProductID, $this->ProductVersion);
    }

    /**
     * Validate this Item to make sure it can be added to a cart.
     *
     * @return ValidationResult
     */
    public function validateForCart()
    {
        return $this->validate();
    }

    /**
     * Validate that product exists and is published, variation exists for product if necessary
     * and quantity is greater than 0
     *
     * @see DataObject::validate()
     * @return ValidationResult
     */
    public function validate()
    {
        $result = new ValidationResult();

        $product = $this->Product();
        $variation = $this->Variation();
        $quantity = $this->Quantity;

        //Check that product is published and exists
        if (!$product || !$product->exists() || !$product->isPublished()) {
            $result->error(
                'Sorry this product is no longer available',
                'ProductExistsError'
            );
        }

        //Check that variation exists if required, not on first write when ItemOption hasn't had a chance to be written
        if ($product && $product->requiresVariation() && (!$variation || !$variation->validateForCart()->valid())) {
            $result->error(
                'Sorry, these product options are no longer available.',
                'VariationExistsError'
            );
        } elseif ($variation && !$variation->validateForCart()->valid()) {
            //If a variation does exist, check that it is valid
            $result->error(
                'Sorry, these product options are no longer available',
                'VariationIncorrectError'
            );
        }

        //Check that quantity is correct
        if (!$quantity || !is_numeric($quantity) || $quantity <= 0 || $quantity > 2147483647) {
            $result->error(
                'Quantity for this product needs to be between 1 - 2,147,483,647',
                'QuantityError'
            );
        }
        return $result;
    }

    public function SummaryOfOptions()
    {
        //TODO: Make this more flexible for formatting

        $summary = '';

        $options = [];
        if ($variation = $this->Variation()) {
            $options[] = $variation->SummaryOfOptions();
        }

        foreach ($this->ItemOptions()->column('Description') as $description) {
            $options[] = $description;
        }

        $summary .= implode('<br /> ', $options);
        return $summary;
    }
}
