<?php
/**
 * Stock field for displaying and editing stock levels for {@link Product}s and
 * {@link Variation}s. The value for this field is represented in the {@link NumericField} 
 * stockLevelField. 
 * 
 * Unlimited stock has a value of -1
 * Out of stock has a value of 0
 * Other values > 0 are the actual stock level
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class StockField extends FormField {
  
  /**
   * Template filename
   * 
   * @var String
   */
  protected $template = "StockField";
  
  /**
   * {@link OptionSetField} for unlimited/limited stock level.
   * 
   * @var OptionSetField
   */
  protected $stockChoiceField;
  
  /**
   * {@link NumericField} for specifying limited stock level.
   * 
   * @var NumericField
   */
  protected $stockLevelField;
  
  /**
   * Create an {@link OptionSetField} and {@link NumericField}.
   * 
   * @param String $name
   * @param String $title
   * @param String $value
   * @param Product|Variation $object Object this stock level is for
   * @param String $maxLength
   * @param String $form
   */
  function __construct($name, $title = null, $value = "", $object, $maxLength = null, $form = null) {

    $quantity = $object->getUnprocessedQuantity();
    $cartQuantity = $quantity['InCarts'];
    $orderQuantity = $quantity['InOrders'];
    $label = sprintf(_t('StockField', 'Stock : %s are currently in shopping carts, %s in orders that have not been dispatched.'), $cartQuantity, $orderQuantity);
    
    $stockChoiceField = new OptionsetField('StockChoice', $label, array(
		  0 => _t('StockField.UNLIMITED',"Unlimited"),
		  1 => _t('StockField.SPECIFYSTOCK',"Specify Stock")
		));
    $this->stockChoiceField = $stockChoiceField;
    
    $stockField = new NumericField('Stock', '', $value, $maxLength, $form);
    $this->stockLevelField = $stockField;
    
    parent::__construct($name, $title, $value, $form);
	}
	
	/**
	 * Create the field for display in CMS.
	 * 
	 * (non-PHPdoc)
	 * @see FormField::Field()
	 * @return String
	 */
  function Field() {

    $this->stockLevelField->setForm($this->form);
    
    if ($this->value == -1) {
	    $this->stockLevelField->addExtraClass('HiddenStock');
	  }
	  return $this->stockLevelField->SmallFieldHolder();
	}
	
	/**
	 * Retrieve the {@link OptionsetField} for stock choice for display in CMS.
	 * 
	 * @return String
	 */
	function StockChoiceField() {

    $stockChoiceValue = ($this->value == -1) ? 0 : 1;
    $this->stockChoiceField->setValue($stockChoiceValue);
	  return $this->stockChoiceField->SmallFieldHolder();
	}
	
	/**
	 * Render the fields and include javascript.
	 * 
	 * (non-PHPdoc)
	 * @see FormField::FieldHolder()
	 * @return String
	 */
  function FieldHolder() {
    
    Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe/javascript/StockField.js');
		Requirements::customCSS('.HiddenStock{display:none;}');
		return $this->renderWith($this->template);
	}
	
	/**
	 * Set value of the {@link NumericField} because that is the actual value 
	 * of the stock level. If unlimited stock is selected the value is -1.
	 * 
	 * (non-PHPdoc)
	 * @see FormField::setValue()
	 * @return StockField
	 */
  function setValue($value) {
		$this->value = $value; 
		$this->stockLevelField->setValue($value);
		return $this;
	}
	
	/**
	 * Get the value of the stock level
	 * 
	 * (non-PHPdoc)
	 * @see FormField::Value()
	 * @return Int
	 */
	function Value() {
	  return $this->value;
	  return $this->stockLevelField->Value();
	}
	
  /**
   * Validate that the stock level is numeric and greater than -2.
   * -1 represents unlimited stock.
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
  			$this->getName(),
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
  			$this->getName(),
  			$errorMessage,
  			"error"
  		);
  		$valid = false;
		}
	  return $valid;
	}
}