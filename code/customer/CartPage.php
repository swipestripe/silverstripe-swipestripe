<?php
class CartPage extends Page {

  public function getCMSFields() {
    $fields = parent::getCMSFields();
    return $fields;
  }
  
	/**
	 * Automatically create a CheckoutPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if(!DataObject::get_one('CartPage')) {
			$page = new CartPage();
			$page->Title = 'Cart';
			$page->Content = '';
			$page->URLSegment = 'cart';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message("Cart page 'Cart' created", 'created');
		}
	}
	
  function canCreate($member = null) {
	  return false;
	}
	
	function canDelete($member = null) {
	  return false;
	}
	
  function canDeleteFromLive($member = null) {
	  return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 */
	function getCMSActions() {
	  $actions = parent::getCMSActions();
	  $actions->removeByName('action_unpublish');
	  return $actions;
	}
}

class CartPage_Controller extends Page_Controller {
  
  /**
   * Include some CSS for the cart page
   */
  function index() {

    Requirements::css('shop/css/Shop.css');

    return array( 
       'Content' => $this->Content, 
       'Form' => $this->Form 
    );
  }
	
	/**
	 * Form including quantities for items for displaying on the cart page
	 * 
	 * TODO validator for positive quantity
	 * 
	 * @see CheckoutForm
	 */
	function CartForm() {
	  $fields = new FieldSet();
	  $validator = new CartFormValidator();
	  $currentOrder = $this->Cart();
	  $items = $currentOrder->Items();
	  
	  if ($items) foreach ($items as $item) {
	    
	    $quantityField = new CartQuantityField('Quantity['.$item->ID.']', '', $item->Quantity, null, null, $item);
	    
	    $fields->push($quantityField); 
	    
	    $itemOptions = $item->ItemOptions();
	    if ($itemOptions && $itemOptions->exists()) foreach($itemOptions as $itemOption) {
	      //TODO if item option is not a Variation then add it as another row to the checkout
	      //Like gift wrapping as an option perhaps
	    } 
	    
	    $validator->addRequiredField('Quantity['.$item->ID.']');
	  }
	  
    $actions = new FieldSet(
      new FormAction('updateCart', 'Update Cart'),
      new FormAction('goToCheckout', 'Go To Checkout')
    );
    
    return new CartForm($this, 'CartForm', $fields, $actions, $validator, $currentOrder);
	}
	
	/**
	 * Update the current cart quantities
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
	function updateCart(Array $data, Form $form) {
	  $this->saveCart($data, $form);
	  $this->redirectBack();
	}
	
	/**
	 * Update the current cart quantities and redirect to checkout
	 * 
	 * @param Array $data
	 * @param Form $form
	 */
	function goToCheckout(Array $data, Form $form) {
	  $this->saveCart($data, $form);
	  
	  if ($checkoutPage = DataObject::get_one('CheckoutPage')) {
	    $this->redirect($checkoutPage->AbsoluteLink());
	  }
	  else Debug::friendlyError(500);
	}
	
	private function saveCart(Array $data, Form $form) {
	  $currentOrder = Product_Controller::get_current_order();
	  $quantities = (isset($data['Quantity'])) ?$data['Quantity'] :null;

	  if ($quantities) foreach ($quantities as $itemID => $quantity) {

	    if ($item = $currentOrder->Items()->find('ID', $itemID)) {
  	    if ($quantity == 0) {
    	    $item->delete();
    	  }
    	  else {
    	    $item->Quantity = $quantity;
  	      $item->write();
    	  }
	    }
	  }
	  $currentOrder->updateTotal();
	}
}