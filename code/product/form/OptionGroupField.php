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
      
      $options = DataObject::get('Option', "ProductID = $product->ID AND AttributeID = $id");
      
      if ($options) { 
        $optionsField = new OptionField($id, $title, $options);
        $optionsField->setEmptyString('Please select');
        
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
		Requirements::javascript('simplecart/javascript/OptionGroupField.js');
		
		return parent::FieldHolder();
		
		/*
		$fs = $this->FieldSet();
		$idAtt = isset($this->id) ? " id=\"{$this->id}\"" : '';
		$className = ($this->columnCount) ? "field CompositeField {$this->extraClass()} multicolumn" : "field CompositeField {$this->extraClass()}";
		$content = "<div class=\"$className\"$idAtt>\n";
		
		foreach($fs as $subfield) {
			if($this->columnCount) {
				$className = "column{$this->columnCount}";
				if(!next($fs)) $className .= " lastcolumn";
				$content .= "\n<div class=\"{$className}\">\n" . $subfield->FieldHolder() . "\n</div>\n";
			} else if($subfield){
				$content .= "\n" . $subfield->FieldHolder() . "\n";
			}
		}
		$content .= "</div>\n";
				
		return $content;
		*/
	}
	
}