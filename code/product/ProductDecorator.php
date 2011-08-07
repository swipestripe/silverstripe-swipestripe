<?php
/**
 * Mixin for other data objects that are to represent products.
 * 
 * @author frankmullenger
 */
class ProductDecorator extends DataObjectDecorator {
  
  /**
   * Currency allowed to be used for products
   * Code match Payment::$site_currency
   * Only once currency site wide allowed
   * 
   * @var Array Currency code indexes currency name
   */
  public static $allowed_currency = array(
    'NZD' => 'New Zealand Dollar'
  );
  
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

		$amountField = new MoneyField('Amount', 'Amount');
		$amountField->setAllowedCurrencies(self::$allowed_currency);
		
		$fields->addFieldToTab('Root.Content.Main', $amountField, 'Content');
	}
	
	/**
	 * Set the currency for all products.
	 * Must match site curency
	 * 
	 * @param array $currency
	 */
	public static function set_allowed_currency(Array $currency) {
	  if (count($currency) && array_key_exists(Payment::site_currency(), $currency)) {
	    self::$allowed_currency = $currency;
	  }
	  else {
	    user_error("Cannot set allowed currency. Currency must match: ".Payment::site_currency(), E_USER_WARNING);
	  }
	}
	
	/**
	 * Generate the get params for cart links
	 * 
	 * @see ProductDecorator::AddToCartLink()
	 * @see ProductDecorator::RemoveFromCartLink()
	 * @param String $productClass Class name of product
	 * @param Int $productID ID of product
	 * @param Int $quantity Quantity of product
	 * @param String $redirectURL URL to redirect to 
	 * @return String Get params joined by &
	 * @deprecated
	 */
	private function generateGetString($productClass, $productID, $quantity = 1, $redirectURL = null) {
	  
	  $string = "ProductClass=$productClass&ProductID=$productID";
	  if ($quantity && is_numeric($quantity) && $quantity > 0) $string .= "&Quantity=$quantity";
	  if ($redirectURL && Director::is_site_url($redirectURL)) $string .= "&Redirect=$redirectURL"; 
	  return $string;
	}
	
	/**
	 * Helper to get URL for adding a product to the cart
	 * 
	 * @return String URL to add product to the cart
	 * @deprecated
	 */
  function AddToCartLink($quantity = null, $redirectURL = null) {

		$getParams = $this->generateGetString(
		  $this->owner->ClassName, 
		  $this->owner->ID,
		  $quantity,
		  $redirectURL
		);
		return Director::absoluteURL(Controller::curr()->Link()."add/?".$getParams);
	}

	/**
	 * Helper to get URL for removing a product from the cart
	 * 
	 * @return String URL to remove a product from the cart
	 * @deprecated
	 */
  function RemoveFromCartLink($quantity = null, $redirectURL = null) {

		$getParams = $this->generateGetString(
		  $this->owner->ClassName, 
		  $this->owner->ID,
		  $quantity,
		  $redirectURL
		);
		return Director::absoluteURL(Controller::curr()->Link()."remove/?".$getParams);
	}
	
	/**
	 * Helper to get URL for clearing the cart
	 * 
	 * @return String URL to clear the cart
	 * @deprecated
	 */
	function ClearCartLink() {
	  return Director::absoluteURL(Controller::curr()->Link()."clear/");
	}
	
	/**
	 * Helper to get URL for the checkout page
	 * TODO if checkout page does not exist throw error
	 * 
	 * @return String URL for the checkout page
	 */
	function GoToCheckoutLink() {
		return DataObject::get_one('CheckoutPage')->Link();
	}

}


