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
	
  public function getCMSFields_forPopup() {
    
    //SS_Log::log(new Exception(print_r($this->Product()->Attributes()->map(), true)), SS_Log::NOTICE);
    
    $fields = new FieldSet();
    $fields->push(new MoneyField('Amount'));
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
  
  protected function onBeforeWrite() {
    
    parent::onBeforeWrite();
    /*
    $controller = Controller::curr();
    $request = $controller->getRequest();
    SS_Log::log(new Exception(print_r($request, true)), SS_Log::NOTICE);
    */
	}

}