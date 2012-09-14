<?php
/**
 * Default settings.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */

//Decorators
Object::add_extension('Payment', 'PaymentDecorator');

//Extend page controller
Object::add_extension('Page_Controller', 'CartControllerExtension');

//Redirect customers logging in to the account page
Security::set_default_login_dest('account');
