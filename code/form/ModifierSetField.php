<?php
/**
 * For displaying a set of modifiers on the {@link CheckoutPage}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage form
 * @version 1.0
 */
class ModifierSetField extends DropdownField {
	
	/**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "ModifierSetField";

	/**
	 * To hold the modifier (link FlatFeeShipping) class that will set the value for the 
	 * actual order {@link Modifier}.
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
	
	/**
	 * Render field with the appropriate template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder() {
		return $this->renderWith($this->template);
	}
	
	/**
	 * Validation is not currently done on this field at this point.
	 * 
	 * @see FormField::validate()
	 */
	function validate($validator) {
	  return true;
	}
	
	/**
	 * Get the modifier e.g: FlatFeeShipping
	 * 
	 * @return Object Mixed object, class depends on type of ModifierSetField e.g: FlatFeeShippingField
	 */
	function getModifier() {
	  return $this->modifier;
	}
	
}