<?php
/**
 * An account page which displays the order history for any given {@link Member} and displays an individual {@link Order}.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage extends Page {

	/**
	 * Automatically create an AccountPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('AccountPage')) {
			$page = new AccountPage();
			$page->Title = 'Account';
			$page->Content = '';
			$page->URLSegment = 'account';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Account page \'Account\' created', 'created');
		}
		
		//Create a new group for customers
		$allGroups = DataObject::get('Group');
		$existingCustomerGroup = $allGroups->find('Title', 'Customers');
		if (!$existingCustomerGroup) {
		  
		  $customerGroup = new Group();
		  $customerGroup->Title = 'Customers';
		  $customerGroup->setCode($customerGroup->Title);
		  $customerGroup->write();
		}
	}
	
	/**
	 * Prevent CMS users from creating another account page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
  function canCreate($member = null) {
	  return false;
	}
	
	/**
	 * Prevent CMS users from deleting the account page.
	 * 
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
	  return false;
	}
	
	/**
	 * Prevent CMS users from unpublishing the account page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see AccountPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
  function canDeleteFromLive($member = null) {
	  return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see AccountPage::canDeleteFromLive()
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
 * Display the account page with listing of previous orders, and display an individual order.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class AccountPage_Controller extends Page_Controller {
  
  /**
   * Allowed actions that can be invoked.
   * 
   * @var Array Set of actions
   */
  static $allowed_actions = array (
    'index',
    'order',
  	'downloadproduct',
    'logout'
  );
  
  /**
   * Check access permissions for account page and return content for displaying the 
   * default page.
   * 
   * @return Array Content data for displaying the page.
   */
  function index() {
    
    Requirements::css('swipestripe/css/Shop.css');
    
    $memberID = Member::currentUserID();
    if (!$memberID) {
      return Security::permissionFailure($this, _t('AccountPage.LOGGED_IN',"You must be logged in to view this page."));
    }

    //Get the orders for this member
    $Orders = DataObject::get('Order', "MemberID = '" . Convert::raw2sql($memberID) . "'", "Created DESC");

    return array( 
      'Content' => $this->Content, 
      'Form' => $this->Form,
      'Orders' => $Orders,
      'Customer' => Customer::currentUser()
    );
  }

	/**
	 * Return the {@link Order} details for the current Order ID that we're viewing (ID parameter in URL).
	 * 
	 * @return Array Content for displaying the page
	 */
  function order($request) {

	  Requirements::css('swipestripe/css/Shop.css');

		$memberID = Member::currentUserID();
	  if (!Member::currentUserID()) {
      return Security::permissionFailure($this, _t('AccountPage.LOGGED_IN',"You must be logged in to view this page."));
    }

		if ($orderID = $request->param('ID')) {
		  
		  $order = DataObject::get_one('Order', "\"Order\".\"ID\" = $orderID");
		  $member = Customer::currentUser();
  		if (!$member || !$member->ID) {
        return Security::permissionFailure($this, _t('AccountPage.LOGGED_IN',"You must be logged in to view this page."));
      }
      
      if ($member && $member != $order->Member()) {
        return Security::permissionFailure($this, _t('AccountPage.CANNOT_VIEW_ORDER',"You cannot view orders that do not belong to you."));
      }
      
      if ($order && $order->exists()) {
        
        //Because this is the page that long payment processes direct back to, want to send
        //a receipt and order notification if they have not already been sent
        $order->sendReceipt();
        $order->sendNotification();
        
        return array(
					'Order' => $order
				);
      }
		}
		
		return array(
			'Order' => false,
			'Message' => _t('AccountPage.NO_ORDER_EXISTS',"You do not have any order corresponding to this ID.")
		);
	}
	
	/**
	 * Log the current member out and redirect to home page.
	 */
  public function logout() {
    Security::logout(false);
    Director::redirect("home/");
  }
	
}

