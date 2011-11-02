<?php

class ModifierSetField extends DropdownField {
	
	/**
	 * Template for main rendering
	 *
	 * @var string
	 */
	protected $template = "ModifierSetField";

	/**
	 * To hold the modifier that will set the value for the Modifier
	 * 
	 * @var Object
	 */
	protected $modifier;
	
	
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
	function __construct($modifier, $title = "", $source = array(), $value = "", $form = null) {

	  $name = "Modifiers[$modifier->ClassName]";
	  $this->modifier = $modifier;

		parent::__construct($name, $title, $source, $value, $form);
	}
	
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	function validate($validator){
	  return true;
	}
	
}