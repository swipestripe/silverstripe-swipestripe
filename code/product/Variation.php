<?php
/**
 * Represents a Variation for a Product. A variation needs to have a valid Option set for each
 * Attribute that the product has e.g Size:Medium, Color:Red, Material:Cotton. Variations are Versioned
 * so that when they are added to an Order and then changed, the Order can still access the correct
 * information.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package shop
 * @subpackage product
 * @version 1.0
 */
class Variation extends DataObject {

  /**
   * DB fields for a Variation
   * 
   * @var Array
   */
  public static $db = array(
    'Amount' => 'Money',
    //'Stock' => 'Int',
  	'Status' => "Enum('Enabled,Disabled','Enabled')",
  );

  /**
   * Has one relation for a Variation
   * 
   * @var Array
   */
  public static $has_one = array(
    'Product' => 'Product',
    'Image' => 'ProductImage',
    'StockLevel' => 'StockLevel'
  );
  
  /**
   * Many many relation for a Variation
   * 
   * @var Array
   */
  public static $many_many = array(
    'Options' => 'Option'
  );
  
  /**
   * Default values for a Variation
   * 
   * @var Array
   */
  public static $defaults = array(
    //'Stock' => -1
  );
  
  /**
   * Versioning for a Variation, so that Orders can access the version 
   * that was purchased and correct information can be retrieved.
   * 
   * @var Array
   */
  static $extensions = array(
		"Versioned('Live')",
	);
  
	/**
	 * Overloaded magic method so that attribute values can be retrieved for display 
	 * in CTFs etc.
	 * 
	 * @see ViewableData::__get()
	 * @see Product::getCMSFields()
	 */
  public function __get($property) {

    if (strpos($property, 'AttributeValue_') === 0) {
      return $this->SummaryOfOptionValueForAttribute(str_replace('AttributeValue_', '', $property));
    }
    else {
      return parent::__get($property);
    }
	}
	
	/**
	 * Get a Variation option for an attribute
	 * 
	 * @param Int $attributeID
	 * @return Option
	 */
	public function getOptionForAttribute($attributeID) {
	  $options = $this->Options();
	  if ($options && $options->exists()) foreach ($options as $option) {
	    
	    if ($option->AttributeID == $attributeID) {
	      return $option;
	    }
	  } 
	  return null;
	}
	
	/**
	 * Add fields for editing a Variation in the CMS popup.
	 * 
	 * @return FieldSet
	 */
  public function getCMSFields_forPopup() {

    $fields = new FieldSet();
    
    $amountField = new VariationMoneyField('Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);
    $fields->push($amountField);
    
    //Stock level field
    $level = $this->StockLevel()->Level;
		$fields->push(new StockField('Stock', null, $level));
		
    
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
  
	/**
	 * Get a summary of the Options, helper method for displaying Options nicely
	 * 
	 * TODO allow attributes to be sorted
	 * 
	 * @return String
	 */
	function SummaryOfOptions() {
	  $options = $this->Options();
	  $options->sort('AttributeID');
	  
	  $temp = array();
	  $summary = '';
	  if ($options && $options->exists()) foreach ($options as $option) {
	    $temp[] = $option->Title;
	  } 
	  $summary = implode(', ', $temp);
	  return $summary;
	}
	
	/**
	 * Get attribute option value, helper method
	 * 
	 * @see Variation::__get()
	 * @param Int $attributeID
	 * @return String
	 */
	public function SummaryOfOptionValueForAttribute($attributeID) {

	  $options = $this->Options();
	  if ($options && $options->exists()) foreach ($options as $option) {
	    if ($option->AttributeID == $attributeID) {
	      return $option->Title;
	    }
	  } 
	  return null;
	}
  
  /**
   * Summary of stock, not currently used.
   * 
   * @return String
   */
  public function SummaryOfStock() {
    $level = $this->StockLevel()->Level;
    if ($level == -1) {
      return 'unlimited';
    }
    return $level;
  }
  
  /**
   * Summarize the Product price, returns Amount formatted with Nice()
   * 
   * @return String
   */
  public function SummaryOfPrice() {
    return $this->Amount->Nice();
  }
  
  /**
   * Basic check to see if Product is in stock. Not currently used.
   * 
   * @return Boolean
   */
  public function inStock() {
    
    return true;
    
    //if ($this->Stock == -1) return true;
    //if ($this->Stock == 0) return false;
    
    //TODO need to check what is currently in people's carts
    //if ($this->Stock > 0) return true; 
  }
  
  /**
   * Validate that this variation is suitable for adding to the cart.
   * 
   * @return ValidationResult
   */
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
  
  /**
   * Convenience method to check that this Variation has valid options.
   * 
   * @return Boolean
   */
  public function hasValidOptions() {
    //Get the options for the product
    //Get the attributes for the product
    //Each variation should have a valid option for each attribute
    //Each variation should have only attributes that match the product
    
    $productAttributeOptions = array();
    $productOptions = $this->Product()->Options();
    $productAttributesMap = $this->Product()->Attributes()->map();

    //Only add attributes that have options for this product
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
  
  /**
   * Convenience method to check that this Variation is not a duplicate.
   * 
   * @see Varaition::validate()
   * @return Boolean
   */
  public function isDuplicate() {

    //Hacky way to get new option IDs from $this->record because $this->Options() returns existing options
    //not the new ones passed in POST data    
    $attributeIDs = $this->Product()->Attributes()->map();
    $variationAttributeOptions = array();
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
  
  /**
   * Check if {@link Variation} amount is a negative value
   * 
   * @return Boolean
   */
  public function isNegativeAmount() {
    return $this->Amount->getAmount() < 0;
  }

  /**
   * Validate the Variation before it is saved. 
   * 
   * @see DataObject::validate()
   * @return ValidationResult
   */
  protected function validate() {
    
    $result = new ValidationResult(); 

    if ($this->isDuplicate()) {
      $result->error(
	      'Duplicate variation for this product',
	      'VariationDuplicateError'
	    );
    }

    if ($this->isNegativeAmount()) {
      $result->error(
	      'Variation price difference is a negative amount',
	      'VariationNegativeAmountError'
	    );
    }
    return $result;
	}
	
	/**
	 * Unpublish {@link Product}s if after the Variations have been saved there are no enabled Variations.
	 * 
	 * TODO check that this works when changing attributes
	 * 
	 * @see DataObject::onAfterWrite()
	 */
  protected function onAfterWrite() {
		parent::onAfterWrite();

		$product = $this->Product();
		$variations = $product->Variations();

		if (!in_array('Enabled', $variations->map('ID', 'Status'))) {
		  $product->doUnpublish(); 
		}
	}
	
  function onBeforeWrite() {
    parent::onBeforeWrite();
    
    //If a stock level is set then update StockLevel
    $request = Controller::curr()->getRequest();
    if ($request && $newLevel = $request->requestVar('Stock')) {
      $stockLevel = $this->StockLevel();
      $stockLevel->Level = $newLevel;
      $stockLevel->write();
      $this->StockLevelID = $stockLevel->ID;
    }
  }
	
  public function updateStockBy($quantity) {
    
    $stockLevel = $this->StockLevel();
	  $stockLevel->Level += $quantity;
	  $stockLevel->write();
	}
}