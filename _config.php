<?php

//Decorators
Object::add_extension('Member', 'CustomerDecorator');
Object::add_extension('Payment', 'PaymentDecorator');
Object::add_extension('SiteConfig', 'CartConfigDecorator');

//TODO get rid of product controller dependency?
Object::add_extension('Page_Controller', 'ProductControllerExtension');

//Allow product images to be sorted
SortableDataObject::add_sortable_classes(array('ProductImage'));

//Redirect customers logging in to the account page
Security::set_default_login_dest('account');

//TODO is this necessary?
LeftAndMain::require_css('stripeycart/css/StripeyCartAdmin.css');

//For unit testing
/*
Object::add_extension('DummyProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'VirutalProductDecorator');
*/