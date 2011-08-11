<?php
/**
 * TODO Only allow Product as a parent for variations
 * 
 * @author frankmullenger
 */
class ProductVariation extends Page {
  
  protected $firstWrite = false;

  public static $db = array(
    'Amount' => 'Money',
    'Stock' => 'Int'
  );

  public static $has_one = array(
    'Image' => 'ProductImage'
  );
  
  public static $defaults = array(
    'Stock' => -1,
    'ShowInMenus' => false,
    'ShowInSearch' => false
  );
    
	function getCMSFields() {
    $fields = parent::getCMSFields();

    //TODO Put in image uploadify field
    
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);	
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
		
		$fields->addFieldToTab('Root.Content.Main', new NumericField('Stock'), 'Content');
		
		$fields->removeFieldFromTab('Root.Content.Main', 'Content');
    
    return $fields;
	}

  function onBeforeWrite() {
    parent::onBeforeWrite();
    if (!$this->ID) $this->firstWrite = true;
  }
 
  /**
   * Copy the original product options or generate the default product 
   * options
   * 
   * @see SiteTree::onAfterWrite()
   */
  function onAfterWrite() {
    parent::onAfterWrite();
    
    if ($this->firstWrite) {
      
      $original = DataObject::get_by_id($this->class, $this->original['ID']);
      if ($original) {
        //TODO duplicate the image here
      }
    }
  }
  
  public function inStock() {
    if ($this->Stock == -1) return true;
    if ($this->Stock == 0) return false;
    
    //TODO need to check what is currently in people's carts
    if ($this->Stock > 0) return true; 
  }
  
}
class ProductVariation_Controller extends Page_Controller {

}