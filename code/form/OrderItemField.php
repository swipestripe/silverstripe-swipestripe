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
	  
	  SS_Log::log(new Exception(print_r('########################', true)), SS_Log::NOTICE);
	  SS_Log::log(new Exception(print_r($this->item->Object(), true)), SS_Log::NOTICE);
	  SS_Log::log(new Exception(print_r($this->item->Object()->isPublished(), true)), SS_Log::NOTICE);
	  
	  return $this->item;
	}
	
	function setItem(Item $item) {
	  $this->item = $item;
	}
	
}