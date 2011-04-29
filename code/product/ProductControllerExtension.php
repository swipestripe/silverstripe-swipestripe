<?php
class ProductControllerExtension extends Extension {

  /**
   * Retrieve the current cart
   * 
   * @see CartController::get_current_order()
   * @return Order 
   */
  function Cart() {

    $order = CartController::get_current_order();
    $order->Items();
    $order->Total;

		//HTTP::set_cache_age(0);
		return $order;
	}
  
}