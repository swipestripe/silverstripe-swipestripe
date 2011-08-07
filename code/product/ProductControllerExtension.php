<?php

class ProductControllerExtension extends Extension {
  
  public static $allowed_actions = array (
  	'add',
    'remove',
    'clear',
    'AddToCartForm',
    'RemoveFromCartForm'
  );
  
  /**
   * Clear the cart by clearing the session
   */
  function clear() {
    Session::clear('Cart.OrderID');
    $this->goToNextPage();
  }

  /**
   * Add an item to the cart
   */
  function add() {
    self::get_current_order()->addItem($this->getProduct(), $this->getQuantity(), $this->getProductOptions());
    $this->goToNextPage();
  }
  
  /**
   * Remove an item from the cart
   */
  function remove() {
    self::get_current_order()->removeItem($this->getProduct(), $this->getQuantity());
    $this->goToNextPage();
  }
  
  /**
   * Find a product based on current request
   * 
   * @see SS_HTTPRequest
   * @return DataObject 
   */
  private function getProduct() {
    $request = $this->owner->getRequest();
    return DataObject::get_by_id($request->requestVar('ProductClass'), $request->requestVar('ProductID'));
  }
  
  private function getProductOptions() {
    
    $options = new DataObjectSet();
    $request = $this->owner->getRequest();

    foreach ($request->requestVar('Options') as $optionClassName => $optionID) {
      $options->push(DataObject::get_by_id($optionClassName, $optionID));
    }
    return $options;
  }
  
  /**
   * Find the quantity based on current request
   * 
   * @return Int
   */
  private function getQuantity() {
    $quantity = $this->owner->getRequest()->requestVar('Quantity');
    return ($quantity) ?$quantity :1;
  }
  
  /**
   * Send user to next page based on current request vars,
   * if no redirect is specified redirect back.
   * 
   * TODO make this work with AJAX
   */
  private function goToNextPage() {
    $redirectURL = $this->owner->getRequest()->requestVar('Redirect');

    //Check if on site URL, if so redirect there, else redirect back
    if ($redirectURL && Director::is_site_url($redirectURL)) Director::redirect(Director::absoluteURL(Director::baseURL() . $redirectURL));
    else Director::redirectBack();
  }

  /**
   * Retrieve the current cart
   * 
   * @return Order 
   */
  function Cart() {
    $order = self::get_current_order();
    $order->Items();
    $order->Total;

		//HTTP::set_cache_age(0);
		return $order;
	}
	
	/**
   * Get the current order from the session, if order does not exist
   * John Connor it (create a new order)
   * 
   * @return Order
   */
  static function get_current_order() {

    $orderID = Session::get('Cart.OrderID');
    
    if ($orderID) {
      $order = DataObject::get_by_id('Order', $orderID);
    }
    else {
      $order = new Order();
      $order->write();
      Session::set('Cart', array(
        'OrderID' => $order->ID
      ));
      Session::save();
    }
    
    return $order;
  }
  
  function AddToCartForm($quantity = null, $redirectURL = null) {

    $fields = $this->getAddProductFields($quantity, $redirectURL);
    $actions = new FieldSet(
      new FormAction('add', 'Add To Cart')
    );
    $validator = new RequiredFields(
    	'ProductClass', 
    	'ProductID'
    );
     
    return new Form($this->owner, 'AddToCartForm', $fields, $actions, $validator);
	}
	
  function RemoveFromCartForm($quantity = null, $redirectURL = null) {

    $fields = $this->getRemoveProductFields($quantity, $redirectURL);
    $actions = new FieldSet(
      new FormAction('remove', 'Remove From Cart')
    );
    $validator = new RequiredFields(
    	'ProductClass', 
    	'ProductID'
    );
     
    return new Form($this->owner, 'AddToCartForm', $fields, $actions, $validator);
	}
	
	protected function getAddProductFields($quantity = null, $redirectURL = null) {
	  $fields = $this->getProductFields($quantity, $redirectURL);
	  
	  //Cannot use extend in Extension class because the concrete class 
    //is not in Object->extension_instances...
    //$this->owner->extend('updateAddProductFields', $fields);

    if (method_exists($this->owner, 'updateAddProductFields')) {
      $this->owner->updateAddProductFields($fields);
    }
    return $fields;
	}
	
	protected function getRemoveProductFields($quantity = null, $redirectURL = null) {
	  $fields = $this->getProductFields($quantity, $redirectURL);

    if (method_exists($this->owner, 'updateRemoveProductFields')) {
      $this->owner->updateRemoveProductFields($fields);
    }
    return $fields;
	}
	
	protected function getProductFields($quantity = null, $redirectURL = null) {
	  
	  $productObject = $this->owner->data();
	  return new FieldSet(
      new HiddenField('ProductClass', 'ProductClass', $productObject->ClassName),
      new HiddenField('ProductID', 'ProductID', $productObject->ID),
      new HiddenField('Quantity', 'Quantity', $quantity),
      new HiddenField('Redirect', 'Redirect', $redirectURL)
    );
	}
  
}
