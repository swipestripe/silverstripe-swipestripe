<?php
class OrderItemField extends FormField {

	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "OrderItemField";
	
	protected $item;
	
  function __construct($item, $form = null){

		$this->item = $item;
		$name = 'OrderItem' . $item->ID;
		parent::__construct($name, null, '', null, $form);
	}
	
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