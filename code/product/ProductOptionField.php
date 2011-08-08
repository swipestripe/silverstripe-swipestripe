<?php
class ProductOptionField extends DropdownField {

  /**
   * Create drop down field for a product option, just ensures name of field 
   * is in the format Options[OptionClassName].
   * 
   * @param String $optionClass Class name of the product option
   * @param String $title
   * @param Array $source
   * @param String $value
   * @param Form $form
   * @param String $emptyString
   */
	function __construct($optionClass, $title = null, $optionSet = null, $value = "", $form = null, $emptyString = null) {
		
	  $name = "Options[$optionClass]";
	  
	  $source = array();
	  if ($optionSet) foreach ($optionSet as $option) {
	    $source[$option->ID] = $option->Title . ' ' . $option->SummaryPrice();
	  }
	  
		parent::__construct($name, $title, $source, $value, $form, $emptyString);
	}
	
}