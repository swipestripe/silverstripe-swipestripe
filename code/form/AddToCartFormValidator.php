<?php
class AddToCartFormValidator extends RequiredFields {

	/**
	 * Check that current order is valid
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function php($data) {

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check that variation exists if necessary
		$form = $this->form;
		$request = $this->form->getRequest();
		
		//Get product variations from options sent
    //TODO refactor this
	  $productVariations = new DataObjectSet();
    $options = $request->postVar('Options');
    $product = DataObject::get_by_id($data['ProductClass'], $data['ProductID']);
    $variations = $product->Variations();

    if ($variations && $variations->exists()) foreach ($variations as $variation) {
      
      $variationOptions = $variation->Options()->map('AttributeID', 'ID');
      if ($options == $variationOptions && $variation->isEnabled()) {
        $productVariations->push($variation);
      }
    }
    
	  if ((!$productVariations || !$productVariations->exists()) && $product->requiresVariation()) {
	    $this->form->sessionMessage(
  		  _t('Form.VARIATIONS_REQUIRED', 'This product requires options before it can be added to the cart.'),
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
	 */
	public function getForm() {
	  return $this->form;
	}
}