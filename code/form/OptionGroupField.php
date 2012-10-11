<?php
/**
 * Represents a group of dropdowns for options for a {@link Product}.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class OptionGroupField extends CompositeField {
	
  /**
   * Holds the current {@link Product} we are viewing
   * 
   * @var Product
   */
  private $product;
  
  /**
   * Construct the field with correct ID names and values
   * 
   * @param String $name
   * @param Product $product
   */
	function __construct($name, $product) {
		$this->name = $name;
		$this->product = $product;
		
		//Set an extra class for the wrapper
		$this->addExtraClass('OptionGroupField');
		
		//Set an ID
		$this->setID('ProductOptions_'.$product->ID);

		//Use the product to get the attributes and options and set them to the class
		$items = new FieldList();
	  $attributes = $this->product->Attributes()->map()->toArray();

    if ($attributes) foreach ($attributes as $id => $title) {
      
      $options = $this->product->getOptionsForAttribute($id);

      if ($options->exists()) {
        $optionsField = new OptionField($id, $title, $options);
        $items->push($optionsField);
      }
    }
		parent::__construct($items);
	}
	
	/**
	 * This field has data
	 * 
	 * @see CompositeField::hasData()
	 * @return Boolean True always
	 */
	function hasData() {
		return true;
	}
	
	/**
	 * Display this field, add some javascript for handling changes to the dropdowns,
	 * populating the next dropdown via AJAX etc.
	 * 
	 * @see CompositeField::FieldHolder()
	 */
	function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe/javascript/OptionGroupField.js');
		return parent::FieldHolder();
	}
}