<?php
/**
 * Mixin for other data objects that are to represent products.
 * 
 * @author frankmullenger
 */
class ProductDecorator extends DataObjectDecorator {
  
  /**
   * Add fields for products such as Amount
   * 
   * @see DataObjectDecorator::extraStatics()
   */
	function extraStatics() {
		return array(
			'db' => array(
				'Amount' => 'Money',
			)
		);
	}
	
	/**
	 * Update the CMS with form fields for extra db fields above
	 * 
	 * @see DataObjectDecorator::updateCMSFields()
	 */
	function updateCMSFields(&$fields) {

	  //TODO: get allowed currencies from Payment class like:
	  //$amountField->setAllowedCurrencies(DPSAdapter::$allowed_currencies);
	  
		$amountField = new MoneyField('Amount', 'Price');
		$amountField->setAllowedCurrencies(array(
  		'NZD'=>'New Zealand Dollar'
  	));
		
  	//TODO: Assuming that the dataobject being decorated is a Page not ideal?
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
	}
	
	/**
	 * Helper to get URL for adding a product to the cart
	 * 
	 * @return String URL to add product to the cart
	 */
  function AddToCartLink($num = null) {
    
    $controller = Controller::curr();

		$productID = $this->owner->ID;
		$productClass = $this->owner->ClassName;
		
		return Director::absoluteURL($controller->Link()."add/?ProductClass=$productClass&ProductID=$productID");
	}
	
	/**
	 * Helper to get URL for removing a product from the cart
	 * 
	 * @return String URL to remove a product from the cart
	 */
  function RemoveFromCartLink($num = null) {
    
    $controller = Controller::curr();
    
		$productID = $this->owner->ID;
		$productClass = $this->owner->ClassName;
		
		return Director::absoluteURL($controller->Link()."remove/?ProductClass=$productClass&ProductID=$productID");
	}
	
	/**
	 * Helper to get URL for clearing the cart
	 * 
	 * @return String URL to clear the cart
	 */
	function ClearCartLink() {
	  
	  $controller = Controller::curr();
	  return Director::absoluteURL($controller->Link()."clear/");
	}
	
	/**
	 * Helper to get URL for the checkout page
	 * TODO if checkout page does not exist throw error
	 * 
	 * @return String URL for the checkout page
	 */
	function GoToCheckoutLink() {
	  //get the checkount page and go to it
	  $checkoutPage = DataObject::get_one('CheckoutPage');
		return $checkoutPage->Link();
	}
}


