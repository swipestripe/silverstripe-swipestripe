<?php
/**
 * Represents a group of dropdowns for options for a product.
 * 
 * @see SelectionGroup
 * @author frankmullenger
 *
 */
class OptionGroupField extends CompositeField {
	
  private $product;
  
	function __construct($name, $product) {
		$this->name = $name;
		$this->product = $product;
		
		//Set an extra class for the wrapper
		$this->addExtraClass('OptionGroupField');
		
		//Set an ID
		$this->setID('ProductOptions_'.$product->ID);
		
		//Use the product to get the attributes and options and set them to the class
		$items = new FieldSet();
	  $attributes = $this->product->Attributes()->map();
	  
    if ($attributes) foreach ($attributes as $id => $title) {
      
      $options = $this->product->getOptionsForAttribute($id);
      
      /*
      $variations = $product->Variations();
      $options = new DataObjectSet();
      if ($variations && $variations->exists()) foreach ($variations as $variation) {
        
        if ($variation->isEnabled()) {
          $option = $variation->getAttributeOption($id);
          if ($option) $options->push($option); 
        }
      }
      */
      
      if ($options->exists()) {
        $optionsField = new OptionField($id, $title, $options);
        //$optionsField->setEmptyString('Please select');
        $items->push($optionsField);
      }
    }
		parent::__construct($items);
	}
	
	function hasData() {
		return true;
	}
	
	function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('shop/javascript/OptionGroupField.js');
		return parent::FieldHolder();
	}
	
}