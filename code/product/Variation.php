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
    else {
      return parent::__get($property);
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
  
  function validateForCart() {
    
    $result = new ValidationResult(); 
	  
	  if (!$this->hasValidOptions()) {
	    $result->error(
	      'This product does not have valid options set',
	      'VariationValidOptionsError'
	    );
	  }
	  
    if (!$this->isEnabled()) {
	    $result->error(
	      'These product options are not available sorry, please choose again',
	      'VariationValidOptionsError'
	    );
	  }
	  
    if ($this->isDeleted()) {
	    $result->error(
	      'These product options have been deleted sorry, please choose again',
	      'VariationDeltedError'
	    );
	  }
	  
	  return $result;
  }
  
  public function hasValidOptions() {
    //Get the options for the product
    //Get the attributes for the product
    //Each variation should have a valid option for each attribute
    //Each variation should have only attributes that match the product
    
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
  
  /**
   * If current variation is enabled, checks lastest version of variation because status is saved
   * in versions. So a variation can be saved as enabled, the version can be added to cart, then
   * the variation is disabled but the previous version stays enabled.
   * 
   * @return Boolean
   */
  public function isEnabled() {

    $latestVersion = Versioned::get_latest_version('Variation', $this->ID);
    return $latestVersion->Status == 'Enabled';
  }
  
  /**
   * Check if the variation has been deleted, need to check the actual variation and not just this version.
   * 
   * @return Boolean
   */
  public function isDeleted() {
    
    $latest = DataObject::get_by_id('Variation', $this->ID);
    return (!$latest || !$latest->exists());
  }

  protected function validate() {
    
    $result = new ValidationResult(); 

    if ($this->isDuplicate()) {
      $result->error(
	      'Duplicate variation for this product',
	      'VariationDuplicateError'
	    );
    }
    
    return $result;
	}

}