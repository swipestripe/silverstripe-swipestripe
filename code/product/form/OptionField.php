<?php
class OptionField extends DropdownField {

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
	function __construct($attributeID, $title = null, $optionSet = null, $value = "", $form = null, $emptyString = null) {
	  
	  //Pass in the attribute ID
		
	  $name = "Options[$attributeID]";
	  
	  $source = array();
	  if ($optionSet && $optionSet->exists()) foreach ($optionSet as $option) {
	    $source[$option->ID] = $option->Title;
	  }
	  
		parent::__construct($name, $title, $source, $value, $form, $emptyString);
	}
	
}