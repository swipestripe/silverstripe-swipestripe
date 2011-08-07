<?php
class CheckoutQuantityField extends TextField {

	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "CheckoutQuantityField";
	
	protected $item;
	
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	function Item() {
	  return $this->item;
	}
	
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
}