<?php
/**
 * Mixin for other data objects that are to represent product options.
 * 
 * @author frankmullenger
 */
class ProductOptionDecorator extends DataObjectDecorator {
  
  /**
   * Add fields for products such as Amount
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'db' => array(
				'Title' => 'Varchar(100)',
        'Amount' => 'Money',
        'Description' => 'Varchar(100)'
			),
			'has_one' => array(
        //'Product' => 'SomeProductClass' //Product relation required in concrete page
      )
		);
	}
	
	public function getCMSFields_forPopup() {
    
    $amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(ProductDecorator::$allowed_currency);

    return new FieldSet(
      new TextField('Title'),
      $amountField,
      new TextField('Description')
    );
  }
  
  public function SummaryPrice() {
    return $this->owner->dbObject('Amount')->Nice();
  }

}


