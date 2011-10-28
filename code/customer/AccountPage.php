<?php
/**
 * Account page shows order history and a form to allow
 * the member to edit his/her details.
 */
class AccountPage extends Page {

	static $db = array(
	);

	/**
	 * Automatically create an AccountPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('AccountPage')) {
			$page = new AccountPage();
			$page->Title = 'Account';
			$page->Content = '<p>View your previous orders below.</p>';
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

class AccountPage_Controller extends Page_Controller {
  
  static $allowed_actions = array (
    'order',
    'orders',
  	'downloadProduct',
    'logout'
  );
  
  /**
   * Check access permissions for account page.
   */
  function index() {
    
    Requirements::css('stripeycart/css/StripeyCart.css');
    
    $memberID = Member::currentUserID();
    if (!$memberID) {
      return Security::permissionFailure($this, 'You must be logged in to view this page.');
    }

    return array( 
       'Content' => $this->Content, 
       'Form' => $this->Form 
    );
  }

	/**
	 * Return the {@link Order} details for the current
	 * Order ID that we're viewing (ID parameter in URL).
	 * 
	 * TODO pass errors back using session instead
	 *
	 * @return array of template variables
	 */
	function order($request) {

	  Requirements::css('stripeycart/css/StripeyCart.css');
    
		$memberID = Member::currentUserID();
	  if (!Member::currentUserID()) {
      return Security::permissionFailure($this, 'You must be logged in to view this page.');
    }

		if($orderID = $request->param('ID')) {
		  
		  $order = DataObject::get_one('Order', "`Order`.`ID` = $orderID");
		  $member = Member::currentUser();
  		if (!$member->ID) {
        return Security::permissionFailure($this, 'You must be logged in to view this page.');
      }
      
      if ($member != $order->Member()) {
        return Security::permissionFailure($this, 'You cannot view orders that do not belong to you.');
      }
      
      if ($order) {
        return array(
					'Order' => $order
				);
      }
		}
		
		return array(
			'Order' => false,
			'Message' => 'You do not have any order corresponding to this ID.'
		);
	}
	
	/**
	 * Retreive processed (non cart) orders this member has made
	 * 
	 * @return DataObjectSet 
	 */
	function orders() {

	  $member = Member::currentUser();
	  if (!$member || !$member->ID) {
      return Security::permissionFailure($this, 'You must be logged in to view this page.');
    }
    
    return $member->Orders();
	}
	
	/**
	 * Redirect browser to the download location, increment number of times
	 * this item has been downloaded.
	 * 
	 * If the item has been downloaded too many times redirects back with 
	 * error message.
	 * 
	 * @param SS_HTTPRequest $request
	 */
	function downloadProduct(SS_HTTPRequest $request) {
	  
	  $memberID = Member::currentUserID();
	  if (!Member::currentUserID()) {
      return Security::permissionFailure($this, 'You must be logged in to view this page.');
    }
	  
	  //TODO can only download product if order has been paid for

	  $item = DataObject::get_by_id('Item', $request->requestVar('ItemID'));
	  if ($item->exists()) {
	    
	    $virtualProduct = $item->Object();
	    
	    if (isset($virtualProduct->FileLocation) && $virtualProduct->FileLocation) {
  	    if ($downloadLocation = $virtualProduct->downloadLocation()) {
    	    $item->DownloadCount = $item->DownloadCount + 1;
    	    $item->write();

    	    Director::redirect($downloadLocation);
    	    return;
    	  }
	    }
	  }

	  //TODO set an error message
	  Director::redirectBack();
	}
	
  public function logout() {
    Security::logout(false);
    Director::redirect("home/");
  }

}

