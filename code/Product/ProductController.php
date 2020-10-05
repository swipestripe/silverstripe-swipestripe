<?php

namespace SwipeStripe\Core\Product;

use SilverStripe\View\Requirements;
use SilverStripe\Core\Convert;
use SwipeStripe\Core\Form\ProductForm;

/**
 * Displays a product, add to cart form, gets options and variation price for a {@link Product}
 * via AJAX.
 *
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage product
 */
class ProductController extends \PageController
{
    /**
     * Allowed actions for this controller
     *
     * @var Array
     */
    private static $allowed_actions = [
        'ProductForm'
    ];

    /**
     * Include some CSS and set the dataRecord to the current Product that is being viewed.
     *
     * @see Page_Controller::init()
     */
    public function init()
    {
        parent::init();

        Requirements::css('swipestripe/swipestripe: css/Shop.css');

        //Get current product page for products that are not part of the site tree
        //and do not have a ParentID set, they are accessed via this controller using
        //Director rules
        if ($this->dataRecord->ID == -1) {
            $params = $this->getURLParams();

            if ($urlSegment = Convert::raw2sql($params['ID'])) {
                $product = Product::get()
                    ->where("\"URLSegment\" = '$urlSegment'")
                    ->limit(1)
                    ->first();

                if ($product && $product->exists()) {
                    $this->dataRecord = $product;
                    $this->failover = $this->dataRecord;

                    $this->customise([
                        'Product' => $this->data()
                    ]);
                }
            }
        }

        $this->extend('onInit');
    }

    /**
     * Legacy function allowing access to product data via $Product variable in templates
     */
    public function Product()
    {
        return $this->data();
    }

    public function ProductForm($quantity = null, $redirectURL = null)
    {
        return ProductForm::create(
            $this,
            ProductForm::class,
            $quantity,
            $redirectURL
        )->disableSecurityToken();
    }
}
