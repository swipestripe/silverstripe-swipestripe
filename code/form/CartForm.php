<?php
/**
 * Form to display the {@link Order} contents on the {@link CartPage}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class CartForm extends Form {
  
  /**
   * The current {@link Order} (cart).
   * 
   * @var Order
   */
  public $currentOrder;
  
  /**
   * Construct the form, set the current order and the template to be used for rendering.
   * 
   * @param Controller $controller
   * @param String $name
   * @param FieldList $fields
   * @param FieldList $actions
   * @param Validator $validator
   * @param Order $currentOrder
   */
  function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null, Order $currentOrder = null) {
    
		parent::__construct($controller, $name, $fields, $actions, $validator);
		$this->setTemplate('CartForm');
		$this->currentOrder = $currentOrder;
  }
  
  /*
   * Retrieve the current {@link Order} which is the cart.
   * 
   * @return Order The current order (cart)
   */
  function Cart() {
    return $this->currentOrder;
  }

}