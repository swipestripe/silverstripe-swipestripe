<?php
class ProductOption extends DataObject {
  
  static $db = array(
    'Title' => 'Varchar(100)',
    'Amount' => 'Money',
    'Description' => 'Varchar(100)'
  );

  static $has_one = array(
    'Product' => 'Product'
  );

  static $extensions = array(
		"Versioned('Live')",
	);
	
  public function getCMSFields_forPopup() {
    
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(Product::$allowed_currency);

    return new FieldSet(
      new TextField('Title'),
      $amountField,
      new TextField('Description')
    );
  }
  
  /**
   * TODO remove Amount from this class
   * 
   * @deprecated
   */
  public function SummaryPrice() {
    return $this->owner->dbObject('Amount')->Nice();
  }
	
}