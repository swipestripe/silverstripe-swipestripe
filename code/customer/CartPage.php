<?php
/**
 * A cart page for the frontend to display contents of a cart to a visitor.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 * @version 1.0
 */
class CartPage extends Page {
  
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
	
	/**
	 * Prevent CMS users from creating another cart page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
  function canCreate($member = null) {
	  return false;
	}
	
	/**
	 * Prevent CMS users from deleting the cart page.
	 * 
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
	  return false;
	}
	
	/**
	 * Prevent CMS users from unpublishing the cart page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see CartPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
  function canDeleteFromLive($member = null) {
	  return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see CartPage::canDeleteFromLive()
	 * @return FieldSet Actions fieldset with unpublish action removed
	 */
	function getCMSActions() {
	  $actions = parent::getCMSActions();
	  $actions->removeByName('action_unpublish');
	  return $actions;
	}
	
	/**
	 * Remove page type dropdown to prevent users from changing page type.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldSet
	 */
  function getCMSFields() {
    $fields = parent::getCMSFields();
    $fields->removeByName('ClassName');
    return $fields;
	}
}

/**
 * Display the cart page, with cart form. Handle cart form actions.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 * @version 1.0
 */
class CartPage_Controller extends Page_Controller {
  
  /**
   * Include some CSS for the cart page.
   * 
   * @return Array Contents for page rendering
   */
  function index() {

    Requirements::css('swipestripe/css/Shop.css');

    return array( 
       'Content' => $this->Content, 
       'Form' => $this->Form 
    );
  }
	
	/**
	 * Form including quantities for items for displaying on the cart page.
	 * 
	 * @return CartForm A new cart form
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
    
    $cartForm = new CartForm($this, 'CartForm', $fields, $actions, $validator, $currentOrder);
    $cartForm->disableSecurityToken();
    return $cartForm;
	}
	
	/**
	 * Update the current cart quantities then redirect back to the cart page.
	 * 
	 * @param Array $data Data submitted from the form via POST
	 * @param Form $form Form that data was submitted from
	 */
	function updateCart(Array $data, Form $form) {
	  $this->saveCart($data, $form);
	  $this->redirectBack();
	}
	
	/**
	 * Remove an item from the cart
	 * 
	 * @see CartPage::updateCart()
	 * @param Array $data Data submitted from the form via POST
	 * @param Form $form Form that data was submitted from
	 */
	function removeItem(Array $data, Form $form) {
	  $itemID = isset($data['action_removeItem']) ? $data['action_removeItem'] : null;
	  if ($itemID) {
	    $data['Quantity'][$itemID] = 0;
	  }
	  $this->updateCart($data, $form);
	}
	
	/**
	 * Update the current cart quantities and redirect to checkout.
	 * 
	 * @param Array $data Data submitted from the form via POST
	 * @param Form $form Form that data was submitted from
	 */
	function goToCheckout(Array $data, Form $form) {
	  $this->saveCart($data, $form);
	  
	  if ($checkoutPage = DataObject::get_one('CheckoutPage')) {
	    $this->redirect($checkoutPage->AbsoluteLink());
	  }
	  else Debug::friendlyError(500);
	}
	
	/**
	 * Save the cart, update the order item quantities and the order total.
	 * 
	 * @param Array $data Data submitted from the form via POST
	 * @param Form $form Form that data was submitted from
	 */
	private function saveCart(Array $data, Form $form) {

	  $currentOrder = CartControllerExtension::get_current_order();
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