<?php

class Variation extends DataObject {

  public static $db = array(
    'Amount' => 'Money',
    'Stock' => 'Int'
  );

  public static $has_one = array(
    'Product' => 'Product',
    'Image' => 'ProductImage'
  );
  
  public static $many_many = array(
    'Options' => 'Option'
  );
  
  public static $defaults = array(
    'Stock' => -1
  );
  
  static $extensions = array(
		"Versioned('Live')",
	);
  
  public function __get($property) {

    if (strpos($property, 'AttributeValue_') === 0) {
      return $this->getAttributeOptionValue(str_replace('AttributeValue_', '', $property));
    }
    elseif ($this->hasMethod($method = "get$property")) {
			return $this->$method();
		} 
		elseif ($this->hasField($property)) {
			return $this->getField($property);
		} 
		elseif ($this->failover) {
			return $this->failover->$property;
		}
	}
	
	public function getAttributeOptionValue($attributeID) {

	  $options = $this->Options();
	  if ($options && $options->exists()) foreach ($options as $option) {
	    if ($option->AttributeID == $attributeID) {
	      return $option->Title;
	    }
	  } 
	  return null;
	}
	
	public function getAttributeOption($attributeID) {
	  $options = $this->Options();
	  if ($options && $options->exists()) foreach ($options as $option) {
	    if ($option->AttributeID == $attributeID) {
	      return $option;
	    }
	  } 
	  return null;
	}
	
	function getOptionSummary() {
	  $options = $this->Options();
	  $summary = '';
	  if ($options && $options->exists()) foreach ($options as $option) {
	    $summary .= $option->Title .', ';
	  } 
	  return $summary;
	}
	
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $amountField = new MoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    $fields->push(new NumericField('Stock'));
    
    $product = $this->Product();
    $attributes = $product->Attributes();
    if ($attributes && $attributes->exists()) foreach ($attributes as $attribute) {

      $options = DataObject::get('Option', "ProductID = $product->ID AND AttributeID = $attribute->ID");
      $currentOptionID = ($currentOption = $this->Options()->find('AttributeID', $attribute->ID)) ?$currentOption->ID :null;
      $optionField = new OptionField($attribute->ID, $attribute->Title, $options, $currentOptionID);
      $optionField->setHasEmptyDefault(true);
      $fields->push($optionField);
    }
    
    return $fields;
  }
  
  public function SummaryStock() {
    if ($this->Stock == -1) {
      return 'unlimited';
    }
    return $this->Stock;
  }
  
  public function inStock() {
    if ($this->Stock == -1) return true;
    if ($this->Stock == 0) return false;
    
    //TODO need to check what is currently in people's carts
    if ($this->Stock > 0) return true; 
  }

}