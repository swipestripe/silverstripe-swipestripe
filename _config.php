<?php

//For cart adding products
Object::add_extension('CheckoutPage_Controller', 'ProductControllerExtension');

//For unit testing
Object::add_extension('DummyProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'ProductDecorator');
Object::add_extension('DummyVirtualProductPage', 'VirutalProductDecorator');
