<?php
class CartQuantityField extends TextField {

	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "CartQuantityField";
	
	protected $item;
	
  function __construct($name, $title = null, $value = "", $maxLength = null, $form = null, $item = null){

		$this->item = $item;
		parent::__construct($name, $title, $value, $maxLength, $form);
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