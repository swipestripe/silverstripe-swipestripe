<?php
/**
 * Default settings.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 * @version 1.0
 */

//Decorators
Object::add_extension('Payment', 'PaymentDecorator');
Object::add_extension('SiteConfig', 'ShopSettings');

//Extend page controller
Object::add_extension('Page_Controller', 'CartControllerExtension');

//Extend FieldSet
Object::add_extension('FieldSet', 'FieldSetExtension');

//Redirect customers logging in to the account page
Security::set_default_login_dest('account');

//Rules for product links
Director::addRules(50, array( 
  //'product//$ID' => 'Product_Controller',
  'product//$ID/$Action' => 'Product_Controller',
  'swipestripe.xml' => 'ShopSettings_Controller'
));

