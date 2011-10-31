<?php

class ModifierSetField extends DropdownField {
	
	/**
	 * @var Array
	 */
	protected $disabledItems = array();
	
	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "ModifierSetField";
	
	/**
	 * Creates a new optionset field for order modifers with the naming convention
	 * Modifiers[ClassName] where ClassName is name of modifier class.
	 * 
	 * @param name The field name, needs to be the class name of the class that is going to be the modifier
	 * @param title The field title
	 * @param source An map of the dropdown items
	 * @param value The current value
	 * @param form The parent form
	 */
	function __construct($name, $title = "", $source = array(), $value = "", $form = null) {
	  
	  //TODO force the use of class name for the name field of modifier fields
	  //e.g: FlatFeeShipping

	  $name = "Modifiers[$name]";
		parent::__construct($name, $title, $source, $value, $form);
	}
	
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
}