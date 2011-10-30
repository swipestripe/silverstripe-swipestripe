<?php

class Variation extends DataObject {

  public static $db = array(
    'Amount' => 'Money',
    'Stock' => 'Int',
  	'Status' => "Enum('Enabled,Disabled','Enabled')",
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
	  $temp = array();
	  $summary = '';
	  if ($options && $options->exists()) foreach ($options as $option) {
	    $temp[] = $option->Title;
	  } 
	  $summary = implode(', ', $temp);
	  return $summary;
	}
	
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $amountField = new MoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    //$fields->push(new NumericField('Stock'));
    
    $product = $this->Product();
    $attributes = $product->Attributes();
    if ($attributes && $attributes->exists()) foreach ($attributes as $attribute) {

      $options = DataObject::get('Option', "ProductID = $product->ID AND AttributeID = $attribute->ID");
      $currentOptionID = ($currentOption = $this->Options()->find('AttributeID', $attribute->ID)) ?$currentOption->ID :null;
      $optionField = new OptionField($attribute->ID, $attribute->Title, $options, $currentOptionID);
      $optionField->setHasEmptyDefault(false);
      $fields->push($optionField);
    }
    
    $fields->push(new DropdownField('Status', 'Status', $this->dbObject('Status')->enumValues()));
    
    return $fields;
  }
  
  public function SummaryStock() {
    if ($this->Stock == -1) {
      return 'unlimited';
    }
    return $this->Stock;
  }
  
  public function SummaryPrice() {
    return $this->Amount->Nice();
  }
  
  public function inStock() {
    if ($this->Stock == -1) return true;
    if ($this->Stock == 0) return false;
    
    //TODO need to check what is currently in people's carts
    if ($this->Stock > 0) return true; 
  }
  
  public function isValid() {

    //Get the options for the product
    //Get the attributes for the product
    //Each variation should have a valid option for each attribute
    
    $productAttributeOptions = array();
    $productOptions = $this->Product()->Options();
    $productAttributesMap = $this->Product()->Attributes()->map();

    if ($productOptions) foreach ($productOptions as $option) {
      
      $attribute = $option->Attribute();
      
      if (!array_key_exists($option->AttributeID, $productAttributesMap)) {
        continue;
      }
      
      if ($attribute) {
        $productAttributeOptions[$option->AttributeID][] = $option->ID;
      }
    }

    $variationAttributeOptions = array();
    $variationOptions = $this->Options();
    
    if (!$variationOptions || !$variationOptions->exists()) return false;
    foreach ($variationOptions as $option) {
      $variationAttributeOptions[$option->AttributeID] = $option->ID;
    }
    
    //If attributes are not equal between product and variation, variation is invalid
    if (array_diff_key($productAttributeOptions, $variationAttributeOptions)
     || array_diff_key($variationAttributeOptions, $productAttributeOptions)) {
      return false;
    }
    
    foreach ($productAttributeOptions as $attributeID => $validOptionIDs) {
      if (!in_array($variationAttributeOptions[$attributeID], $validOptionIDs)) {
        return false;
      }
    }

    return true;
  }
  
  public function isDuplicate() {

    //Hacky way to get new option IDs from $this->record because $this->Options() returns existing options
    //not the new ones passed in POST data    
    $attributeIDs = $this->Product()->Attributes()->map();
    if ($attributeIDs) foreach ($attributeIDs as $attributeID => $title) {
      
      $attributeOptionID = $this->record['Options[' . $attributeID .']'];
      if (isset($attributeOptionID)) {
        $variationAttributeOptions[$attributeID] = $attributeOptionID;
      }
    }

    if ($variationAttributeOptions) {

      $product = $this->Product();
      $variations = DataObject::get('Variation', "Variation.ProductID = " . $product->ID . " AND Variation.ID != " . $this->ID);
      
      if ($variations) foreach ($variations as $variation) {
  
        $tempAttrOptions = array();
        if ($variation->Options()) foreach ($variation->Options() as $option) {
          $tempAttrOptions[$option->AttributeID] = $option->ID;
        } 

        if ($tempAttrOptions == $variationAttributeOptions) {
          return true;
        }
      }
    }
    return false;
  }
  
  public function isEnabled() {
    return $this->Status == 'Enabled';
  }

  protected function validate() {
    /*
    if (!$this->isValid()) {
      return new ValidationResult(false, 'Options are not set for this product variation.');
    }
    */
    if ($this->isDuplicate()) {
      return new ValidationResult(false, 'Duplicate variation for this product.');
    }
    
		return new ValidationResult();
	}

}