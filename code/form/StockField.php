<?php
/**
 * TODO
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 * @version 1.0
 */
class StockField extends FormField {
  
  protected $template = "StockField";
  
  protected $stockChoiceField;
  
  protected $stockLevelField;
  
  function __construct($name, $title = null, $value = "", $object, $maxLength = null, $form = null) {

    $quantity = $object->getUnprocessedQuantity();
    $cartQuantity = $quantity['InCarts'];
    $orderQuantity = $quantity['InOrders'];
    $label = "Stock : $cartQuantity are currently in shopping carts, $orderQuantity in orders that have not been dispatched.";
    
    $stockChoiceField = new OptionsetField('StockChoice', $label, array(
		  0 => 'Unlimited',
		  1 => 'Specify Stock'
		));
    $this->stockChoiceField = $stockChoiceField;
    
    $stockField = new NumericField('Stock', '', $value, $maxLength, $form);
    $this->stockLevelField = $stockField;
    
    parent::__construct($name, $title, $value, $form);
	}
	
  function Field() {

    $this->stockLevelField->setForm($this->form);
    
    if ($this->value == -1) {
	    $this->stockLevelField->addExtraClass('HiddenStock');
	  }
	  return $this->stockLevelField->SmallFieldHolder();
	}
	
	function StockChoiceField() {

    $stockChoiceValue = ($this->value == -1) ? 0 : 1;
    $this->stockChoiceField->setValue($stockChoiceValue);
	  return $this->stockChoiceField->SmallFieldHolder();
	}
	
  function FieldHolder() {
    
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe/javascript/StockField.js');
		Requirements::customCSS('.HiddenStock{display:none;}');
		return $this->renderWith($this->template);
	}
	
  function setValue($value) {
		$this->value = $value; 
		$this->stockLevelField->setValue($value);
		return $this;
	}
	
	function Value() {
	  return $this->value;
	  return $this->stockLevelField->Value();
	}
	
  /**
   * TODO
   * 
   * @see FormField::validate()
   * @return Boolean
   */
  function validate($validator) {

    //PHP validation does not seem to work for form fields in the CMS
	  $valid = true;
	  return $valid;
	  
    if (isset($this->value) && !is_numeric(trim($this->value))){
      
      $errorMessage = _t('Form.STOCK_LEVEL_NOT_NUMERIC', 'This stock value is not a number.');
  		if ($msg = $this->getCustomValidationMessage()) {
  			$errorMessage = $msg;
  		}
      
      $validator->validationError(
  			$this->Name(),
  			$errorMessage,
  			"error"
  		);
  		$valid = false;
		} 
		
		if (isset($this->value) && is_numeric($this->value) && $this->value < -1) {
		  
		  $errorMessage = _t('Form.STOCK_LEVEL_NOT_NUMERIC', 'This stock value is incorrect.');
  		if ($msg = $this->getCustomValidationMessage()) {
  			$errorMessage = $msg;
  		}
      
      $validator->validationError(
  			$this->Name(),
  			$errorMessage,
  			"error"
  		);
  		$valid = false;
		}
	  return $valid;
	}
}