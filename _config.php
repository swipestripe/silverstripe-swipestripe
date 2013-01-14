<?php
/**
 * Default settings.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */

//Extensions
Object::add_extension('Payment', 'Payment_Extension');
Object::add_extension('PaymentProcessor', 'Payment_ProcessorExtension');
Object::add_extension('LeftAndMain', 'ShopAdmin_LeftAndMainExtension');
Object::add_extension('Page_Controller', 'Cart');
Object::add_extension('Image', 'Product_ImageExtension');

//Redirect customers logging in to the account page
Security::set_default_login_dest('account');
