<?php
/**
 * Validator for {@link AddToCartForm} which validates that the product {@link Variation} is 
 * correct for the {@link Product} being added to the cart.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage form
 */
class AddToCartFormValidator extends RequiredFields {

	/**
	 * Check that current product variation is valid
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	function php($data) {

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check that variation exists if necessary
		$form = $this->form;
		$request = $this->form->getRequest();
		
		//Get product variations from options sent
    //TODO refactor this
	  $productVariations = new DataList();
    $options = $request->postVar('Options');
    $product = DataObject::get_by_id($data['ProductClass'], $data['ProductID']);
    $variations = ($product) ? $product->Variations() : new DataList();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {
      
      $variationOptions = $variation->Options()->map('AttributeID', 'ID');
      if ($options == $variationOptions && $variation->isEnabled()) {
        $productVariations->push($variation);
      }
    }
    
	  if ((!$productVariations || !$productVariations->exists()) && $product && $product->requiresVariation()) {
	    $this->form->sessionMessage(
  		  _t('Form.VARIATIONS_REQUIRED', 'This product requires options before it can be added to the cart.'),
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
  		return $valid;
	  }
	  
	  //Validate that the product/variation being added is inStock()
	  $stockLevel = 0;
	  if ($product) {
	    if ($product->requiresVariation()) {
	      $stockLevel = $productVariations->First()->StockLevel()->Level;
	    }
	    else {
	      $stockLevel = $product->StockLevel()->Level;
	    }
	  }
	  if ($stockLevel == 0) {
	    $this->form->sessionMessage(
  		  _t('Form.STOCK_LEVEL', ''), //"Sorry, this product is out of stock." - similar message will already be displayed on product page
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
	  }
	  
	  //Validate the quantity is not greater than the available stock
	  $quantity = $request->postVar('Quantity');
	  if ($stockLevel > 0 && $stockLevel < $quantity) {
	    $this->form->sessionMessage(
  		  _t('Form.STOCK_LEVEL_MORE_THAN_QUANTITY', 'The quantity is greater than available stock for this product.'),
  		  'bad'
  		);
  		
  		//Have to set an error for Form::validate()
  		$this->errors[] = true;
  		$valid = false;
	  }

		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form The current form
	 */
	public function getForm() {
	  return $this->form;
	}
}