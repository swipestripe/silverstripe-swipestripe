<?php

/*
 * Consider making these default
 * 
Object::add_extension('ProductPage', 'ProductDecorator');
Object::add_extension('ShoePage', 'ProductDecorator');
Object::add_extension('ShoeSize', 'ProductOptionDecorator');

Object::add_extension('Member', 'CustomerDecorator');
Object::add_extension('Page_Controller', 'ProductControllerExtension');
Object::add_extension('Payment', 'PaymentDecorator');
Object::add_extension('SiteConfig', 'OrderConfigDecorator');
*/

Object::add_extension('Page_Controller', 'ProductControllerExtension');

//For cart adding products
//Object::add_extension('CheckoutPage_Controller', 'ProductControllerExtension');

//Sorting blocks on home page, images on gallery, addresses on contact page
//SortableDataObject::add_sortable_classes(array('ProductImage'));

//For unit testing
/*
Object::add_extension('DummyProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'VirutalProductDecorator');
*/
