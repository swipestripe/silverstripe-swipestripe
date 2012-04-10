<?php
/**
 * For {@link Product} {@link Option} fields to be displayed on the {@link Product} page.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
 */
class RegionField extends DropdownField {

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
	function __construct($name, $title = null, $source = array(), $value = "", $form = null, $emptyString = null) {

	  $this->addExtraClass('dropdown');
		parent::__construct($name, $title, $source, $value, $form, $emptyString);
	}
	
  function FieldHolder() {

    $regions = Shipping::supported_regions();
    $jsonRegions = json_encode($regions);

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascriptTemplate('swipestripe/javascript/RegionField.js', array(
		  "regions" => $jsonRegions,
		  'defaultValue' => $this->Value()
		));
		return parent::FieldHolder();
	}
	
}